<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/seed_admin.php <username> <password>\n");
    exit(1);
}

$username = trim((string)$argv[1]);
$password = (string)$argv[2];

if ($username === '' || $password === '') {
    fwrite(STDERR, "Username/password cannot be empty.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Failed to hash password.\n");
    exit(1);
}

$pdo = db();
$stmt = $pdo->prepare('
  INSERT INTO auth_users (username, password_hash, role)
  VALUES (?, ?, "admin")
  ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)
');
$stmt->execute([$username, $hash]);

fwrite(STDOUT, "Seeded admin user: $username\n");

