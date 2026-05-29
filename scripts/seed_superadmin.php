<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/seed_superadmin.php <username> <password> [email]\n");
    exit(1);
}

$username = trim((string)$argv[1]);
$password = (string)$argv[2];
$email = isset($argv[3]) ? strtolower(trim((string)$argv[3])) : '';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email address.\n");
    exit(1);
}
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Failed to hash password.\n");
    exit(1);
}

$pdo = db();
try {
    if ($email !== '') {
        $pdo->prepare('
          INSERT INTO auth_users (username, email, password_hash, role)
          VALUES (?, ?, ?, "admin")
          ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), email = VALUES(email)
        ')->execute([$username, $email, $hash]);
    } else {
        $pdo->prepare('
          INSERT INTO auth_users (username, password_hash, role)
          VALUES (?, ?, "admin")
          ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)
        ')->execute([$username, $hash]);
    }
} catch (Throwable $e) {
    if ($email !== '' && str_contains($e->getMessage(), 'email')) {
        fwrite(STDERR, "auth_users.email missing — run: php scripts/migrate.php\n");
        exit(1);
    }
    throw $e;
}

$msg = "Seeded admin user: {$username}";
if ($email !== '') {
    $msg .= " (email: {$email})";
}
fwrite(STDOUT, $msg . "\n");
