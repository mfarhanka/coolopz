<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
$jobMetrics = coolopz_fetch_job_metrics();
$jobs = coolopz_fetch_jobs();
$pageTitle = 'CoolOpz Portal | Jobs';
$activePage = 'jobs';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Jobs</span>
                <p class="hero-copy">Keep this page focused on ticket flow, technician assignment, and the jobs that still need attention.</p>
            </section>
            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Open Tickets</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $jobMetrics['open_tickets'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>All service jobs not yet closed.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">On Site</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $jobMetrics['on_site'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Technicians currently working at client locations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Awaiting Parts</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $jobMetrics['awaiting_parts'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Jobs paused until replacement parts arrive.</p>
                    </div>
                </div>
            </section>
            <section class="row g-4 mt-1">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-3">
                            <div>
                                <span class="section-label">Service board</span>
                                <h2 class="panel-title">Active Job List</h2>
                            </div>
                            <span class="subtle-note">Updated 10:24 AM</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Client</th>
                                        <th>Technician</th>
                                        <th>Zone</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($job['ticket_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($job['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($job['technician_team'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($job['zone'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-badge <?= coolopz_status_badge_class($job['status']) ?>"><?= htmlspecialchars($job['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>