<?php
$pageTitle = 'CoolOpz Portal | Dashboard';
$activePage = 'dashboard';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <span class="section-label">Dashboard</span>
                <h1 class="hero-title">A clear daily view of aircond operations.</h1>
                <p class="hero-copy">Keep the dashboard focused on the essentials: daily job count, team availability, and the current priority list.</p>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Active Jobs</span>
                        <strong class="stat-value">24</strong>
                        <p>Current jobs being handled today.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Available Technicians</span>
                        <strong class="stat-value">12</strong>
                        <p>Ready for maintenance and emergency calls.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Completed Today</span>
                        <strong class="stat-value">18</strong>
                        <p>Jobs closed and ready for invoicing.</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mt-1">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-3">
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
                                    <tr>
                                        <td>#COP-2041</td>
                                        <td>Skyline Residence</td>
                                        <td>Repair</td>
                                        <td><span class="status-badge status-urgent">Urgent</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2038</td>
                                        <td>Pelita Food Hall</td>
                                        <td>Maintenance</td>
                                        <td><span class="status-badge status-progress">In Progress</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2035</td>
                                        <td>Riverview Co-Working</td>
                                        <td>Installation</td>
                                        <td><span class="status-badge status-queued">Queued</span></td>
                                    </tr>
                                    <tr>
                                        <td>#COP-2031</td>
                                        <td>Harbor Dental Clinic</td>
                                        <td>Gas Top-Up</td>
                                        <td><span class="status-badge status-complete">Completed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>