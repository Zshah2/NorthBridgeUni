<?php

declare(strict_types=1);

require_once __DIR__ . '/url.php';

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_is_admin(): bool
{
    auth_start_session();
    return isset($_SESSION['auth']) && is_array($_SESSION['auth']) && ($_SESSION['auth']['role'] ?? null) === 'admin';
}

function auth_require_admin(): void
{
    if (!auth_is_admin()) {
        header('Location: ' . url('/login'));
        exit;
    }
}

function auth_login_admin(string $username, string $password): bool
{
    auth_start_session();

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM auth_users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    if (!password_verify($password, $row['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'role' => $row['role'],
    ];

    return true;
}

/**
 * Creates an admin auth user.
 * Returns [ok, errorMessage].
 *
 * @return array{0:bool,1:?string}
 */
function auth_create_admin(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '') {
        return [false, 'Username is required.'];
    }
    if (strlen($username) > 100) {
        return [false, 'Username is too long.'];
    }
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        return [false, 'Failed to hash password.'];
    }

    $pdo = db();
    try {
        $stmt = $pdo->prepare('INSERT INTO auth_users (username, password_hash, role) VALUES (?, ?, "admin")');
        $stmt->execute([$username, $hash]);
    } catch (PDOException $e) {
        // 23000 is integrity constraint violation (duplicate username)
        if ($e->getCode() === '23000') {
            return [false, 'That username is already taken.'];
        }
        return [false, 'Failed to create admin user.'];
    }

    return [true, null];
}

function auth_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

