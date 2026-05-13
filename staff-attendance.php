<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

function coolopz_staff_attendance_presets_page(): array
{
    return [
        'full_day' => [
            'label' => 'Full Day (9:00 AM - 5:00 PM)',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
        ],
        'half_day_am' => [
            'label' => 'Half Day Morning (9:00 AM - 1:00 PM)',
            'clock_in' => '09:00:00',
            'clock_out' => '13:00:00',
        ],
        'half_day_pm' => [
            'label' => 'Half Day Afternoon (1:00 PM - 5:00 PM)',
            'clock_in' => '13:00:00',
            'clock_out' => '17:00:00',
        ],
    ];
}

function coolopz_staff_attendance_shift_type_page(string $clockInAt, string $clockOutAt): string
{
    $timeRange = date('H:i:s', strtotime($clockInAt)) . '|' . date('H:i:s', strtotime($clockOutAt));

    return match ($timeRange) {
        '09:00:00|17:00:00' => 'full_day',
        '09:00:00|13:00:00' => 'half_day_am',
        '13:00:00|17:00:00' => 'half_day_pm',
        default => 'full_day',
    };
}

function coolopz_staff_attendance_find_page(int $attendanceId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, user_id, clock_in_at, clock_out_at, source
         FROM staff_attendance
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute([
        'id' => $attendanceId,
    ]);
    $attendance = $statement->fetch();

    return $attendance === false ? null : $attendance;
}

function coolopz_staff_attendance_find_manual_page(int $attendanceId): ?array
{
    $attendance = coolopz_staff_attendance_find_page($attendanceId);

    if ($attendance === null || (string) ($attendance['source'] ?? '') !== 'manual') {
        return null;
    }

    return $attendance;
}

function coolopz_staff_attendance_log_audit_page(
    ?int $attendanceId,
    string $actionName,
    ?int $changedByUserId,
    int $affectedUserId,
    ?string $oldClockInAt,
    ?string $oldClockOutAt,
    ?string $newClockInAt,
    ?string $newClockOutAt,
    ?string $oldSource,
    ?string $newSource
): void {
    $statement = coolopz_db()->prepare(
        'INSERT INTO staff_attendance_audit (
            attendance_id,
            action_name,
            changed_by_user_id,
            affected_user_id,
            old_clock_in_at,
            old_clock_out_at,
            new_clock_in_at,
            new_clock_out_at,
            old_source,
            new_source
        ) VALUES (
            :attendance_id,
            :action_name,
            :changed_by_user_id,
            :affected_user_id,
            :old_clock_in_at,
            :old_clock_out_at,
            :new_clock_in_at,
            :new_clock_out_at,
            :old_source,
            :new_source
        )'
    );
    $statement->execute([
        'attendance_id' => $attendanceId,
        'action_name' => $actionName,
        'changed_by_user_id' => $changedByUserId,
        'affected_user_id' => $affectedUserId,
        'old_clock_in_at' => $oldClockInAt,
        'old_clock_out_at' => $oldClockOutAt,
        'new_clock_in_at' => $newClockInAt,
        'new_clock_out_at' => $newClockOutAt,
        'old_source' => $oldSource,
        'new_source' => $newSource,
    ]);
}

