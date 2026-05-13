<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('coolopz_session');
    session_start();
}

function coolopz_find_user(string $username): ?array
{
    $statement = coolopz_db()->prepare(
        'SELECT id, username, email, full_name, role_name, password_hash FROM users WHERE username = :username LIMIT 1'
    );
    $statement->execute(['username' => strtolower(trim($username))]);
    $user = $statement->fetch();

    if ($user === false) {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'name' => $user['full_name'],
        'role' => $user['role_name'],
        'password_hash' => $user['password_hash'],
    ];
}

function coolopz_is_logged_in(): bool
{
    return isset($_SESSION['coolopz_user']);
}

function coolopz_current_user(): ?array
{
    if (isset($_SESSION['coolopz_user']) && !isset($_SESSION['coolopz_user']['id']) && isset($_SESSION['coolopz_user']['username'])) {
        $user = coolopz_find_user((string) $_SESSION['coolopz_user']['username']);

        if ($user !== null) {
            $_SESSION['coolopz_user']['id'] = $user['id'];
        }
    }

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
    return ['index.php', 'jobs.php', 'customers.php', 'services.php', 'reports.php', 'staff.php', 'clock.php'];
}

function coolopz_normalize_redirect(?string $target): string
{
    $target = basename((string) $target);

    if (in_array($target, coolopz_allowed_routes(), true)) {
        return $target;
    }

    return 'index.php';
}

function coolopz_login(string $username, string $password): bool
{
    $user = coolopz_find_user($username);

    if ($user === null || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['coolopz_user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
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

function coolopz_require_role(array $roles): void
{
    coolopz_require_login();

    $currentUser = coolopz_current_user();
    $currentRole = $currentUser['role'] ?? '';

    if (in_array($currentRole, $roles, true)) {
        return;
    }

    http_response_code(403);
    echo 'Forbidden';
    exit;
}