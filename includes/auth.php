<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('coolopz_session');
    session_start();
}

function coolopz_demo_users(): array
{
    return [
        [
            'email' => 'admin@coolopz.local',
            'name' => 'Admin User',
            'role' => 'Operations Admin',
            'password_hash' => '$2y$10$C9hFVK0LFCUv2aw9zR/ChOZRpZHcC/UmCJyFpAjIjFzSM3t.O2b82',
        ],
    ];
}

function coolopz_find_user(string $email): ?array
{
    foreach (coolopz_demo_users() as $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            return $user;
        }
    }

    return null;
}

function coolopz_is_logged_in(): bool
{
    return isset($_SESSION['coolopz_user']);
}

function coolopz_current_user(): ?array
{
    return $_SESSION['coolopz_user'] ?? null;
}

function coolopz_user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        if (strlen($initials) === 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'AU';
}

function coolopz_allowed_routes(): array
{
    return ['index.php', 'jobs.php', 'customers.php', 'reports.php'];
}

function coolopz_normalize_redirect(?string $target): string
{
    $target = basename((string) $target);

    if (in_array($target, coolopz_allowed_routes(), true)) {
        return $target;
    }

    return 'index.php';
}

function coolopz_login(string $email, string $password): bool
{
    $user = coolopz_find_user($email);

    if ($user === null || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['coolopz_user'] = [
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
    ];

    return true;
}

function coolopz_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function coolopz_require_login(): void
{
    if (coolopz_is_logged_in()) {
        return;
    }

    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
    header('Location: login.php?redirect=' . urlencode(coolopz_normalize_redirect($currentPage)));
    exit;
}