function coolopz_staff_attendance_save_manual_page(int $attendanceId, int $userId, string $workDate, string $shiftType, ?int $changedByUserId): void
{
    $presets = coolopz_staff_attendance_presets_page();

    if (!isset($presets[$shiftType])) {
        throw new RuntimeException('Select a valid attendance type.');
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $workDate);
    if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $workDate) {
        throw new RuntimeException('Select a valid attendance date.');
    }

    $clockInAt = $workDate . ' ' . $presets[$shiftType]['clock_in'];
    $clockOutAt = $workDate . ' ' . $presets[$shiftType]['clock_out'];
    $pdo = coolopz_db();
    $overlapStatement = $pdo->prepare(
        'SELECT 1
         FROM staff_attendance
         WHERE user_id = :user_id
           AND id <> :attendance_id
           AND clock_in_at < :clock_out_at
           AND COALESCE(clock_out_at, :max_clock_out_at) > :clock_in_at
         LIMIT 1'
    );
    $overlapStatement->execute([
        'attendance_id' => $attendanceId,
        'user_id' => $userId,
        'clock_in_at' => $clockInAt,
        'clock_out_at' => $clockOutAt,
        'max_clock_out_at' => '9999-12-31 23:59:59',
    ]);

    if ($overlapStatement->fetch() !== false) {
        throw new RuntimeException('This staff member already has attendance recorded for the selected time range.');
    }

    if ($attendanceId > 0) {
        $attendance = coolopz_staff_attendance_find_manual_page($attendanceId);

        if ($attendance === null) {
            throw new RuntimeException('The selected manual attendance record could not be found.');
        }

        $updateStatement = $pdo->prepare(
            'UPDATE staff_attendance
             SET user_id = :user_id,
                 clock_in_at = :clock_in_at,
                 clock_out_at = :clock_out_at
             WHERE id = :id
               AND source = :source'
        );
        $updateStatement->execute([
            'id' => $attendanceId,
            'user_id' => $userId,
            'clock_in_at' => $clockInAt,
            'clock_out_at' => $clockOutAt,
            'source' => 'manual',
        ]);

        coolopz_staff_attendance_log_audit_page(
            $attendanceId,
            'update',
            $changedByUserId,
            $userId,
            (string) $attendance['clock_in_at'],
            (string) $attendance['clock_out_at'],
            $clockInAt,
            $clockOutAt,
            (string) $attendance['source'],
            'manual'
        );

        return;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO staff_attendance (user_id, clock_in_at, clock_out_at, source)
         VALUES (:user_id, :clock_in_at, :clock_out_at, :source)'
    );
    $insertStatement->execute([
        'user_id' => $userId,
        'clock_in_at' => $clockInAt,
        'clock_out_at' => $clockOutAt,
        'source' => 'manual',
    ]);

    coolopz_staff_attendance_log_audit_page(
        (int) coolopz_db()->lastInsertId(),
        'create',
        $changedByUserId,
        $userId,
        null,
        null,
        $clockInAt,
        $clockOutAt,
        null,
        'manual'
    );
}

function coolopz_staff_attendance_delete_manual_page(int $attendanceId, ?int $changedByUserId): void
{
    $attendance = coolopz_staff_attendance_find_page($attendanceId);

    if ($attendance === null) {
        throw new RuntimeException('The selected attendance record could not be found.');
    }

    $statement = coolopz_db()->prepare(
        'DELETE FROM staff_attendance WHERE id = :id'
    );
    $statement->execute([
        'id' => $attendanceId,
    ]);

    coolopz_staff_attendance_log_audit_page(
        $attendanceId,
        'delete',
        $changedByUserId,
        (int) $attendance['user_id'],
        (string) $attendance['clock_in_at'],
        (string) $attendance['clock_out_at'],
        null,
        null,
        (string) $attendance['source'],
        null
    );
}

function coolopz_staff_attendance_page_entries(int $userId, int $limit = 100): array
{
    $limit = max(1, min($limit, 365));
    $statement = coolopz_db()->prepare(
        'SELECT id,
                user_id,
                clock_in_at,
                clock_out_at,
                source,
                TIMESTAMPDIFF(MINUTE, clock_in_at, COALESCE(clock_out_at, CURRENT_TIMESTAMP)) AS worked_minutes,
                created_at,
                updated_at
         FROM staff_attendance
         WHERE user_id = :user_id
         ORDER BY clock_in_at DESC, id DESC
         LIMIT ' . $limit
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function coolopz_attendance_source_badge_class(string $source): string
{
    return match ($source) {
        'manual' => 'status-urgent',
        'clock' => 'status-progress',
        default => 'status-queued',
    };
}

function coolopz_attendance_source_label(string $source): string
{
    return match ($source) {
        'manual' => 'Manual',
        'clock' => 'Clock',
        default => 'Unknown',
    };
}

coolopz_require_role(['Operations Admin']);

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Staff Attendance';
$activePage = 'staff';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);
$currentUserId = isset($currentUser['id']) ? (int) $currentUser['id'] : null;
$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$selectedStaffUser = $selectedUserId > 0 ? coolopz_find_staff_user($selectedUserId) : null;
$editingAttendanceId = isset($_GET['edit_attendance']) ? (int) $_GET['edit_attendance'] : 0;
$errorMessage = '';
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'attendance-added' => 'Manual attendance added successfully.',
    'attendance-updated' => 'Manual attendance updated successfully.',
    'attendance-deleted' => 'Manual attendance deleted successfully.',
    default => '',
};
$attendancePresets = coolopz_staff_attendance_presets_page();
$manualAttendanceForm = [
    'attendance_id' => 0,
    'user_id' => $selectedUserId,
    'work_date' => date('Y-m-d'),
    'shift_type' => 'full_day',
];
$showAttendanceModal = false;

if ($selectedStaffUser === null) {
    $errorMessage = 'The selected staff account could not be found.';
}

