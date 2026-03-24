<?php
$pageTitle = 'CoolOpz Portal | Reports';
$activePage = 'reports';
$currentUserName = 'Admin User';
$currentUserRole = 'Operations Admin';
$userInitials = 'AU';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Reports</span>
                <p class="hero-copy">Keep reporting simple with the main KPIs and a single service mix summary.</p>
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
                <div class="col-12">
                    <div class="simple-panel">
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
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>