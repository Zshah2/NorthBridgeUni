<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/url.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/auth.php';
require __DIR__ . '/../app/lib/csrf.php';

auth_start_session();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_require_valid();
}

auth_logout();

header('Location: ' . url('/login.php'), true, 302);
exit;
