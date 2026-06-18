<?php

declare(strict_types=1);

/**
 * Ensures mainadmin has sign-in email + display name (local dev helper).
 *
 * Usage: php scripts/ensure_mainadmin_email.php [email] [display_name]
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/auth.php';

$email = isset($argv[1]) ? auth_normalize_email((string)$argv[1]) : 'zshah2@oldwestbury.edu';
$displayName = isset($argv[2]) ? trim((string)$argv[2]) : 'Mohammad Shah';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email.\n");
    exit(1);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM auth_users WHERE username = ? LIMIT 1');
$stmt->execute(['mainadmin']);
$id = (int)($stmt->fetchColumn() ?: 0);
if ($id < 1) {
    fwrite(STDERR, "No mainadmin user — run: php scripts/seed_superadmin.php {$email} 'Main@1234' mainadmin\n");
    exit(1);
}

[$ok, $err] = auth_update_user_email($id, $email);
if (!$ok) {
    fwrite(STDERR, ($err ?? 'Email update failed') . "\n");
    exit(1);
}
[$ok, $err] = auth_update_user_display_name($id, $displayName);
if (!$ok) {
    fwrite(STDERR, ($err ?? 'Name update failed') . " — run: php scripts/migrate.php\n");
    exit(1);
}

fwrite(STDOUT, "mainadmin: sign-in {$email}, display name \"{$displayName}\"\n");
