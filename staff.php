<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

function coolopz_staff_format_work_minutes(int $minutes): string
{
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
                return sprintf('%dh %02dm', $hours, $remainingMinutes);
        }

        return sprintf('%dm', $remainingMinutes);
}

function coolopz_staff_attendance_overview(): array
{
        $statement = coolopz_db()->query(
                "SELECT users.id,
                                users.username,
                                users.full_name,
                                users.email,
                                users.role_name,
                                open_shift.clock_in_at AS active_clock_in_at,
                                last_shift.clock_in_at AS last_clock_in_at,
                                last_shift.clock_out_at AS last_clock_out_at,
                                COALESCE(today_stats.today_minutes, 0) AS today_minutes,
                                COALESCE(week_stats.week_minutes, 0) AS week_minutes
                 FROM users
                 LEFT JOIN staff_attendance AS open_shift
                     ON open_shift.id = (
                                SELECT attendance_open.id
                                FROM staff_attendance AS attendance_open
                                WHERE attendance_open.user_id = users.id
                                    AND attendance_open.clock_out_at IS NULL
                                ORDER BY attendance_open.clock_in_at DESC, attendance_open.id DESC
                                LIMIT 1
                     )
                 LEFT JOIN staff_attendance AS last_shift
                     ON last_shift.id = (
                                SELECT attendance_last.id
                                FROM staff_attendance AS attendance_last
                                WHERE attendance_last.user_id = users.id
                                ORDER BY attendance_last.clock_in_at DESC, attendance_last.id DESC
                                LIMIT 1
                     )
                 LEFT JOIN (
                         SELECT user_id,
                                        SUM(TIMESTAMPDIFF(MINUTE, clock_in_at, COALESCE(clock_out_at, CURRENT_TIMESTAMP))) AS today_minutes
                         FROM staff_attendance
                         WHERE DATE(clock_in_at) = CURDATE()
                         GROUP BY user_id
                 ) AS today_stats
                     ON today_stats.user_id = users.id
                 LEFT JOIN (
                         SELECT user_id,
                                        SUM(TIMESTAMPDIFF(MINUTE, clock_in_at, COALESCE(clock_out_at, CURRENT_TIMESTAMP))) AS week_minutes
                         FROM staff_attendance
                         WHERE YEARWEEK(clock_in_at, 1) = YEARWEEK(CURDATE(), 1)
                         GROUP BY user_id
                 ) AS week_stats
                     ON week_stats.user_id = users.id
                 ORDER BY users.full_name ASC, users.id ASC"
        );

        return $statement->fetchAll();
}

function coolopz_staff_attendance_presets(): array
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

function coolopz_staff_is_valid_work_date(string $workDate): bool
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $workDate);

    return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $workDate;
}

function coolopz_staff_add_manual_attendance(int $userId, string $workDate, string $shiftType): void
{
    coolopz_staff_save_manual_attendance(0, $userId, $workDate, $shiftType);
}

function coolopz_staff_shift_type_from_entry(string $clockInAt, string $clockOutAt): string
{
    $timeRange = date('H:i:s', strtotime($clockInAt)) . '|' . date('H:i:s', strtotime($clockOutAt));

    return match ($timeRange) {
        '09:00:00|17:00:00' => 'full_day',
        '09:00:00|13:00:00' => 'half_day_am',
        '13:00:00|17:00:00' => 'half_day_pm',
        default => 'full_day',
    };
}

function coolopz_staff_find_manual_attendance(int $attendanceId): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT staff_attendance.id,
                staff_attendance.user_id,
                staff_attendance.clock_in_at,
                staff_attendance.clock_out_at,
                staff_attendance.source,
                users.full_name,
                users.username
         FROM staff_attendance
         INNER JOIN users ON users.id = staff_attendance.user_id
         WHERE staff_attendance.id = :id
           AND staff_attendance.source = :source
         LIMIT 1'
    );
    $statement->execute([
        'id' => $attendanceId,
        'source' => 'manual',
    ]);
    $attendance = $statement->fetch();

    return $attendance === false ? null : $attendance;
}

