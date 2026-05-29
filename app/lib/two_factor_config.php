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

            return $cfg;
        }
    }

    $cfg = $defaults;

    return $cfg;
}
