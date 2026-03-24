<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

coolopz_require_role(['Operations Admin']);

$currentUser = coolopz_current_user();
$pageTitle = 'CoolOpz Portal | Staff';
$activePage = 'staff';
$currentUserName = $currentUser['name'] ?? 'Admin User';
$currentUserRole = $currentUser['role'] ?? 'Operations Admin';
$userInitials = coolopz_user_initials($currentUserName);

$errorMessage = '';
$successMessage = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'role_name' => 'Service Coordinator',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $formData['email'] = trim((string) ($_POST['email'] ?? ''));
    $formData['role_name'] = trim((string) ($_POST['role_name'] ?? 'Service Coordinator'));
    $password = (string) ($_POST['password'] ?? '');

    if ($formData['full_name'] === '' || $formData['email'] === '' || $password === '') {
        $errorMessage = 'Name, email, and password are required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters.';
    } else {
        try {
            coolopz_create_staff_user($formData['full_name'], $formData['email'], $formData['role_name'], $password);
            $successMessage = 'Staff account created successfully.';
            $formData = [
                'full_name' => '',
                'email' => '',
                'role_name' => 'Service Coordinator',
            ];
        } catch (PDOException $exception) {
            $errorMessage = str_contains($exception->getMessage(), 'Duplicate')
                ? 'That email address already exists.'
                : 'Unable to create the staff account right now.';
        }
    }
}

$staffMetrics = coolopz_fetch_staff_metrics();
$staffUsers = coolopz_fetch_staff_users();

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/sidebar.php';
?>
        <main class="portal-main">
<?php include __DIR__ . '/includes/topbar.php'; ?>
            <section class="hero-section">
                <span class="section-label">Staff</span>
                <p class="hero-copy">Create and manage portal accounts for your internal team.</p>
            </section>

            <section class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Total Users</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['total_users'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>All accounts with access to the portal.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Admins</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['admins'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Users with full administrative access.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="simple-panel stat-card">
                        <span class="stat-label">Staff Accounts</span>
                        <strong class="stat-value"><?= htmlspecialchars((string) $staffMetrics['staff'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <p>Operational users for daily portal work.</p>
                    </div>
                </div>
            </section>

            <section class="row g-4 mt-1">
                <div class="col-xl-5">
                    <div class="simple-panel h-100">
                        <span class="section-label">Create user</span>
                        <h2 class="panel-title">Add Staff Account</h2>

<?php if ($errorMessage !== ''): ?>
                        <div class="login-alert mt-3" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
                        <div class="form-success mt-3" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                        <form method="post" class="row g-3 mt-1">
                            <div class="col-12">
                                <label class="form-label" for="full_name">Full Name</label>
                                <input class="form-control" id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Email</label>
                                <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="role_name">Role</label>
                                <select class="form-select" id="role_name" name="role_name">
                                    <option value="Service Coordinator"<?= $formData['role_name'] === 'Service Coordinator' ? ' selected' : '' ?>>Service Coordinator</option>
                                    <option value="Technician Lead"<?= $formData['role_name'] === 'Technician Lead' ? ' selected' : '' ?>>Technician Lead</option>
                                    <option value="Operations Admin"<?= $formData['role_name'] === 'Operations Admin' ? ' selected' : '' ?>>Operations Admin</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control" id="password" name="password" type="password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-portal-primary w-100">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="simple-panel h-100">
                        <div class="panel-head mb-3">
                            <div>
                                <span class="section-label">Accounts</span>
                                <h2 class="panel-title">Portal Users</h2>
                            </div>
                            <span class="subtle-note"><?= htmlspecialchars((string) count($staffUsers), ENT_QUOTES, 'UTF-8') ?> listed</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table portal-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($staffUsers as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-badge <?= coolopz_status_badge_class($user['role_name'] === 'Operations Admin' ? 'Priority' : 'In Progress') ?>"><?= htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>