if ($selectedStaffUser !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'manual_attendance') {
        $manualAttendanceForm = [
            'attendance_id' => (int) ($_POST['attendance_id'] ?? 0),
            'user_id' => (int) ($_POST['attendance_user_id'] ?? $selectedUserId),
            'work_date' => trim((string) ($_POST['work_date'] ?? date('Y-m-d'))),
            'shift_type' => trim((string) ($_POST['shift_type'] ?? 'full_day')),
        ];
        $showAttendanceModal = true;

        if ($manualAttendanceForm['user_id'] !== (int) $selectedStaffUser['id']) {
            $errorMessage = 'Attendance correction can only be added for the selected staff member.';
        } else {
            try {
                coolopz_staff_attendance_save_manual_page(
                    $manualAttendanceForm['attendance_id'],
                    $manualAttendanceForm['user_id'],
                    $manualAttendanceForm['work_date'],
                    $manualAttendanceForm['shift_type'],
                    $currentUserId
                );
                $message = $manualAttendanceForm['attendance_id'] > 0 ? 'attendance-updated' : 'attendance-added';
                header('Location: staff-attendance.php?user_id=' . urlencode((string) $selectedStaffUser['id']) . '&message=' . urlencode($message));
                exit;
            } catch (RuntimeException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }
    } elseif ($action === 'delete_attendance') {
        $attendanceId = (int) ($_POST['attendance_id'] ?? 0);

        try {
            $attendance = coolopz_staff_attendance_find_page($attendanceId);
            if ($attendance === null || (int) $attendance['user_id'] !== (int) $selectedStaffUser['id']) {
                throw new RuntimeException('The selected attendance record could not be found.');
            }

            coolopz_staff_attendance_delete_manual_page($attendanceId, $currentUserId);
            header('Location: staff-attendance.php?user_id=' . urlencode((string) $selectedStaffUser['id']) . '&message=attendance-deleted');
            exit;
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }
    }
}

if ($selectedStaffUser !== null && $editingAttendanceId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editingAttendance = coolopz_staff_attendance_find_manual_page($editingAttendanceId);

    if ($editingAttendance === null || (int) $editingAttendance['user_id'] !== (int) $selectedStaffUser['id'] || empty($editingAttendance['clock_out_at'])) {
        $errorMessage = 'The selected manual attendance record could not be found.';
    } else {
        $manualAttendanceForm = [
            'attendance_id' => (int) $editingAttendance['id'],
            'user_id' => (int) $editingAttendance['user_id'],
            'work_date' => date('Y-m-d', strtotime((string) $editingAttendance['clock_in_at'])),
            'shift_type' => coolopz_staff_attendance_shift_type_page((string) $editingAttendance['clock_in_at'], (string) $editingAttendance['clock_out_at']),
        ];
        $showAttendanceModal = true;
    }
}

$attendanceEntries = $selectedStaffUser !== null ? coolopz_staff_attendance_page_entries((int) $selectedStaffUser['id']) : [];
$clockSummary = $selectedStaffUser !== null
    ? coolopz_fetch_staff_clock_summary((int) $selectedStaffUser['id'])
    : ['today_minutes' => 0, 'week_minutes' => 0, 'open_entry' => null, 'last_entry' => null];

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Attendance List</span>
                <p class="hero-copy">Review the full attendance history for a selected staff member, including clock-based and manual entries.</p>
            </section>

<?php if ($errorMessage !== ''): ?>
            <div class="login-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php else: ?>
