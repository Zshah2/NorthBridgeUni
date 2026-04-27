<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

$username = 'staff';
$password = 'Staff@1234';
$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Failed to hash password.\n");
    exit(1);
}

$pdo = db();
$pdo->prepare('
  INSERT INTO auth_users (username, password_hash, role)
  VALUES (?, ?, "viewer")
  ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)
')->execute([$username, $hash]);

fwrite(STDOUT, "Viewer login: username={$username} password={$password}\n");
