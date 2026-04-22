<?php

declare(strict_types=1);

/**
 * Full web path to index.php (e.g. /CollegWeb/public/index.php) for link generation.
 */
function app_front_controller(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!is_string($script) || !str_ends_with($script, '/index.php')) {
        $cached = '';

        return $cached;
    }

    $cached = str_replace('\\', '/', $script);

    return $cached;
}

/**
 * JetBrains / PhpStorm built-in server (port 63342) does not rewrite /public/login → index.php.
 * In that case links must be /public/index.php/login. Opt in/out with APP_USE_INDEX_PHP_LINKS (1/0/true/false).
 */
function app_use_index_php_in_links(): bool
{
    $env = getenv('APP_USE_INDEX_PHP_LINKS');
    if ($env === '0' || strtolower((string)$env) === 'false' || strtolower((string)$env) === 'off') {
        return false;
    }
    if ($env === '1' || strtolower((string)$env) === 'true' || strtolower((string)$env) === 'on') {
        return true;
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');

    return str_contains($host, ':63342');
}

/**
 * URL prefix when the app is not served at the web server root (derived from SCRIPT_NAME …/index.php).
 * Override with env APP_BASE_PATH (no trailing slash), e.g. APP_BASE_PATH=/CollegWeb/public
 */
function app_base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $env = getenv('APP_BASE_PATH');
    if (is_string($env) && $env !== '') {
        $cached = '/' . trim($env, '/');

        return $cached;
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!is_string($script) || $script === '' || $script === '/index.php') {
        $cached = '';

        return $cached;
    }

    if (str_ends_with($script, '/index.php')) {
        $dir = substr($script, 0, -strlen('/index.php'));
        $cached = $dir === '/' ? '' : rtrim($dir, '/');

        return $cached;
    }

    $cached = '';

    return $cached;
}

/**
 * Absolute app URL path (leading /). Optional ?query only. Pass-through for http(s):// URLs.
 */
function url(string $path): string
{
    if ($path !== '' && preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return $path;
    }

    $query = '';
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }

    $p = '/' . ltrim($path, '/');
    if (app_use_index_php_in_links()) {
        $fc = app_front_controller();
        if ($fc !== '') {
            return $fc . $p . $query;
        }
    }

    $base = app_base_path();

    return ($base === '' ? '' : $base) . $p . $query;
}

/** For nav/config links: leave #anchors unchanged; prefix absolute app paths. */
function nav_url(string $href): string
{
    $href = trim($href);
    if ($href === '' || $href[0] === '#') {
        return $href;
    }
    if ($href[0] === '/') {
        return url($href);
    }

    return $href;
}
