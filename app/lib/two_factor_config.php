<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function twofa_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $defaults = [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => '',
        'from_name' => 'Northbridge College Admin',
        'otp_expiry_minutes' => 5,
    ];

    $path = __DIR__ . '/../config/2fa_config.php';
    if (is_file($path)) {
        $local = require $path;
        if (is_array($local)) {
            $cfg = array_merge($defaults, $local);
            $cfg = twofa_config_apply_env($cfg);

            return $cfg;
        }
    }

    $cfg = twofa_config_apply_env($defaults);

    return $cfg;
}

/**
 * @param array<string, mixed> $cfg
 * @return array<string, mixed>
 */
function twofa_config_apply_env(array $cfg): array
{
    $map = [
        'smtp_host' => 'SMTP_HOST',
        'smtp_port' => 'SMTP_PORT',
        'smtp_encryption' => 'SMTP_ENCRYPTION',
        'smtp_username' => 'SMTP_USERNAME',
        'smtp_password' => 'SMTP_PASSWORD',
        'from_email' => 'SMTP_FROM_EMAIL',
        'from_name' => 'SMTP_FROM_NAME',
        'otp_expiry_minutes' => 'OTP_EXPIRY_MINUTES',
    ];

    foreach ($map as $key => $envKey) {
        $val = getenv($envKey);
        if ($val === false || $val === '') {
            continue;
        }
        if ($key === 'smtp_port' || $key === 'otp_expiry_minutes') {
            $cfg[$key] = (int)$val;
        } else {
            $cfg[$key] = (string)$val;
        }
    }

    return $cfg;
}
