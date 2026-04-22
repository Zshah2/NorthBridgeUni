<?php

declare(strict_types=1);

require_once __DIR__ . '/url.php';

function csrf_token(): string
{
    auth_start_session();
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_validate(): bool
{
    auth_start_session();
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['_csrf'] ?? '';

    return is_string($sent) && is_string($expected) && $expected !== '' && hash_equals($expected, $sent);
}

function csrf_require_valid(): void
{
    if (!csrf_validate()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        $msg = 'Your session expired or the form token was invalid. Go back, refresh the page, and try again.';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/><title>Session expired</title></head>';
        echo '<body style="margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;line-height:1.5">';
        echo '<div style="max-width:28rem;margin:4rem auto;padding:0 1.5rem">';
        echo '<h1 style="font-size:1.125rem;font-weight:600;margin:0 0 0.5rem">Could not verify form</h1>';
        echo '<p style="margin:0 0 1.25rem;font-size:0.9375rem;color:#94a3b8">' . htmlspecialchars($msg) . '</p>';
        $login = htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8');
        echo '<a href="' . $login . '" style="display:inline-block;font-size:0.875rem;font-weight:600;color:#0ea5e9;text-decoration:none">Return to login</a>';
        echo '</div></body></html>';
        exit;
    }
}
