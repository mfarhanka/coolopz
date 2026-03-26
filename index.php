<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
$metrics = coolopz_fetch_dashboard_metrics();
$priorityJobs = coolopz_fetch_priority_jobs();
$pageTitle = 'CoolOpz Portal | Dashboard';
$activePage = 'dashboard';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Dashboard</span>
                <p class="hero-copy">Keep the dashboard focused on the essentials: daily job count, team availability, and the current priority list.</p>
            </section>

            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Active Jobs</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $metrics['active_jobs'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Current jobs being handled today.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Available Technicians</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $metrics['available_technicians'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Ready for maintenance and emergency calls.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Completed Today</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $metrics['completed_today'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Jobs closed and ready for invoicing.</p>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Priority queue</span>
                                <h2 class="panel-title">Current Service Jobs</h2>
                            </div>
                            <a class="btn btn-portal-secondary btn-sm" href="jobs.php">All Jobs</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Client</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($priorityJobs as $job): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($job['ticket_number'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($job['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($job['service_type'], ENT_QUOTES, 'UTF-8') ?></td>
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