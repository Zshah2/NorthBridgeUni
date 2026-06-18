<?php

declare(strict_types=1);

/**
 * Send a test OTP email (does not store a code in the DB).
 *
 * Usage: php scripts/test_2fa_email.php [recipient@email]
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/two_factor.php';

$to = isset($argv[1]) ? trim((string)$argv[1]) : 'zshah2@oldwestbury.edu';
$code = twofa_generate_code();

[$sent, $err] = twofa_send_email($to, $code, 'Mohammad Shah');
if (!$sent) {
    fwrite(STDERR, "Failed: " . ($err ?? 'unknown') . "\n");
    fwrite(STDERR, "Tip: copy app/config/2fa_config.local.php.example → 2fa_config.local.php and set smtp_password.\n");
    fwrite(STDERR, "     Or export SMTP_PASSWORD. With dev_log_otp true, code is logged to storage/logs/otp.log\n");
    exit(1);
}

$cfg = twofa_config();
if (twofa_dev_log_enabled($cfg) && trim((string)($cfg['smtp_password'] ?? '')) === '') {
    fwrite(STDOUT, "Dev fallback — code {$code} (see storage/logs/otp.log). Configure SMTP for real email.\n");
} else {
    fwrite(STDOUT, "Sent test code {$code} to {$to}. Check your inbox.\n");
}
