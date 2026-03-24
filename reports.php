<?php
$pageTitle = 'CoolOpz Portal | Reports';
$activePage = 'reports';
$sidebarLabel = 'Reports';
$sidebarMetric = '96.4% satisfaction';
$sidebarText = 'Review performance, revenue, response time, and service quality trends.';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <div class="row align-items-center g-4">
                    <div class="col-xl-8">
                        <span class="section-label">Reports</span>
                        <h1 class="hero-title">Track operational performance and revenue at a glance.</h1>
                        <p class="hero-copy">This reporting page provides monthly KPIs, service mix, and outcome highlights for management review.</p>
                    </div>
                    <div class="col-xl-4">
                        <div class="simple-panel hero-note">
                            <span class="section-label">This month</span>
                            <ul class="snapshot-list">
                                <li>RM 84,200 billed</li>
                                <li>132 jobs completed</li>
                                <li>28-minute avg. response time</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Monthly Revenue</span>
                        <strong class="stat-value">RM84k</strong>
                        <p>Total invoiced service value for the current month.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Completion Rate</span>
                        <strong class="stat-value">92%</strong>
                        <p>Jobs closed within the planned service window.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Customer Rating</span>
                        <strong class="stat-value">4.8</strong>
                        <p>Average post-service rating across all recent jobs.</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mt-1">
                <div class="col-xl-7">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-3">
                            <div>
                                <span class="section-label">Performance mix</span>
                                <h2 class="panel-title">Service Category Breakdown</h2>
                            </div>
                            <span class="subtle-note">March 2026</span>
                        </div>

                        <div class="chart-stack">
                            <div class="chart-row">
                                <div class="chart-meta">
                                    <strong>Preventive Maintenance</strong>
                                    <span>46%</span>
                                </div>
                                <div class="chart-bar"><span style="width: 46%"></span></div>
                            </div>
                            <div class="chart-row">
                                <div class="chart-meta">
                                    <strong>Repairs</strong>
                                    <span>31%</span>
                                </div>
                                <div class="chart-bar"><span style="width: 31%"></span></div>
                            </div>
                            <div class="chart-row">
                                <div class="chart-meta">
                                    <strong>Installations</strong>
                                    <span>23%</span>
                                </div>
                                <div class="chart-bar"><span style="width: 23%"></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="simple-panel h-100">
                        <span class="section-label">Highlights</span>
                        <h2 class="panel-title">Management Notes</h2>
                        <div class="info-list mt-3">
                            <div class="info-row">
                                <div>
                                    <strong>Fastest zone</strong>
                                    <p>Shah Alam maintained the best response time this month.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Top revenue client</strong>
                                    <p>Meridian Office Park contributed the highest contract value.</p>
                                </div>
                            </div>
                            <div class="info-row">
                                <div>
                                    <strong>Improvement area</strong>
                                    <p>Parts-related delays remain the main cause of missed targets.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>