<?php

declare(strict_types=1);

/**
 * Optional real file for hosts / IDEs that expect `login.php` in `public/`.
 * The app routes `/login` through `index.php`; this script forwards there.
 */

$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/login.php'));
$dir = dirname($script);
if ($dir === '/' || $dir === '.' || $dir === '') {
    $loc = 'index.php/login';
} else {
    $loc = $dir . '/index.php/login';
}

header('Location: ' . $loc, true, 302);
exit;
