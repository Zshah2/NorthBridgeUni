<?php

declare(strict_types=1);

/**
 * Normalized request script path (e.g. /CollegWeb/public/login.php).
 */
function app_script_name(): string
{
    return str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
}

/**
 * Path to the front controller (index.php) for the app, even when the current
 * request is login.php, admin.php, etc. (same directory as index.php).
 */
function app_front_controller(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $script = app_script_name();
    if ($script === '') {
        $cached = '';

        return $cached;
    }

    if (str_ends_with($script, '/index.php')) {
        $cached = $script;

        return $cached;
    }

    $dir = dirname($script);
    if ($dir === '/' || $dir === '.') {
        $cached = '/index.php';
    } else {
        $cached = $dir . '/index.php';
    }

    return $cached;
}

/**
 * JetBrains / PhpStorm built-in server (port 63342) does not rewrite /public/login → index.php.
 * In that case links use /public/index.php/... . Opt in/out with APP_USE_INDEX_PHP_LINKS (1/0/true/false).
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
 * URL prefix when the app is not served at the web root (same folder as index.php).
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

    $script = app_script_name();
    if ($script === '' || $script === '/index.php') {
        $cached = '';

        return $cached;
    }

    if (str_ends_with($script, '/index.php')) {
        $base = substr($script, 0, -strlen('/index.php'));
    } else {
        $base = dirname($script);
    }

    $base = rtrim($base, '/');
    if ($base === '' || $base === '/') {
        $cached = '';
    } else {
        $cached = $base;
    }

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
            // Home: avoid /index.php/ or /index.php/index.php
            if ($p === '/' || $p === '/index.php') {
                return $fc . $query;
            }

            return $fc . $p . $query;
        }
    }

    $base = app_base_path();

    if ($p === '/index.php') {
        return ($base === '' ? '' : $base) . '/index.php' . $query;
    }

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
