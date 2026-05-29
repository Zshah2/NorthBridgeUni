<?php

declare(strict_types=1);

require_once __DIR__ . '/two_factor_config.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

function twofa_load_composer(): bool
{
    static $loaded = false;
    if ($loaded) {
        return class_exists(PHPMailer::class);
    }
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        $loaded = true;
    }

    return class_exists(PHPMailer::class);
}

function twofa_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function twofa_generate_code(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function twofa_invalidate_pending(PDO $pdo, string $email): void
{
    $id = twofa_normalize_email($email);
    $pdo->prepare('UPDATE otp_codes SET used = 1 WHERE identifier = ? AND used = 0')->execute([$id]);
}

function twofa_store_code(PDO $pdo, string $email, string $code): void
{
    $cfg = twofa_config();
    $minutes = max(1, (int)($cfg['otp_expiry_minutes'] ?? 5));
    $id = twofa_normalize_email($email);
    $pdo->prepare('
      INSERT INTO otp_codes (identifier, code, expires_at, used)
      VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0)
    ')->execute([$id, $code, $minutes]);
}

/**
 * @return array{0: bool, 1: ?string}
 */
function twofa_send_email(string $email, string $code): array
{
    if (!twofa_load_composer()) {
        return [false, 'Email is not configured. Run: composer install'];
    }

    $cfg = twofa_config();
    $host = trim((string)($cfg['smtp_host'] ?? ''));
    if ($host === '') {
        return [false, 'SMTP is not configured. Copy app/config/2fa_config.php.example to 2fa_config.php'];
    }

    $minutes = max(1, (int)($cfg['otp_expiry_minutes'] ?? 5));
    $fromEmail = trim((string)($cfg['from_email'] ?? ''));
    $fromName = trim((string)($cfg['from_name'] ?? 'Northbridge College Admin'));
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid from_email in 2fa_config.php'];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = (int)($cfg['smtp_port'] ?? 587);
        $enc = strtolower(trim((string)($cfg['smtp_encryption'] ?? 'tls')));
        if ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $user = (string)($cfg['smtp_username'] ?? '');
        if ($user !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = (string)($cfg['smtp_password'] ?? '');
        }
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->Subject = 'Your Northbridge admin verification code';
        $mail->Body = "Your verification code is: {$code}\n\nThis code expires in {$minutes} minutes.\n\nIf you did not request this, ignore this email.";
        $mail->send();

        return [true, null];
    } catch (MailException $e) {
        $msg = app_debug() ? $e->getMessage() : 'Could not send verification email.';

        return [false, $msg];
    } catch (Throwable $e) {
        $msg = app_debug() ? $e->getMessage() : 'Could not send verification email.';

        return [false, $msg];
    }
}

/**
 * @return array{0: bool, 1: ?string}
 */
function twofa_issue_and_send(PDO $pdo, string $email): array
{
    $email = twofa_normalize_email($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Invalid email address.'];
    }

    twofa_invalidate_pending($pdo, $email);
    $code = twofa_generate_code();
    twofa_store_code($pdo, $email, $code);

    return twofa_send_email($email, $code);
}

function twofa_verify(PDO $pdo, string $email, string $code): bool
{
    $email = twofa_normalize_email($email);
    $code = preg_replace('/\D/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return false;
    }

    $stmt = $pdo->prepare('
      SELECT id FROM otp_codes
      WHERE identifier = ? AND code = ? AND used = 0 AND expires_at > NOW()
      ORDER BY id DESC
      LIMIT 1
    ');
    $stmt->execute([$email, $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    $pdo->prepare('UPDATE otp_codes SET used = 1 WHERE id = ?')->execute([(int)$row['id']]);

    return true;
}
