<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_login();

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Customers';
$activePage = 'customers';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

$customerTypes = coolopz_customer_types();
$renewalStatuses = coolopz_customer_renewal_statuses();

$errorMessage = '';
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'created' => 'Customer created successfully.',
    'updated' => 'Customer updated successfully.',
    'deleted' => 'Customer deleted successfully.',
    default => '',
};
$shouldOpenCustomerModal = false;

$customerForm = [
    'name' => '',
    'customer_type' => 'Commercial',
    'notes' => '',
    'renewal_status' => 'Contract Active',
    'rating' => '5.0',
];

$editCustomerId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        $deleteCustomerId = (int) ($_POST['customer_id'] ?? 0);

        if ($deleteCustomerId < 1 || coolopz_find_customer($deleteCustomerId) === null) {
            $errorMessage = 'The selected customer could not be found.';
        } else {
            coolopz_delete_customer($deleteCustomerId);
            header('Location: customers.php?message=deleted');
            exit;
        }
    } else {
        $editCustomerId = $action === 'update' ? (int) ($_POST['customer_id'] ?? 0) : 0;
        $customerForm = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'customer_type' => trim((string) ($_POST['customer_type'] ?? 'Commercial')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'renewal_status' => trim((string) ($_POST['renewal_status'] ?? 'Contract Active')),
            'rating' => trim((string) ($_POST['rating'] ?? '5.0')),
        ];

        if ($customerForm['name'] === '' || $customerForm['notes'] === '') {
            $errorMessage = 'Customer name and notes are required.';
            $shouldOpenCustomerModal = true;
        } elseif (!in_array($customerForm['customer_type'], $customerTypes, true)) {
            $errorMessage = 'Select a valid customer type.';
            $shouldOpenCustomerModal = true;
        } elseif (!in_array($customerForm['renewal_status'], $renewalStatuses, true)) {
            $errorMessage = 'Select a valid renewal status.';
            $shouldOpenCustomerModal = true;
        } elseif (!is_numeric($customerForm['rating']) || (float) $customerForm['rating'] < 0 || (float) $customerForm['rating'] > 5) {
            $errorMessage = 'Rating must be between 0 and 5.';
            $shouldOpenCustomerModal = true;
        } elseif ($action === 'update' && ($editCustomerId < 1 || coolopz_find_customer($editCustomerId) === null)) {
            $errorMessage = 'The customer you are trying to update no longer exists.';
            $shouldOpenCustomerModal = true;
        } else {
            $customerPayload = $customerForm;
            $customerPayload['rating'] = number_format((float) $customerForm['rating'], 1, '.', '');

            try {
                if ($action === 'update') {
                    coolopz_update_customer($editCustomerId, $customerPayload);
                    header('Location: customers.php?message=updated');
                    exit;
                }

                coolopz_create_customer($customerPayload);
                header('Location: customers.php?message=created');
                exit;
            } catch (PDOException $exception) {
                $errorMessage = str_contains($exception->getMessage(), 'Duplicate')
                    ? 'That customer already exists.'
                    : 'Unable to save the customer right now.';
                $shouldOpenCustomerModal = true;
            }
        }
    }
}

if ($editCustomerId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editingCustomer = coolopz_find_customer($editCustomerId);

    if ($editingCustomer === null) {
        $errorMessage = 'The selected customer could not be found.';
        $editCustomerId = 0;
    } else {
        $shouldOpenCustomerModal = true;
        $customerForm = [
            'name' => $editingCustomer['name'],
            'customer_type' => $editingCustomer['customer_type'],
            'notes' => $editingCustomer['notes'],
            'renewal_status' => $editingCustomer['renewal_status'],
            'rating' => number_format((float) ($editingCustomer['rating'] ?? 0), 1, '.', ''),
        ];
    }
}

$customerMetrics = coolopz_fetch_customer_metrics();
$customers = coolopz_fetch_customers();

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Customers</span>
                <p class="hero-copy">Use this page for the active customer list and simple renewal tracking.</p>
            </section>

            <section class="row g-2 g-lg-3">
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

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Accounts</span>
                                <h2 class="panel-title">Customer Portfolio</h2>
                            </div>
                            <div class="jobs-actions">
                                <span class="subtle-note"><?= htmlspecialchars((string) $customerMetrics['total'], ENT_QUOTES, 'UTF-8') ?> total accounts</span>
                                <button type="button" class="btn btn-portal-primary btn-sm" data-bs-toggle="modal" data-bs-target="#customerModal">Add Customer</button>
                            </div>
                        </div>

<?php if ($errorMessage !== ''): ?>
                        <div class="login-alert mb-2" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
                        <div class="form-success mb-2" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                        <div class="card-stack">
<?php foreach ($customers as $customer): ?>
                            <div class="stack-card">
                                <div>
                                    <strong><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <p><?= htmlspecialchars($customer['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="subtle-note d-block mt-1"><?= htmlspecialchars($customer['customer_type'], ENT_QUOTES, 'UTF-8') ?> • Rating <?= htmlspecialchars(number_format((float) ($customer['rating'] ?? 0), 1), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="jobs-actions justify-content-end">
                                    <span class="status-badge <?= coolopz_status_badge_class($customer['renewal_status']) ?>"><?= htmlspecialchars($customer['renewal_status'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <a class="btn btn-portal-secondary btn-sm" href="customers.php?edit=<?= htmlspecialchars((string) $customer['id'], ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                    <form method="post" class="m-0" onsubmit="return confirm('Delete this customer?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $customer['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <span class="section-label mb-2"><?= $editCustomerId > 0 ? 'Edit customer' : 'Create customer' ?></span>
                                <h2 class="panel-title" id="customerModalLabel"><?= $editCustomerId > 0 ? 'Update Customer' : 'Add Customer' ?></h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
<?php if ($errorMessage !== ''): ?>
                            <div class="login-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="<?= $editCustomerId > 0 ? 'update' : 'create' ?>">
<?php if ($editCustomerId > 0): ?>
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $editCustomerId, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Customer Name</label>
                                    <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($customerForm['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="customer_type">Customer Type</label>
                                    <select class="form-select" id="customer_type" name="customer_type">
<?php foreach ($customerTypes as $customerType): ?>
                                        <option value="<?= htmlspecialchars($customerType, ENT_QUOTES, 'UTF-8') ?>"<?= $customerForm['customer_type'] === $customerType ? ' selected' : '' ?>><?= htmlspecialchars($customerType, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="renewal_status">Renewal Status</label>
                                    <select class="form-select" id="renewal_status" name="renewal_status">
<?php foreach ($renewalStatuses as $renewalStatus): ?>
                                        <option value="<?= htmlspecialchars($renewalStatus, ENT_QUOTES, 'UTF-8') ?>"<?= $customerForm['renewal_status'] === $renewalStatus ? ' selected' : '' ?>><?= htmlspecialchars($renewalStatus, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="rating">Rating</label>
                                    <input class="form-control" id="rating" name="rating" type="number" min="0" max="5" step="0.1" value="<?= htmlspecialchars((string) $customerForm['rating'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control notes-field" id="notes" name="notes" rows="4" required><?= htmlspecialchars($customerForm['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="col-12 jobs-form-actions">
                                    <button type="submit" class="btn btn-portal-primary"><?= $editCustomerId > 0 ? 'Save Changes' : 'Create Customer' ?></button>
                                    <a class="btn btn-portal-secondary" href="customers.php">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
<?php if ($shouldOpenCustomerModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('customerModal');
    if (!modalElement) {
        return;
    }

    var customerModal = new bootstrap.Modal(modalElement);
    customerModal.show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>