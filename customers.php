<?php
$pageTitle = 'CoolOpz Portal | Customers';
$activePage = 'customers';

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
            <section class="hero-section">
                <span class="section-label">Customers</span>
                <h1 class="hero-title">Keep customer accounts and contracts organized.</h1>
                <p class="hero-copy">Use this page for the active customer list and simple renewal tracking.</p>
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
                <div class="col-12">
                    <div class="simple-panel">
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
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>