<?php if ($successMessage !== ''): ?>
            <div class="form-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Staff Member</span>
                        <strong class="stat-value"><?= htmlspecialchars($selectedStaffUser['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p><?= htmlspecialchars($selectedStaffUser['role_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Hours Today</span>
                        <strong class="stat-value"><?= htmlspecialchars(coolopz_format_work_minutes((int) $clockSummary['today_minutes']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Tracked time for the selected staff member today.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Hours This Week</span>
                        <strong class="stat-value"><?= htmlspecialchars(coolopz_format_work_minutes((int) $clockSummary['week_minutes']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Tracked time for the current week.</p>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Entries</span>
                                <h2 class="panel-title">Attendance History for <?= htmlspecialchars($selectedStaffUser['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <div class="staff-actions">
                                <button type="button" class="btn btn-portal-primary btn-sm" data-bs-toggle="modal" data-bs-target="#attendanceCorrectionModal">Add Correction</button>
                                <span class="subtle-note"><?= htmlspecialchars((string) count($attendanceEntries), ENT_QUOTES, 'UTF-8') ?> entries</span>
                                <a class="btn btn-portal-secondary btn-sm" href="staff.php">Back to Staff</a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Duration</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php if ($attendanceEntries === []): ?>
                                    <tr>
                                        <td colspan="7" class="subtle-note">No attendance entries found for this staff member.</td>
                                    </tr>
<?php else: ?>
<?php foreach ($attendanceEntries as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime((string) $entry['clock_in_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('h:i A', strtotime((string) $entry['clock_in_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(!empty($entry['clock_out_at']) ? date('h:i A', strtotime((string) $entry['clock_out_at'])) : 'Still active', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(coolopz_format_work_minutes((int) $entry['worked_minutes']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-badge <?= coolopz_attendance_source_badge_class((string) ($entry['source'] ?? '')) ?>"><?= htmlspecialchars(coolopz_attendance_source_label((string) ($entry['source'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><span class="status-badge <?= !empty($entry['clock_out_at']) ? 'status-complete' : 'status-progress' ?>"><?= htmlspecialchars(!empty($entry['clock_out_at']) ? 'Closed' : 'Open Shift', ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
<?php if (($entry['source'] ?? '') === 'manual' && !empty($entry['clock_out_at'])): ?>
                                            <div class="staff-actions">
                                                <a class="btn btn-portal-secondary btn-sm" href="staff-attendance.php?user_id=<?= htmlspecialchars((string) $selectedStaffUser['id'], ENT_QUOTES, 'UTF-8') ?>&edit_attendance=<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                                <form method="post" class="m-0" onsubmit="return confirm('Delete this manual attendance entry?');">
                                                    <input type="hidden" name="action" value="delete_attendance">
                                                    <input type="hidden" name="attendance_id" value="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
<?php elseif (($entry['source'] ?? '') === 'clock'): ?>
                                            <form method="post" class="m-0" onsubmit="return confirm('Delete this clock attendance record?');">
                                                <input type="hidden" name="action" value="delete_attendance">
                                                <input type="hidden" name="attendance_id" value="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
<?php else: ?>
                                            <span class="subtle-note">-</span>
<?php endif; ?>
                                        </td>
                                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <div class="modal fade" id="attendanceCorrectionModal" tabindex="-1" aria-labelledby="attendanceCorrectionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="attendanceCorrectionModalLabel"><?= htmlspecialchars($manualAttendanceForm['attendance_id'] > 0 ? 'Edit Manual Attendance' : 'Add Manual Attendance', ENT_QUOTES, 'UTF-8') ?></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="manual_attendance">
                                <input type="hidden" name="attendance_id" value="<?= htmlspecialchars((string) $manualAttendanceForm['attendance_id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="attendance_user_id" value="<?= htmlspecialchars((string) $selectedStaffUser['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <div class="mb-3">
                                    <label class="form-label" for="attendance_staff_name">Staff Member</label>
                                    <input class="form-control" id="attendance_staff_name" type="text" value="<?= htmlspecialchars($selectedStaffUser['full_name'] . ' (' . $selectedStaffUser['username'] . ')', ENT_QUOTES, 'UTF-8') ?>" disabled>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="work_date">Work Date</label>
                                        <input class="form-control" id="work_date" name="work_date" type="date" value="<?= htmlspecialchars($manualAttendanceForm['work_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="shift_type">Attendance Type</label>
                                        <select class="form-select" id="shift_type" name="shift_type" required>
<?php foreach ($attendancePresets as $presetKey => $preset): ?>
                                            <option value="<?= htmlspecialchars($presetKey, ENT_QUOTES, 'UTF-8') ?>"<?= $manualAttendanceForm['shift_type'] === $presetKey ? ' selected' : '' ?>><?= htmlspecialchars($preset['label'], ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-text mt-3">Full day uses 9:00 AM to 5:00 PM. Half-day presets use either 9:00 AM to 1:00 PM or 1:00 PM to 5:00 PM.</div>
                            </div>
                            <div class="modal-footer">
<?php if ($manualAttendanceForm['attendance_id'] > 0): ?>
                                <a class="btn btn-portal-secondary" href="staff-attendance.php?user_id=<?= htmlspecialchars((string) $selectedStaffUser['id'], ENT_QUOTES, 'UTF-8') ?>">Cancel Edit</a>
<?php endif; ?>
                                <button type="button" class="btn btn-portal-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-portal-primary"><?= htmlspecialchars($manualAttendanceForm['attendance_id'] > 0 ? 'Save Attendance Changes' : 'Add Attendance', ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<?php if ($showAttendanceModal): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modalElement = document.getElementById('attendanceCorrectionModal');
                    if (modalElement) {
                        var modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    }
                });
            </script>
<?php endif; ?>
<?php endif; ?>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>