function coolopz_staff_save_manual_attendance(int $attendanceId, int $userId, string $workDate, string $shiftType): void
{
    $presets = coolopz_staff_attendance_presets();

    if (!isset($presets[$shiftType])) {
        throw new RuntimeException('Select a valid attendance type.');
    }

    if (!coolopz_staff_is_valid_work_date($workDate)) {
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
        $attendance = coolopz_staff_find_manual_attendance($attendanceId);

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
}

function coolopz_staff_delete_manual_attendance(int $attendanceId): void
{
    $attendance = coolopz_staff_find_manual_attendance($attendanceId);

    if ($attendance === null) {
        throw new RuntimeException('The selected manual attendance record could not be found.');
    }

    $statement = coolopz_db()->prepare(
        'DELETE FROM staff_attendance WHERE id = :id AND source = :source'
    );
    $statement->execute([
        'id' => $attendanceId,
        'source' => 'manual',
    ]);
}

function coolopz_staff_fetch_manual_attendance_entries(int $limit = 20): array
{
    $limit = max(1, min($limit, 50));
    $statement = coolopz_db()->query(
    "SELECT staff_attendance.id,
        staff_attendance.user_id,
        staff_attendance.clock_in_at,
        staff_attendance.clock_out_at,
        staff_attendance.source,
        users.full_name,
        users.username,
        users.role_name
     FROM staff_attendance
     INNER JOIN users ON users.id = staff_attendance.user_id
     WHERE staff_attendance.source = 'manual'
     ORDER BY staff_attendance.clock_in_at DESC, staff_attendance.id DESC
     LIMIT " . $limit
    );

    return $statement->fetchAll();
}

coolopz_require_role(['Operations Admin']);

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Staff';
$activePage = 'staff';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

$errorMessage = '';
$allowedRoles = [
    'Service Coordinator',
    'Technician Lead',
    'Operations Admin',
];
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'created' => 'Portal account created successfully.',
    'password-reset' => 'Password reset successfully.',
    'deleted' => 'Portal account removed successfully.',
    'attendance-added' => 'Manual attendance added successfully.',
    'attendance-updated' => 'Manual attendance updated successfully.',
    'attendance-deleted' => 'Manual attendance deleted successfully.',
    default => '',
};
$formData = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role_name' => 'Service Coordinator',
];
$manualAttendanceForm = [
    'attendance_id' => 0,
    'user_id' => 0,
    'work_date' => date('Y-m-d'),
    'shift_type' => 'full_day',
];
$attendancePresets = coolopz_staff_attendance_presets();
$editingAttendanceId = isset($_GET['edit_attendance']) ? (int) $_GET['edit_attendance'] : 0;
$resetTargetId = isset($_GET['reset']) ? (int) $_GET['reset'] : 0;
$resetPasswordForm = [
    'user_id' => $resetTargetId,
    'password' => '',
    'confirm_password' => '',
];
$resetPasswordUser = $resetTargetId > 0 ? coolopz_find_staff_user($resetTargetId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        $deleteUserId = (int) ($_POST['user_id'] ?? 0);
        $deleteUser = $deleteUserId > 0 ? coolopz_find_staff_user($deleteUserId) : null;

        if ($deleteUser === null) {
            $errorMessage = 'The selected staff account could not be found.';
        } elseif (($deleteUser['email'] ?? '') === ($currentUser['email'] ?? '')) {
            $errorMessage = 'You cannot remove the account you are currently using.';
        } else {
            coolopz_delete_staff_user($deleteUserId);
            header('Location: staff.php?message=deleted');
            exit;
        }
    } elseif ($action === 'manual_attendance') {
        $manualAttendanceForm = [
            'attendance_id' => (int) ($_POST['attendance_id'] ?? 0),
            'user_id' => (int) ($_POST['attendance_user_id'] ?? 0),
            'work_date' => trim((string) ($_POST['work_date'] ?? date('Y-m-d'))),
            'shift_type' => trim((string) ($_POST['shift_type'] ?? 'full_day')),
        ];
        $attendanceUser = $manualAttendanceForm['user_id'] > 0 ? coolopz_find_staff_user($manualAttendanceForm['user_id']) : null;

        if ($attendanceUser === null) {
            $errorMessage = 'Select a valid staff account for the attendance correction.';
        } else {
            try {
                coolopz_staff_save_manual_attendance(
                    $manualAttendanceForm['attendance_id'],
                    $manualAttendanceForm['user_id'],
                    $manualAttendanceForm['work_date'],
                    $manualAttendanceForm['shift_type']
                );
                $manualAttendanceMessage = $manualAttendanceForm['attendance_id'] > 0 ? 'attendance-updated' : 'attendance-added';
                header('Location: staff.php?message=' . urlencode($manualAttendanceMessage));
                exit;
            } catch (RuntimeException $exception) {
                $errorMessage = $exception->getMessage();
            }
        }
    } elseif ($action === 'delete_attendance') {
        $attendanceId = (int) ($_POST['attendance_id'] ?? 0);

        try {
            coolopz_staff_delete_manual_attendance($attendanceId);
            header('Location: staff.php?message=attendance-deleted');
            exit;
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }
    } elseif ($action === 'reset_password') {
        $resetPasswordForm = [
            'user_id' => (int) ($_POST['user_id'] ?? 0),
            'password' => (string) ($_POST['password'] ?? ''),
            'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
        ];
        $resetPasswordUser = $resetPasswordForm['user_id'] > 0 ? coolopz_find_staff_user($resetPasswordForm['user_id']) : null;

        if ($resetPasswordUser === null) {
            $errorMessage = 'The selected staff account could not be found.';
        } elseif ($resetPasswordForm['password'] === '' || $resetPasswordForm['confirm_password'] === '') {
            $errorMessage = 'Enter and confirm the new password.';
        } elseif (strlen($resetPasswordForm['password']) < 8) {
            $errorMessage = 'Password must be at least 8 characters.';
        } elseif ($resetPasswordForm['password'] !== $resetPasswordForm['confirm_password']) {
            $errorMessage = 'The password confirmation does not match.';
        } else {
            coolopz_reset_staff_password($resetPasswordForm['user_id'], $resetPasswordForm['password']);
            header('Location: staff.php?message=password-reset');
            exit;
        }
    } else {
        $formData['username'] = strtolower(trim((string) ($_POST['username'] ?? '')));
        $formData['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
        $formData['email'] = trim((string) ($_POST['email'] ?? ''));
        $formData['role_name'] = trim((string) ($_POST['role_name'] ?? 'Service Coordinator'));
        $password = (string) ($_POST['password'] ?? '');

        if ($formData['username'] === '' || $formData['full_name'] === '' || $formData['email'] === '' || $password === '') {
            $errorMessage = 'Username, name, email, and password are required.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/', $formData['username'])) {
            $errorMessage = 'Username must be 3-50 characters using letters, numbers, dots, dashes, or underscores.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Enter a valid email address.';
        } elseif (!in_array($formData['role_name'], $allowedRoles, true)) {
            $errorMessage = 'Select a valid role for the new account.';
        } elseif (strlen($password) < 8) {
            $errorMessage = 'Password must be at least 8 characters.';
        } else {
            try {
                coolopz_create_staff_user($formData['username'], $formData['full_name'], $formData['email'], $formData['role_name'], $password);
                header('Location: staff.php?message=created');
                exit;
            } catch (PDOException $exception) {
                $errorMessage = str_contains($exception->getMessage(), 'username')
                    ? 'That username already exists.'
                    : (str_contains($exception->getMessage(), 'Duplicate')
                        ? 'That email address already exists.'
                        : 'Unable to create the staff account right now.'
                    );
            }
        }
    }
}

