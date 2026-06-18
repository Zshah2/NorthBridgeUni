<?php

declare(strict_types=1);

/**
 * One-shot local setup: composer, migrations, mainadmin seed + profile.
 */

$root = dirname(__DIR__);
chdir($root);

function run(string $cmd): int
{
    fwrite(STDOUT, "→ {$cmd}\n");
    passthru($cmd, $code);

    return (int)$code;
}

if (!is_file($root . '/vendor/autoload.php')) {
    if (run('composer install --no-interaction') !== 0) {
        exit(1);
    }
}

if (run('php scripts/migrate.php') !== 0) {
    exit(1);
}

if (run("php scripts/seed_superadmin.php zshah2@oldwestbury.edu 'Main@1234' mainadmin") !== 0) {
    fwrite(STDERR, "Seed failed (MySQL running?)\n");
    exit(1);
}

if (run('php scripts/ensure_mainadmin_email.php') !== 0) {
    exit(1);
}

fwrite(STDOUT, "\nReady.\n");
fwrite(STDOUT, "  Sign in: zshah2@oldwestbury.edu / Main@1234\n");
fwrite(STDOUT, "  Server:  php -S localhost:8000 -t public public/router.php\n");
fwrite(STDOUT, "  Test mail: php scripts/test_2fa_email.php\n");
fwrite(STDOUT, "  Real email: app/config/2fa_config.local.php with smtp_password (see .example)\n");
