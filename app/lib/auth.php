<?php

declare(strict_types=1);

/**
 * Portal roles (`auth_users.role`):
 * - admin: full access — registration add/drop, holds, future grade writes.
 * - limited: registration + holds; cannot perform blocked write actions (e.g. grade import).
 * - viewer: read-only — no mutations via admin.php or legacy /admin/holds/* POST routes.
 */

require_once __DIR__ . '/url.php';

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function auth_role(): ?string
{
    auth_start_session();
    $r = $_SESSION['auth']['role'] ?? null;

    return is_string($r) && $r !== '' ? $r : null;
}

function auth_is_admin(): bool
{
    return auth_role() === 'admin';
}

function auth_is_limited(): bool
{
    return auth_role() === 'limited';
}

function auth_is_viewer(): bool
{
    return auth_role() === 'viewer';
}

/** Any logged-in staff portal role */
function auth_is_portal_user(): bool
{
    $r = auth_role();

    return $r === 'admin' || $r === 'limited' || $r === 'viewer';
}

/** Add/clear student holds (admin.php + legacy holds routes) */
function auth_can_manage_holds(): bool
{
    return auth_is_admin() || auth_is_limited();
}

/** Add/drop enrollments in admin registration UI */
function auth_can_manage_registration(): bool
{
    return auth_is_admin() || auth_is_limited();
}

function auth_require_admin(): void
{
    if (!auth_is_admin()) {
        header('Location: ' . url('/login.php'));
        exit;
    }
}

function auth_require_portal_user(): void
{
    if (!auth_is_portal_user()) {
        header('Location: ' . url('/login.php'));
        exit;
    }
}

function auth_require_hold_manager(): void
{
    auth_require_portal_user();
    if (!auth_can_manage_holds()) {
        header('Location: ' . url('/admin.php?view=dashboard&msg=forbidden'));
        exit;
    }
}

function auth_login(string $username, string $password): bool
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

function auth_login_portal_user(string $username, string $password): bool
{
    if (!auth_login($username, $password)) {
        return false;
    }

    return auth_is_portal_user();
}

function auth_login_admin(string $username, string $password): bool
{
    if (!auth_login($username, $password)) {
        return false;
    }

    return auth_is_admin();
}

/**
 * Creates an admin auth user.
 *
 * @return array{0:bool,1:?string}
 */
function auth_create_admin(string $username, string $password): array
{
    return auth_create_user_with_role($username, $password, 'admin');
}

/**
 * @return array{0:bool,1:?string}
 */
function auth_create_user_with_role(string $username, string $password, string $role): array
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
    if (!in_array($role, ['admin', 'limited', 'viewer'], true)) {
        return [false, 'Invalid role.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        return [false, 'Failed to hash password.'];
    }

    $pdo = db();
    try {
        $stmt = $pdo->prepare('INSERT INTO auth_users (username, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hash, $role]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' || (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062)) {
            return [false, 'That username is already taken.'];
        }

        return [false, 'Failed to create user.'];
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
