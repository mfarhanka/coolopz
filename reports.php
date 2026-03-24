<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
$reportMetrics = coolopz_fetch_report_metrics();
$breakdown = coolopz_fetch_service_breakdown();
$pageTitle = 'CoolOpz Portal | Reports';
$activePage = 'reports';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

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
                        <strong class="stat-value">RM<?= htmlspecialchars(number_format((float) $reportMetrics['monthly_revenue'], 0), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Total invoiced service value for the current month.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Completion Rate</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $reportMetrics['completion_rate'], ENT_QUOTES, 'UTF-8') ?>%</strong>
                        <p>Jobs closed within the planned service window.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Customer Rating</span>
                        <strong class="stat-value"><?= htmlspecialchars(number_format((float) $reportMetrics['customer_rating'], 1), ENT_QUOTES, 'UTF-8') ?></strong>
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
<?php foreach ($breakdown as $item): ?>
                            <div class="chart-row">
                                <div class="chart-meta">
                                    <strong><?= htmlspecialchars($item['service_type'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span><?= htmlspecialchars((string) $item['percentage'], ENT_QUOTES, 'UTF-8') ?>%</span>
                                </div>
                                <div class="chart-bar"><span style="width: <?= htmlspecialchars((string) $item['percentage'], ENT_QUOTES, 'UTF-8') ?>%"></span></div>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>