<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (coolopz_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'CoolOpz Portal | Login';
$errorMessage = '';
$email = '';
$redirectTo = coolopz_normalize_redirect($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (coolopz_login($email, $password)) {
        header('Location: ' . $redirectTo);
        exit;
    }

    $errorMessage = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-card">
            <div class="login-brand">
                <span class="brand-mark">CO</span>
                <div>
                    <strong>CoolOpz Portal</strong>
                    <span>Aircond Service Management</span>
                </div>
            </div>

            <div class="login-copy">
                <span class="section-label">Login</span>
                <h1 class="login-title">Sign in to continue</h1>
                <p>Use the admin account below to access the portal.</p>
            </div>

<?php if ($errorMessage !== ''): ?>
            <div class="login-alert" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

            <form method="post" class="row g-3">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">

                <div class="col-12">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" placeholder="admin@coolopz.local" required>
                </div>

                <div class="col-12">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" placeholder="Enter password" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-portal-primary w-100">Login</button>
                </div>
            </form>

            <div class="login-help">
                <strong>Demo account</strong>
                <span>Email: admin@coolopz.local</span>
                <span>Password: CoolOpz123!</span>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>