<?php

declare(strict_types=1);

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
