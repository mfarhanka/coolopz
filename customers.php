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

$errorMessage = '';
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'created' => 'Customer created successfully.',
    'updated' => 'Customer updated successfully.',
    'deleted' => 'Customer deleted successfully.',
    default => '',
};
$shouldOpenCustomerModal = false;
$customerPhoneChecked = false;
$customerPhoneCheckMessage = '';

$customerForm = [
    'name' => '',
    'phone_number' => '',
    'email' => '',
    'notes' => '',
];

$editCustomerId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');
    $customerPhoneChecked = $action === 'update' || ((string) ($_POST['phone_checked'] ?? '0')) === '1';

    if ($action === 'delete') {
        $deleteCustomerId = (int) ($_POST['customer_id'] ?? 0);

        if ($deleteCustomerId < 1 || coolopz_find_customer($deleteCustomerId) === null) {
            $errorMessage = 'The selected customer could not be found.';
        } else {
            coolopz_delete_customer($deleteCustomerId);
            header('Location: customers.php?message=deleted');
            exit;
        }
    } elseif ($action === 'check_phone') {
        $customerForm['phone_number'] = coolopz_normalize_customer_phone_number((string) ($_POST['phone_number'] ?? ''));

        if ($customerForm['phone_number'] === '') {
            $errorMessage = 'Phone number is required before adding the customer details.';
        } elseif (!coolopz_is_valid_customer_phone_number($customerForm['phone_number'])) {
            $errorMessage = 'Phone number must use the 60123456789 format.';
        } else {
            $existingCustomer = coolopz_find_customer_by_phone_number($customerForm['phone_number']);

            if ($existingCustomer !== null) {
                $errorMessage = 'That phone number is already used by ' . $existingCustomer['name'] . '.';
            } else {
                $customerPhoneChecked = true;
                $customerPhoneCheckMessage = 'Phone number is available. Continue with the customer details.';
            }
        }

        $shouldOpenCustomerModal = true;
    } else {
        $editCustomerId = $action === 'update' ? (int) ($_POST['customer_id'] ?? 0) : 0;
        $customerForm = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'phone_number' => coolopz_normalize_customer_phone_number((string) ($_POST['phone_number'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
        $duplicateCustomer = coolopz_find_customer_by_phone_number(
            $customerForm['phone_number'],
            $action === 'update' ? $editCustomerId : null
        );

        if ($action !== 'update' && !$customerPhoneChecked) {
            $errorMessage = 'Check the phone number before adding the customer details.';
            $shouldOpenCustomerModal = true;
        } elseif ($customerForm['name'] === '' || $customerForm['phone_number'] === '') {
            $errorMessage = 'Customer name and phone number are required.';
            $shouldOpenCustomerModal = true;
        } elseif (!coolopz_is_valid_customer_phone_number($customerForm['phone_number'])) {
            $errorMessage = 'Phone number must use the 60123456789 format.';
            $shouldOpenCustomerModal = true;
        } elseif ($duplicateCustomer !== null) {
            $errorMessage = 'That phone number is already used by ' . $duplicateCustomer['name'] . '.';
            $shouldOpenCustomerModal = true;
        } elseif ($customerForm['email'] !== '' && filter_var($customerForm['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errorMessage = 'Enter a valid email address or leave it blank.';
            $shouldOpenCustomerModal = true;
        } elseif ($action === 'update' && ($editCustomerId < 1 || coolopz_find_customer($editCustomerId) === null)) {
            $errorMessage = 'The customer you are trying to update no longer exists.';
            $shouldOpenCustomerModal = true;
        } else {
            $customerPayload = $customerForm;
            $customerPayload['email'] = $customerForm['email'] === '' ? null : $customerForm['email'];

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
        $customerPhoneChecked = true;
        $customerForm = [
            'name' => $editingCustomer['name'],
            'phone_number' => $editingCustomer['phone_number'] ?? '',
            'email' => $editingCustomer['email'] ?? '',
            'notes' => $editingCustomer['notes'],
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
                <p class="hero-copy">Use this page for the active customer list and core contact details.</p>
            </section>

            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Total Accounts</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['total'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Customers currently tracked in the portal.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">With Phone No</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['with_phone_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Accounts with a recorded primary contact number.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">With Email</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $customerMetrics['with_email'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Email is optional, but tracked when available.</p>
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
                                    <span class="subtle-note d-block mt-1">Phone: <?= htmlspecialchars($customer['phone_number'] !== '' ? $customer['phone_number'] : '-', ENT_QUOTES, 'UTF-8') ?></span>
<?php if (($customer['email'] ?? null) !== null && trim((string) $customer['email']) !== ''): ?>
                                    <span class="subtle-note d-block">Email: <?= htmlspecialchars((string) $customer['email'], ENT_QUOTES, 'UTF-8') ?></span>
<?php endif; ?>
                                    <p><?= htmlspecialchars($customer['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="jobs-actions justify-content-end">
                                    <a class="btn btn-outline-primary btn-sm" href="jobs.php?customer_id=<?= htmlspecialchars((string) $customer['id'], ENT_QUOTES, 'UTF-8') ?>">Create Job</a>
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

<?php if ($customerPhoneCheckMessage !== ''): ?>
                            <div class="form-success" role="status"><?= htmlspecialchars($customerPhoneCheckMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($editCustomerId === 0 && !$customerPhoneChecked): ?>
                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="check_phone">
                                <div class="col-md-8">
                                    <label class="form-label" for="phone_number_check">Phone No</label>
                                    <input class="form-control" id="phone_number_check" name="phone_number" type="text" inputmode="numeric" pattern="60[0-9]{9,10}" value="<?= htmlspecialchars($customerForm['phone_number'], ENT_QUOTES, 'UTF-8') ?>" placeholder="60123456789" required>
                                    <div class="form-text">Enter the phone number first. The system will save it as 60123456789 format.</div>
                                    <div class="form-text" id="phone_number_check_normalized">Saved as: <?= htmlspecialchars($customerForm['phone_number'] !== '' ? $customerForm['phone_number'] : '-', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="col-12 jobs-form-actions">
                                    <button type="submit" class="btn btn-portal-primary">Check Phone No</button>
                                    <a class="btn btn-portal-secondary" href="customers.php">Cancel</a>
                                </div>
                            </form>
<?php else: ?>
                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="<?= $editCustomerId > 0 ? 'update' : 'create' ?>">
<?php if ($editCustomerId > 0): ?>
                                <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $editCustomerId, ENT_QUOTES, 'UTF-8') ?>">
<?php else: ?>
                                <input type="hidden" name="phone_checked" value="1">
<?php endif; ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone_number">Phone No</label>
                                    <input class="form-control" id="phone_number" name="phone_number" type="text" inputmode="numeric" pattern="60[0-9]{9,10}" value="<?= htmlspecialchars($customerForm['phone_number'], ENT_QUOTES, 'UTF-8') ?>" placeholder="60123456789"<?= $editCustomerId === 0 ? ' readonly' : '' ?> required>
<?php if ($editCustomerId === 0): ?>
                                    <div class="form-text">Phone number already checked. Use Cancel to restart with a different number.</div>
<?php else: ?>
                                    <div class="form-text">The system stores phone numbers as 60123456789 format.</div>
<?php endif; ?>
                                    <div class="form-text" id="phone_number_normalized">Saved as: <?= htmlspecialchars($customerForm['phone_number'] !== '' ? $customerForm['phone_number'] : '-', ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Customer Name</label>
                                    <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($customerForm['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars((string) $customerForm['email'], ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control notes-field" id="notes" name="notes" rows="4"><?= htmlspecialchars($customerForm['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="col-12 jobs-form-actions">
                                    <button type="submit" class="btn btn-portal-primary"><?= $editCustomerId > 0 ? 'Save Changes' : 'Create Customer' ?></button>
                                    <a class="btn btn-portal-secondary" href="customers.php">Cancel</a>
                                </div>
                            </form>
<?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    ['phone_number_check', 'phone_number'].forEach(function (inputId) {
        var input = document.getElementById(inputId);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        var preview = document.getElementById(inputId + '_normalized');

        var normalizePhoneNumber = function () {
            input.value = input.value.replace(/\D+/g, '');

            if (preview instanceof HTMLElement) {
                preview.textContent = 'Saved as: ' + (input.value !== '' ? input.value : '-');
            }
        };

        input.addEventListener('input', normalizePhoneNumber);
        input.addEventListener('paste', function () {
            window.setTimeout(normalizePhoneNumber, 0);
        });

        normalizePhoneNumber();
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>