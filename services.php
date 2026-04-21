<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_role(['Operations Admin']);

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Services';
$activePage = 'services';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

$errorMessage = '';
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'created' => 'Service created successfully.',
    'updated' => 'Service updated successfully.',
    'deleted' => 'Service deleted successfully.',
    default => '',
};
$shouldOpenServiceModal = false;

$serviceForm = [
    'name' => '',
    'default_price' => '',
    'notes' => '',
];

$editServiceId = isset($_GET['edit']) ? (int) ($_GET['edit'] ?? 0) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        $deleteServiceId = (int) ($_POST['service_id'] ?? 0);

        if ($deleteServiceId < 1 || coolopz_find_service($deleteServiceId) === null) {
            $errorMessage = 'The selected service could not be found.';
        } else {
            coolopz_delete_service($deleteServiceId);
            header('Location: services.php?message=deleted');
            exit;
        }
    } else {
        $editServiceId = $action === 'update' ? (int) ($_POST['service_id'] ?? 0) : 0;
        $serviceForm = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'default_price' => trim((string) ($_POST['default_price'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($serviceForm['name'] === '') {
            $errorMessage = 'Service name is required.';
            $shouldOpenServiceModal = true;
        } elseif ($serviceForm['default_price'] !== '' && (!is_numeric($serviceForm['default_price']) || (float) $serviceForm['default_price'] < 0)) {
            $errorMessage = 'Default price must be a valid positive number.';
            $shouldOpenServiceModal = true;
        } elseif ($action === 'update' && ($editServiceId < 1 || coolopz_find_service($editServiceId) === null)) {
            $errorMessage = 'The service you are trying to update no longer exists.';
            $shouldOpenServiceModal = true;
        } else {
            $servicePayload = [
                'name' => $serviceForm['name'],
                'default_price' => number_format((float) ($serviceForm['default_price'] !== '' ? $serviceForm['default_price'] : '0'), 2, '.', ''),
                'notes' => $serviceForm['notes'],
            ];

            try {
                if ($action === 'update') {
                    coolopz_update_service($editServiceId, $servicePayload);
                    header('Location: services.php?message=updated');
                    exit;
                }

                coolopz_create_service($servicePayload);
                header('Location: services.php?message=created');
                exit;
            } catch (PDOException $exception) {
                $errorMessage = str_contains($exception->getMessage(), 'Duplicate')
                    ? 'That service already exists.'
                    : 'Unable to save the service right now.';
                $shouldOpenServiceModal = true;
            }
        }
    }
}

if ($editServiceId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editingService = coolopz_find_service($editServiceId);

    if ($editingService === null) {
        $errorMessage = 'The selected service could not be found.';
        $editServiceId = 0;
    } else {
        $shouldOpenServiceModal = true;
        $serviceForm = [
            'name' => $editingService['name'],
            'default_price' => (float) $editingService['default_price'] > 0 ? number_format((float) $editingService['default_price'], 2, '.', '') : '',
            'notes' => $editingService['notes'],
        ];
    }
}

$serviceMetrics = coolopz_fetch_service_metrics();
$services = coolopz_fetch_services();

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Services</span>
                <p class="hero-copy">Manage the billable service list used by the operations team.</p>
            </section>

            <section class="row g-2 g-lg-3">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Total Services</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $serviceMetrics['total'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>All service items currently available.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">With Default Price</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $serviceMetrics['priced'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Services with a standard charge ready to use.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Average Price</span>
                        <strong class="stat-value">RM<?= htmlspecialchars(number_format((float) $serviceMetrics['average_price'], 2), ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Average default price across the service list.</p>
                    </div>
                </div>
            </section>

            <section class="row g-3 mt-0">
                <div class="col-12">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-2">
                            <div>
                                <span class="section-label">Service catalog</span>
                                <h2 class="panel-title">Manage Services</h2>
                            </div>
                            <div class="jobs-actions">
                                <span class="subtle-note"><?= htmlspecialchars((string) count($services), ENT_QUOTES, 'UTF-8') ?> listed</span>
                                <button type="button" class="btn btn-portal-primary btn-sm" data-bs-toggle="modal" data-bs-target="#serviceModal">Add Service</button>
                            </div>
                        </div>

<?php if ($errorMessage !== ''): ?>
                        <div class="login-alert mb-2" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
                        <div class="form-success mb-2" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Default Price</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((float) $service['default_price'] > 0 ? 'RM' . number_format((float) $service['default_price'], 2) : '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($service['notes'] !== '' ? $service['notes'] : 'No notes added.', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="jobs-actions">
                                                <a class="btn btn-portal-secondary btn-sm" href="services.php?edit=<?= htmlspecialchars((string) $service['id'], ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                                                <form method="post" class="m-0" onsubmit="return confirm('Delete this service?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="service_id" value="<?= htmlspecialchars((string) $service['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
<?php endforeach; ?>
<?php if ($services === []): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No services available yet.</td>
                                    </tr>
<?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <span class="section-label mb-2"><?= $editServiceId > 0 ? 'Edit service' : 'Create service' ?></span>
                                <h2 class="panel-title" id="serviceModalLabel"><?= $editServiceId > 0 ? 'Update Service' : 'Add Service' ?></h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
<?php if ($errorMessage !== ''): ?>
                            <div class="login-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                            <form method="post" class="row g-2">
                                <input type="hidden" name="action" value="<?= $editServiceId > 0 ? 'update' : 'create' ?>">
<?php if ($editServiceId > 0): ?>
                                <input type="hidden" name="service_id" value="<?= htmlspecialchars((string) $editServiceId, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
                                <div class="col-md-6">
                                    <label class="form-label" for="name">Service Name</label>
                                    <input class="form-control" id="name" name="name" type="text" value="<?= htmlspecialchars($serviceForm['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="default_price">Default Price</label>
                                    <input class="form-control" id="default_price" name="default_price" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) $serviceForm['default_price'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes</label>
                                    <textarea class="form-control notes-field" id="notes" name="notes" rows="4"><?= htmlspecialchars($serviceForm['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div class="col-12 jobs-form-actions">
                                    <button type="submit" class="btn btn-portal-primary"><?= $editServiceId > 0 ? 'Save Changes' : 'Create Service' ?></button>
                                    <a class="btn btn-portal-secondary" href="services.php">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
<?php if ($shouldOpenServiceModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('serviceModal');
    if (!modalElement) {
        return;
    }

    var serviceModal = new bootstrap.Modal(modalElement);
    serviceModal.show();
});
</script>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>