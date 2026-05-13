<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

function coolopz_format_work_minutes(int $minutes): string
{
    $minutes = max(0, $minutes);
    $hours = intdiv($minutes, 60);
    $remainingMinutes = $minutes % 60;

    if ($hours > 0) {
        return sprintf('%dh %02dm', $hours, $remainingMinutes);
    }

    return sprintf('%dm', $remainingMinutes);
}

coolopz_require_login();

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Clock In/Out';
$activePage = 'clock';
$currentUserName = $currentUser['name'] ?? 'Staff User';
$currentUserRole = $currentUser['role'] ?? 'Team Member';
$userInitials = coolopz_user_initials($currentUserName);
$currentUserId = (int) ($currentUser['id'] ?? 0);
$errorMessage = '';
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'clocked-in' => 'Clock-in recorded successfully.',
    'clocked-out' => 'Clock-out recorded successfully.',
    default => '',
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($currentUserId <= 0) {
        $errorMessage = 'Your account session is missing staff details. Please sign in again.';
    } else {
        try {
            if ($action === 'clock_in') {
                coolopz_clock_in_staff($currentUserId);
                header('Location: clock.php?message=clocked-in');
                exit;
            }

            if ($action === 'clock_out') {
                coolopz_clock_out_staff($currentUserId);
                header('Location: clock.php?message=clocked-out');
                exit;
            }

            $errorMessage = 'The requested attendance action is not valid.';
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }
    }
}

$clockSummary = $currentUserId > 0
    ? coolopz_fetch_staff_clock_summary($currentUserId)
    : [
        'open_entry' => null,
        'last_entry' => null,
        'today_minutes' => 0,
        'week_minutes' => 0,
    ];
$clockHistory = $currentUserId > 0 ? coolopz_fetch_staff_clock_history($currentUserId) : [];
$openEntry = $clockSummary['open_entry'];
$lastEntry = $clockSummary['last_entry'];
$clockedInAt = $openEntry['clock_in_at'] ?? null;

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Attendance</span>
                <p class="hero-copy">Track staff working time with a simple personal clock-in and clock-out log.</p>
            </section>

            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Current Status</span>
                        <strong class="stat-value"><?= htmlspecialchars($openEntry !== null ? 'In' : 'Out', ENT_QUOTES, 'UTF-8') ?></strong>
                        <p><?= htmlspecialchars($openEntry !== null ? 'You are currently on the clock.' : 'You are currently clocked out.', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Hours Today</span>
                        <strong class="stat-value"><?= htmlspecialchars(coolopz_format_work_minutes((int) $clockSummary['today_minutes']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Total tracked time for today.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Hours This Week</span>
                        <strong class="stat-value"><?= htmlspecialchars(coolopz_format_work_minutes((int) $clockSummary['week_minutes']), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Tracked time from the current work week.</p>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-lg-5">
                    <div class="simple-panel h-100 clock-action-panel">
                        <div>
                            <span class="section-label">Action</span>
                            <h2 class="panel-title">Staff Clock</h2>
                        </div>

<?php if ($errorMessage !== ''): ?>
                        <div class="login-alert mb-0" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
                        <div class="form-success mb-0" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                        <div class="clock-status-stack">
                            <span class="status-badge <?= $openEntry !== null ? 'status-progress' : 'status-queued' ?>"><?= htmlspecialchars($openEntry !== null ? 'Clocked In' : 'Clocked Out', ENT_QUOTES, 'UTF-8') ?></span>
<?php if ($clockedInAt !== null): ?>
                            <strong class="clock-time-mark"><?= htmlspecialchars(date('h:i A', strtotime((string) $clockedInAt)), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="subtle-note">Clocked in on <?= htmlspecialchars(date('d M Y', strtotime((string) $clockedInAt)), ENT_QUOTES, 'UTF-8') ?></span>
<?php elseif ($lastEntry !== null && !empty($lastEntry['clock_out_at'])): ?>
                            <strong class="clock-time-mark"><?= htmlspecialchars(date('h:i A', strtotime((string) $lastEntry['clock_out_at'])), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="subtle-note">Last clock-out on <?= htmlspecialchars(date('d M Y', strtotime((string) $lastEntry['clock_out_at'])), ENT_QUOTES, 'UTF-8') ?></span>
<?php else: ?>
                            <strong class="clock-time-mark">--</strong>
                            <span class="subtle-note">No attendance records yet.</span>
<?php endif; ?>
                        </div>

                        <form method="post" class="d-grid gap-2">
<?php if ($openEntry !== null): ?>
                            <input type="hidden" name="action" value="clock_out">
                            <button type="submit" class="btn btn-outline-danger">Clock Out</button>
<?php else: ?>
                            <input type="hidden" name="action" value="clock_in">
                            <button type="submit" class="btn btn-portal-primary">Clock In</button>
<?php endif; ?>
                        </form>

                        <p class="hero-copy mb-0">Use this page at the start and end of each shift so your working hours stay accurate.</p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Recent log</span>
                                <h2 class="panel-title">Attendance History</h2>
                            </div>
                            <span class="subtle-note"><?= htmlspecialchars((string) count($clockHistory), ENT_QUOTES, 'UTF-8') ?> entries</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php if ($clockHistory === []): ?>
                                    <tr>
                                        <td colspan="5" class="subtle-note">No clock entries yet.</td>
                                    </tr>
<?php else: ?>
<?php foreach ($clockHistory as $entry): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime((string) $entry['clock_in_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(date('h:i A', strtotime((string) $entry['clock_in_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(!empty($entry['clock_out_at']) ? date('h:i A', strtotime((string) $entry['clock_out_at'])) : 'Still active', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars(coolopz_format_work_minutes((int) $entry['worked_minutes']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
<?php if (!empty($entry['clock_out_at'])): ?>
                                            <span class="status-badge status-complete">Closed</span>
<?php else: ?>
                                            <span class="clock-history-note">Open shift</span>
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
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>