<?php
require_once __DIR__ . '/includes/auth.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
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
                                    <tr>
                                        <td>#COP-2048</td>
                                        <td>Northpoint Suites</td>
                                        <td>Team Alpha</td>
                                        <td>KL Central</td>
                                        <td><span class="status-badge status-urgent">Urgent</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2045</td>
                                        <td>Pelita Food Hall</td>
                                        <td>Team Delta</td>
                                        <td>Shah Alam</td>
                                        <td><span class="status-badge status-progress">In Progress</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2042</td>
                                        <td>Riverview Co-Working</td>
                                        <td>Team Sigma</td>
                                        <td>Putrajaya</td>
                                        <td><span class="status-badge status-queued">Queued</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2039</td>
                                        <td>Harbor Dental Clinic</td>
                                        <td>Team Nova</td>
                                        <td>Ampang</td>
                                        <td><span class="status-badge status-complete">Completed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="info-list mt-3">
                            <div class="info-row">
                                <div>
                                    <strong>08:30</strong>
                                    <p>Warehouse release for compressor replacements.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>11:00</strong>
                                    <p>Commercial maintenance visit at Meridian Office Park.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>14:00</strong>
                                    <p>New installation handover at Riverview Co-Working.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>17:30</strong>
                                    <p>Close completed jobs and sync service photos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>