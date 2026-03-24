<?php
$pageTitle = 'CoolOpz Portal | Jobs';
$activePage = 'jobs';
$sidebarLabel = 'Jobs';
$sidebarMetric = '6 teams dispatched';
$sidebarText = 'Track urgent repairs, installations, and scheduled maintenance from one page.';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <div class="row align-items-center g-4">
                    <div class="col-xl-8">
                        <span class="section-label">Jobs</span>
                        <h1 class="hero-title">Manage service jobs by status, team, and priority.</h1>
                        <p class="hero-copy">This page focuses on daily execution with active tickets, assigned crews, and a short schedule view for dispatch coordination.</p>
                    </div>
                    <div class="col-xl-4">
                        <div class="simple-panel hero-note">
                            <span class="section-label">Dispatch window</span>
                            <ul class="snapshot-list">
                                <li>4 urgent tickets</li>
                                <li>12 maintenance visits</li>
                                <li>8 installation tasks</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Open Tickets</span>
                        <strong class="stat-value">29</strong>
                        <p>All service jobs not yet closed.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">On Site</span>
                        <strong class="stat-value">14</strong>
                        <p>Technicians currently working at client locations.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Awaiting Parts</span>
                        <strong class="stat-value">5</strong>
                        <p>Jobs paused until replacement parts arrive.</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mt-1">
                <div class="col-xl-8">
                    <div class="simple-panel h-100">
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
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="simple-panel h-100">
                        <span class="section-label">Schedule</span>
                        <h2 class="panel-title">Dispatch Timeline</h2>
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