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
$allowedRoles = [
    'Service Coordinator',
    'Technician Lead',
    'Operations Admin',
];
$messageKey = (string) ($_GET['message'] ?? '');
$successMessage = match ($messageKey) {
    'created' => 'Portal account created successfully.',
    'password-reset' => 'Password reset successfully.',
    'deleted' => 'Portal account removed successfully.',
    default => '',
};
$formData = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role_name' => 'Service Coordinator',
];
$resetTargetId = isset($_GET['reset']) ? (int) $_GET['reset'] : 0;
$resetPasswordForm = [
    'user_id' => $resetTargetId,
    'password' => '',
    'confirm_password' => '',
];
$resetPasswordUser = $resetTargetId > 0 ? coolopz_find_staff_user($resetTargetId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        $deleteUserId = (int) ($_POST['user_id'] ?? 0);
        $deleteUser = $deleteUserId > 0 ? coolopz_find_staff_user($deleteUserId) : null;

        if ($deleteUser === null) {
            $errorMessage = 'The selected staff account could not be found.';
        } elseif (($deleteUser['email'] ?? '') === ($currentUser['email'] ?? '')) {
            $errorMessage = 'You cannot remove the account you are currently using.';
        } else {
            coolopz_delete_staff_user($deleteUserId);
            header('Location: staff.php?message=deleted');
            exit;
        }
    } elseif ($action === 'reset_password') {
        $resetPasswordForm = [
            'user_id' => (int) ($_POST['user_id'] ?? 0),
            'password' => (string) ($_POST['password'] ?? ''),
            'confirm_password' => (string) ($_POST['confirm_password'] ?? ''),
        ];
        $resetPasswordUser = $resetPasswordForm['user_id'] > 0 ? coolopz_find_staff_user($resetPasswordForm['user_id']) : null;

        if ($resetPasswordUser === null) {
            $errorMessage = 'The selected staff account could not be found.';
        } elseif ($resetPasswordForm['password'] === '' || $resetPasswordForm['confirm_password'] === '') {
            $errorMessage = 'Enter and confirm the new password.';
        } elseif (strlen($resetPasswordForm['password']) < 8) {
            $errorMessage = 'Password must be at least 8 characters.';
        } elseif ($resetPasswordForm['password'] !== $resetPasswordForm['confirm_password']) {
            $errorMessage = 'The password confirmation does not match.';
        } else {
            coolopz_reset_staff_password($resetPasswordForm['user_id'], $resetPasswordForm['password']);
            header('Location: staff.php?message=password-reset');
            exit;
        }
    } else {
        $formData['username'] = strtolower(trim((string) ($_POST['username'] ?? '')));
        $formData['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
        $formData['email'] = trim((string) ($_POST['email'] ?? ''));
        $formData['role_name'] = trim((string) ($_POST['role_name'] ?? 'Service Coordinator'));
        $password = (string) ($_POST['password'] ?? '');

        if ($formData['username'] === '' || $formData['full_name'] === '' || $formData['email'] === '' || $password === '') {
            $errorMessage = 'Username, name, email, and password are required.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/', $formData['username'])) {
            $errorMessage = 'Username must be 3-50 characters using letters, numbers, dots, dashes, or underscores.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Enter a valid email address.';
        } elseif (!in_array($formData['role_name'], $allowedRoles, true)) {
            $errorMessage = 'Select a valid role for the new account.';
        } elseif (strlen($password) < 8) {
            $errorMessage = 'Password must be at least 8 characters.';
        } else {
            try {
                coolopz_create_staff_user($formData['username'], $formData['full_name'], $formData['email'], $formData['role_name'], $password);
                header('Location: staff.php?message=created');
                exit;
            } catch (PDOException $exception) {
                $errorMessage = str_contains($exception->getMessage(), 'username')
                    ? 'That username already exists.'
                    : (str_contains($exception->getMessage(), 'Duplicate')
                        ? 'That email address already exists.'
                        : 'Unable to create the staff account right now.'
                    );
            }
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

            <section class="row g-2 g-lg-3">
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

            <section class="row g-3 mt-0">
                <div class="col-xl-5">
                    <div class="simple-panel h-100">
                        <span class="section-label">Create user</span>
                        <h2 class="panel-title">Add Portal Account</h2>

<?php if ($errorMessage !== ''): ?>
                        <div class="login-alert mt-2" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
                        <div class="form-success mt-2" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

                        <form method="post" class="row g-2 mt-0">
                            <div class="col-12">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" type="text" value="<?= htmlspecialchars($formData['username'], ENT_QUOTES, 'UTF-8') ?>" pattern="[A-Za-z0-9._-]{3,50}" required>
                                <div class="form-text">This username is used to sign in.</div>
                            </div>
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
<?php foreach ($allowedRoles as $roleName): ?>
                                    <option value="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?>"<?= $formData['role_name'] === $roleName ? ' selected' : '' ?>><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                                </select>
                                <div class="form-text">Choose Operations Admin to grant full administrative access.</div>
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
                        <div class="panel-head mb-2">
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
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($staffUsers as $user): ?>
<?php $isCurrentUser = ($user['email'] ?? '') === ($currentUser['email'] ?? ''); ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-badge <?= coolopz_status_badge_class($user['role_name'] === 'Operations Admin' ? 'Priority' : 'In Progress') ?>"><?= htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><?= htmlspecialchars(date('d M Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <div class="staff-actions">
                                                <a class="btn btn-portal-secondary btn-sm" href="staff.php?reset=<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>">Reset Password</a>
<?php if ($isCurrentUser): ?>
                                                <span class="subtle-note">Current account</span>
<?php else: ?>
                                                <form method="post" class="m-0" onsubmit="return confirm('Remove this staff account?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                                </form>
<?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

<?php if ($resetPasswordUser !== null): ?>
                        <div class="staff-reset-panel mt-3">
                            <div>
                                <span class="section-label">Password</span>
                                <h3 class="panel-title">Reset Password for <?= htmlspecialchars($resetPasswordUser['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p class="hero-copy mb-0">Set a new password for <?= htmlspecialchars($resetPasswordUser['username'], ENT_QUOTES, 'UTF-8') ?>.</p>
                            </div>

                            <form method="post" class="row g-2 mt-0">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $resetPasswordUser['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <div class="col-md-6">
                                    <label class="form-label" for="reset_password">New Password</label>
                                    <input class="form-control" id="reset_password" name="password" type="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="confirm_password">Confirm Password</label>
                                    <input class="form-control" id="confirm_password" name="confirm_password" type="password" required>
                                </div>
                                <div class="col-12 d-flex gap-2 justify-content-end">
                                    <a class="btn btn-portal-secondary" href="staff.php">Cancel</a>
                                    <button type="submit" class="btn btn-portal-primary">Save New Password</button>
                                </div>
                            </form>
                        </div>
<?php elseif ($resetTargetId > 0): ?>
                        <div class="login-alert mt-3" role="alert">The selected staff account could not be found.</div>
<?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
<?php include __DIR__ . '/includes/footer.php'; ?>