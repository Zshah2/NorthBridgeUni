<?php

declare(strict_types=1);

/**
 * Optional real file for hosts / IDEs that expect `signup.php` in `public/`.
 * Forwards to the front controller route `/signup`.
 */

$script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/signup.php'));
$dir = dirname($script);
if ($dir === '/' || $dir === '.' || $dir === '') {
    $loc = 'index.php/signup';
} else {
    $loc = $dir . '/index.php/signup';
}

header('Location: ' . $loc, true, 302);
exit;
