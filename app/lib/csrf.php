<?php

declare(strict_types=1);

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
        echo 'Session expired or invalid form token. Please refresh the page and try again.';
        exit;
    }
}
