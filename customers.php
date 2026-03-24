<?php
$pageTitle = 'CoolOpz Portal | Customers';
$activePage = 'customers';
$sidebarLabel = 'Customers';
$sidebarMetric = '68 active accounts';
$sidebarText = 'Track contract renewals, service history, and client priorities.';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <div class="row align-items-center g-4">
                    <div class="col-xl-8">
                        <span class="section-label">Customers</span>
                        <h1 class="hero-title">Keep customer accounts, contracts, and follow-ups organized.</h1>
                        <p class="hero-copy">This page groups active accounts, recent touchpoints, and renewal opportunities so service and sales teams can act quickly.</p>
                    </div>
                    <div class="col-xl-4">
                        <div class="simple-panel hero-note">
                            <span class="section-label">Account health</span>
                            <ul class="snapshot-list">
                                <li>11 annual contracts</li>
                                <li>6 renewals due this month</li>
                                <li>4 clients need callback today</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Commercial Clients</span>
                        <strong class="stat-value">28</strong>
                        <p>Businesses with recurring service plans.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Residential Accounts</span>
                        <strong class="stat-value">40</strong>
                        <p>Homeowners and property management accounts.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Renewals Pending</span>
                        <strong class="stat-value">6</strong>
                        <p>Contracts that need follow-up this month.</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mt-1">
                <div class="col-xl-7">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-3">
                            <div>
                                <span class="section-label">Accounts</span>
                                <h2 class="panel-title">Customer Portfolio</h2>
                            </div>
                            <span class="subtle-note">68 total accounts</span>
                        </div>

                        <div class="card-stack">
                            <div class="stack-card">
                                <div>
                                    <strong>Meridian Office Park</strong>
                                    <p>Quarterly preventive maintenance for 32 indoor units.</p>
                                </div>
                                <span class="status-badge status-progress">Contract Active</span>
                            </div>
                            <div class="stack-card">
                                <div>
                                    <strong>Bloom Pediatric Center</strong>
                                    <p>High-priority service account with same-day response terms.</p>
                                </div>
                                <span class="status-badge status-urgent">Priority</span>
                            </div>
                            <div class="stack-card">
                                <div>
                                    <strong>Casa Bayu Residence</strong>
                                    <p>Renewal proposal prepared for multi-unit maintenance package.</p>
                                </div>
                                <span class="status-badge status-queued">Renewal Due</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="simple-panel h-100">
                        <span class="section-label">Follow-up list</span>
                        <h2 class="panel-title">Recent Customer Actions</h2>
                        <div class="info-list mt-3">
                            <div class="info-row">
                                <div>
                                    <strong>Northpoint Suites</strong>
                                    <p>Send maintenance summary and invoice pack.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Pelita Food Hall</strong>
                                    <p>Confirm next preventive maintenance slot for kitchen zone.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Harbor Dental Clinic</strong>
                                    <p>Collect feedback after completed gas top-up service.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>