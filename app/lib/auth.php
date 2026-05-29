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

/** Fully authenticated portal user (password + 2FA complete). Pending 2FA does not count. */
function auth_is_portal_user(): bool
{
    auth_start_session();
    if (!isset($_SESSION['auth']) || !is_array($_SESSION['auth'])) {
        return false;
    }
    $r = auth_role();

    return $r === 'admin' || $r === 'limited' || $r === 'viewer';
}

function auth_has_pending_2fa(): bool
{
    auth_start_session();

    return isset($_SESSION['pending_2fa_email'])
        && is_string($_SESSION['pending_2fa_email'])
        && $_SESSION['pending_2fa_email'] !== ''
        && isset($_SESSION['pending_2fa_auth'])
        && is_array($_SESSION['pending_2fa_auth']);
}

function auth_clear_pending_2fa(): void
{
    auth_start_session();
    unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_auth'], $_SESSION['twofa_resend_after']);
}

/**
 * @return array{id: int, username: string, role: string, email: ?string}|null
 */
function auth_fetch_user_row(string $username): ?array
{
    $pdo = db();
    try {
        $stmt = $pdo->prepare('
          SELECT id, username, email, password_hash, role, IFNULL(is_active, 1) AS is_active
          FROM auth_users WHERE username = ? LIMIT 1
        ');
        $stmt->execute([$username]);
    } catch (Throwable) {
        $stmt = $pdo->prepare('
          SELECT id, username, password_hash, role, IFNULL(is_active, 1) AS is_active
          FROM auth_users WHERE username = ? LIMIT 1
        ');
        $stmt->execute([$username]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    return [
        'id' => (int)$row['id'],
        'username' => (string)$row['username'],
        'role' => (string)$row['role'],
        'email' => isset($row['email']) ? (trim((string)$row['email']) ?: null) : null,
        'password_hash' => (string)$row['password_hash'],
        'is_active' => (int)($row['is_active'] ?? 1),
    ];
}

/**
 * Verify portal credentials without establishing a logged-in session (for 2FA step 1).
 *
 * @return array{id: int, username: string, role: string, email: ?string}|null
 */
function auth_verify_portal_credentials(string $username, string $password): ?array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return null;
    }

    $row = auth_fetch_user_row($username);
    if ($row === null) {
        return null;
    }
    if (!password_verify($password, $row['password_hash'])) {
        return null;
    }
    if ($row['is_active'] !== 1) {
        return null;
    }
    $role = $row['role'];
    if ($role !== 'admin' && $role !== 'limited' && $role !== 'viewer') {
        return null;
    }

    return [
        'id' => $row['id'],
        'username' => $row['username'],
        'role' => $role,
        'email' => $row['email'],
    ];
}

/**
 * @param array{id: int, username: string, role: string, email?: ?string} $row
 */
function auth_begin_pending_2fa(array $row): void
{
    auth_start_session();
    auth_clear_pending_2fa();
    session_regenerate_id(true);

    $email = isset($row['email']) ? strtolower(trim((string)$row['email'])) : '';
    $_SESSION['pending_2fa_email'] = $email;
    $_SESSION['pending_2fa_auth'] = [
        'id' => (int)$row['id'],
        'username' => (string)$row['username'],
        'role' => (string)$row['role'],
    ];
}

function auth_complete_pending_2fa(): bool
{
    auth_start_session();
    if (!auth_has_pending_2fa()) {
        return false;
    }

    $pending = $_SESSION['pending_2fa_auth'];
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'id' => (int)($pending['id'] ?? 0),
        'username' => (string)($pending['username'] ?? ''),
        'role' => (string)($pending['role'] ?? ''),
    ];
    auth_clear_pending_2fa();

    return auth_is_portal_user();
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
    $row = auth_verify_portal_credentials($username, $password);
    if ($row === null) {
        return false;
    }

    auth_start_session();
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'id' => $row['id'],
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
    auth_clear_pending_2fa();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
