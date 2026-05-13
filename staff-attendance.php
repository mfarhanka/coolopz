<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

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
$selectedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$selectedStaffUser = $selectedUserId > 0 ? coolopz_find_staff_user($selectedUserId) : null;
$errorMessage = '';

if ($selectedStaffUser === null) {
    $errorMessage = 'The selected staff account could not be found.';
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
                                    </tr>
                                </thead>
                                <tbody>
<?php if ($attendanceEntries === []): ?>
                                    <tr>
                                        <td colspan="6" class="subtle-note">No attendance entries found for this staff member.</td>
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
                                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
<?php endif; ?>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>