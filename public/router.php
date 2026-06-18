<?php

declare(strict_types=1);

/**
 * Router for PHP’s built-in server so /login and /admin/… reach index.php
 * while real files (e.g. /assets/css/app.css) are still served as static files.
 *
 * From project root:
 *   php -S localhost:8000 -t public public/router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = ($uri === false || $uri === null || $uri === '') ? '/' : urldecode($uri);
$uri = str_replace('\\', '/', $uri);

if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

require __DIR__ . '/index.php';
