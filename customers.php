<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
$customerMetrics = coolopz_fetch_customer_metrics();
$customers = coolopz_fetch_customers();
$pageTitle = 'CoolOpz Portal | Customers';
$activePage = 'customers';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Customers</span>
                <p class="hero-copy">Use this page for the active customer list and simple renewal tracking.</p>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Commercial Clients</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['commercial'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Businesses with recurring service plans.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Residential Accounts</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['residential'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Homeowners and property management accounts.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Renewals Pending</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['renewals_pending'], ENT_QUOTES, 'UTF-8') ?></strong>
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
                            <span class="subtle-note"><?= htmlspecialchars((string) $customerMetrics['total'], ENT_QUOTES, 'UTF-8') ?> total accounts</span>
                        </div>

                        <div class="card-stack">
<?php foreach ($customers as $customer): ?>
                            <div class="stack-card">
                                <div>
                                    <strong><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <p><?= htmlspecialchars($customer['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <span class="status-badge <?= coolopz_status_badge_class($customer['renewal_status']) ?>"><?= htmlspecialchars($customer['renewal_status'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>