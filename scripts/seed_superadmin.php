<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/auth.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/seed_superadmin.php <email> <password> [username]\n");
    fwrite(STDERR, "  Legacy: php scripts/seed_superadmin.php <username> <password> <email>\n");
    exit(1);
}

$arg1 = trim((string)$argv[1]);
$password = (string)$argv[2];

if (str_contains($arg1, '@')) {
    $email = auth_normalize_email($arg1);
    $username = isset($argv[3]) ? trim((string)$argv[3]) : auth_username_from_email($email);
} else {
    $username = $arg1;
    $email = isset($argv[3]) ? auth_normalize_email((string)$argv[3]) : '';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "A valid email is required (use email as the first argument, or pass email as the third argument).\n");
    exit(1);
}
if ($username === '') {
    fwrite(STDERR, "Username is required.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Failed to hash password.\n");
    exit(1);
}

$pdo = db();
try {
    $pdo->prepare('
      INSERT INTO auth_users (username, email, password_hash, role)
      VALUES (?, ?, ?, "admin")
      ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), email = VALUES(email)
    ')->execute([$username, $email, $hash]);
} catch (Throwable $e) {
    if (str_contains($e->getMessage(), 'email')) {
        fwrite(STDERR, "auth_users.email missing — run: php scripts/migrate.php\n");
        exit(1);
    }
    throw $e;
}

fwrite(STDOUT, "Seeded admin: sign in with email {$email} (username: {$username})\n");