if ($editingAttendanceId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editingAttendance = coolopz_staff_find_manual_attendance($editingAttendanceId);

    if ($editingAttendance === null || empty($editingAttendance['clock_out_at'])) {
        $errorMessage = 'The selected manual attendance record could not be found.';
    } else {
        $manualAttendanceForm = [
            'attendance_id' => (int) $editingAttendance['id'],
            'user_id' => (int) $editingAttendance['user_id'],
            'work_date' => date('Y-m-d', strtotime((string) $editingAttendance['clock_in_at'])),
            'shift_type' => coolopz_staff_shift_type_from_entry((string) $editingAttendance['clock_in_at'], (string) $editingAttendance['clock_out_at']),
        ];
    }
}

$staffMetrics = coolopz_fetch_staff_metrics();
$staffUsers = coolopz_fetch_staff_users();
$staffAttendanceOverview = coolopz_staff_attendance_overview();
$manualAttendanceEntries = coolopz_staff_fetch_manual_attendance_entries();

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Staff</span>
                <p class="hero-copy">Create and manage portal accounts for your internal team.</p>
            </section>

<?php if ($errorMessage !== ''): ?>
            <div class="login-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
            <div class="form-success" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Total Users</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['total_users'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>All accounts with access to the portal.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Admins</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['admins'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Users with full administrative access.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Staff Accounts</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['staff'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Operational users for daily portal work.</p>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Attendance</span>
                                <h2 class="panel-title">Staff Attendance Overview</h2>
                            </div>
                            <span class="subtle-note"><?= htmlspecialchars((string) count($staffAttendanceOverview), ENT_QUOTES, 'UTF-8') ?> users tracked</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Current / Last Clock</th>
                                        <th>Today</th>
                                        <th>This Week</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($staffAttendanceOverview as $attendanceUser): ?>
<?php
    $isClockedIn = !empty($attendanceUser['active_clock_in_at']);
    $lastClockMoment = $isClockedIn
        ? (string) $attendanceUser['active_clock_in_at']
        : (string) ($attendanceUser['last_clock_out_at'] ?: $attendanceUser['last_clock_in_at']);
    $lastClockLabel = $isClockedIn
        ? 'Clocked in'
        : (!empty($attendanceUser['last_clock_out_at']) ? 'Last clock-out' : (!empty($attendanceUser['last_clock_in_at']) ? 'Last clock-in' : 'No records'));
?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($attendanceUser['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="subtle-note"><?= htmlspecialchars($attendanceUser['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($attendanceUser['role_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="status-badge <?= $isClockedIn ? 'status-progress' : 'status-queued' ?>"><?= htmlspecialchars($isClockedIn ? 'Clocked In' : 'Clocked Out', ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
<?php if ($lastClockMoment !== ''): ?>
                                            <strong><?= htmlspecialchars(date('d M Y h:i A', strtotime($lastClockMoment)), ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="subtle-note"><?= htmlspecialchars($lastClockLabel, ENT_QUOTES, 'UTF-8') ?></span>
<?php else: ?>
                                            <span class="subtle-note">No attendance records</span>
<?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(coolopz_staff_format_work_minutes((int) $attendanceUser['today_minutes']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(coolopz_staff_format_work_minutes((int) $attendanceUser['week_minutes']), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-xl-6">
                    <div class="simple-panel h-100">
                        <span class="section-label">Attendance correction</span>
                        <h2 class="panel-title">Add Manual Attendance</h2>
                        <p class="hero-copy mb-2">Use the default workday presets when staff missed their clock-in or clock-out.</p>

                        <form method="post" class="row g-2 mt-0">
                            <input type="hidden" name="action" value="manual_attendance">
                            <input type="hidden" name="attendance_id" value="<?= htmlspecialchars((string) $manualAttendanceForm['attendance_id'], ENT_QUOTES, 'UTF-8') ?>">

                            <div class="col-12">
                                <label class="form-label" for="attendance_user_id">Staff Member</label>
                                <select class="form-select" id="attendance_user_id" name="attendance_user_id" required>
                                    <option value="">Select staff member</option>
<?php foreach ($staffUsers as $user): ?>
                                    <option value="<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>"<?= $manualAttendanceForm['user_id'] === (int) $user['id'] ? ' selected' : '' ?>><?= htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                </select>
                            </div>
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
                            <div class="col-12">
                                <div class="form-text">Full day uses 9:00 AM to 5:00 PM. Half-day presets use either 9:00 AM to 1:00 PM or 1:00 PM to 5:00 PM.</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-portal-primary w-100"><?= htmlspecialchars($manualAttendanceForm['attendance_id'] > 0 ? 'Save Attendance Changes' : 'Add Attendance', ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
<?php if ($manualAttendanceForm['attendance_id'] > 0): ?>
                            <div class="col-12">
                                <a class="btn btn-portal-secondary w-100" href="staff.php">Cancel Edit</a>
                            </div>
<?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="simple-panel h-100">
                        <span class="section-label">Create user</span>
                        <h2 class="panel-title">Add Portal Account</h2>

                        <form method="post" class="row g-2 mt-0">
                            <div class="col-12">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" type="text" value="<?= htmlspecialchars($formData['username'], ENT_QUOTES, 'UTF-8') ?>" pattern="[A-Za-z0-9._-]{3,50}" required>
                                <div class="form-text">This username is used to sign in.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="full_name">Full Name</label>
                                <input class="form-control" id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="role_name">Role</label>
                                <select class="form-select" id="role_name" name="role_name">
<?php foreach ($allowedRoles as $roleName): ?>
                                    <option value="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?>"<?= $formData['role_name'] === $roleName ? ' selected' : '' ?>><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose Operations Admin to grant full administrative access.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" id="password" name="password" type="password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-portal-primary w-100">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Manual entries</span>
                                <h2 class="panel-title">Recent Manual Attendance</h2>
                            </div>
                            <span class="subtle-note"><?= htmlspecialchars((string) count($manualAttendanceEntries), ENT_QUOTES, 'UTF-8') ?> entries</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Hours</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php if ($manualAttendanceEntries === []): ?>
                                    <tr>
                                        <td colspan="5" class="subtle-note">No manual attendance entries yet.</td>
                                    </tr>
<?php else: ?>
<?php foreach ($manualAttendanceEntries as $entry): ?>
<?php
    $entryShiftType = !empty($entry['clock_out_at'])
        ? coolopz_staff_shift_type_from_entry((string) $entry['clock_in_at'], (string) $entry['clock_out_at'])
        : 'full_day';
    $entryLabel = $attendancePresets[$entryShiftType]['label'] ?? 'Manual Attendance';
    $entryMinutes = !empty($entry['clock_out_at'])
        ? (int) round((strtotime((string) $entry['clock_out_at']) - strtotime((string) $entry['clock_in_at'])) / 60)
        : 0;
?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($entry['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <span class="subtle-note"><?= htmlspecialchars($entry['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime((string) $entry['clock_in_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($entryLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(coolopz_staff_format_work_minutes($entryMinutes), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="staff-actions">
                                                <a class="btn btn-portal-secondary btn-sm" href="staff.php?edit_attendance=<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                                <form method="post" class="m-0" onsubmit="return confirm('Delete this manual attendance entry?');">
                                                    <input type="hidden" name="action" value="delete_attendance">
                                                    <input type="hidden" name="attendance_id" value="<?= htmlspecialchars((string) $entry['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
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

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Accounts</span>
                                <h2 class="panel-title">Portal Users</h2>
                            </div>
                            <span class="subtle-note"><?= htmlspecialchars((string) count($staffUsers), ENT_QUOTES, 'UTF-8') ?> listed</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($staffUsers as $user): ?>
<?php $isCurrentUser = ($user['email'] ?? '') === ($currentUser['email'] ?? ''); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-badge <?= coolopz_status_badge_class($user['role_name'] === 'Operations Admin' ? 'Priority' : 'In Progress') ?>"><?= htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="staff-actions">
                                                <a class="btn btn-portal-secondary btn-sm" href="staff.php?reset=<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>">Reset Password</a>
<?php if ($isCurrentUser): ?>
                                                <span class="subtle-note">Current account</span>
<?php else: ?>
                                                <form method="post" class="m-0" onsubmit="return confirm('Remove this staff account?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                                </form>
<?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

<?php if ($resetPasswordUser !== null): ?>
                        <div class="staff-reset-panel mt-3">
                            <div>
                                <span class="section-label">Password</span>
                                <h3 class="panel-title">Reset Password for <?= htmlspecialchars($resetPasswordUser['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="hero-copy mb-0">Set a new password for <?= htmlspecialchars($resetPasswordUser['username'], ENT_QUOTES, 'UTF-8') ?>.</p>
                            </div>

                            <form method="post" class="row g-2 mt-0">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $resetPasswordUser['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <div class="col-md-6">
                                    <label class="form-label" for="reset_password">New Password</label>
                                    <input class="form-control" id="reset_password" name="password" type="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="confirm_password">Confirm Password</label>
                                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" required>
                                </div>
                                <div class="col-12 d-flex gap-2 justify-content-end">
                                    <a class="btn btn-portal-secondary" href="staff.php">Cancel</a>
                                    <button type="submit" class="btn btn-portal-primary">Save New Password</button>
                                </div>
                            </form>
                        </div>
<?php elseif ($resetTargetId > 0): ?>
                        <div class="login-alert mt-3" role="alert">The selected staff account could not be found.</div>
<?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>