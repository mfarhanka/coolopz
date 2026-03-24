<?php
$pageTitle = 'CoolOpz Portal | Dashboard';
$activePage = 'dashboard';
$sidebarLabel = 'Today';
$sidebarMetric = '24 active jobs';
$sidebarText = 'Separate pages for dashboard, operations, customers, and reporting.';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <div class="row align-items-center g-4">
                    <div class="col-xl-8">
                        <span class="section-label">Dashboard</span>
                        <h1 class="hero-title">A clear daily view of aircond operations.</h1>
                        <p class="hero-copy">The dashboard now acts as the home page for CoolOpz Portal, while jobs, customers, and reports live on their own screens.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a class="btn btn-portal-primary" href="jobs.php">View Jobs</a>
                            <a class="btn btn-portal-secondary" href="customers.php">Open Customers</a>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="simple-panel hero-note">
                            <span class="section-label">Operations snapshot</span>
                            <ul class="snapshot-list">
                                <li>7 technicians on-site</li>
                                <li>3 urgent repairs waiting</li>
                                <li>18 jobs completed today</li>
                            </ul>
                        </div>
                    </div>
                </div>
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
                <div class="col-xl-7">
                    <div class="simple-panel h-100">
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

                <div class="col-xl-5">
                    <div class="simple-panel h-100">
                        <span class="section-label">Quick insights</span>
                        <h2 class="panel-title">Today&apos;s Highlights</h2>
                        <div class="info-list mt-3">
                            <div class="info-row">
                                <div>
                                    <strong>Response time</strong>
                                    <p>Average dispatch in 28 minutes across all zones.</p>
                                </div>
                                <span class="status-badge status-progress">Stable</span>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Pending invoices</strong>
                                    <p>6 completed jobs still waiting for billing release.</p>
                                </div>
                                <span class="status-badge status-queued">Review</span>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Contract renewals</strong>
                                    <p>3 customers need follow-up before the end of the week.</p>
                                </div>
                                <span class="status-badge status-urgent">Action</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>