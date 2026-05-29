<?php

declare(strict_types=1);

/**
 * Database settings from environment (DB_* or MYSQL_* on cloud hosts).
 * For local dev, copy database.local.php.example → database.local.php (gitignored).
 * No credentials are hardcoded in this file.
 */

$config = [
    'host' => '',
    'port' => 3306,
    'database' => '',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4',
];

/** @var array<string, true> */
$setFromEnv = [];

$applyEnv = static function (string $envKey, string $configKey, bool $asInt = false) use (&$config, &$setFromEnv): void {
    $raw = getenv($envKey);
    if ($raw === false || $raw === '') {
        return;
    }
    $config[$configKey] = $asInt ? (int)$raw : (string)$raw;
    $setFromEnv[$configKey] = true;
};

// MYSQL_* (common on some managed platforms)
$applyEnv('MYSQL_HOST', 'host');
$applyEnv('MYSQL_PORT', 'port', true);
$applyEnv('MYSQL_DATABASE', 'database');
$applyEnv('MYSQL_USER', 'username');
if (getenv('MYSQL_PASSWORD') !== false) {
    $config['password'] = (string)getenv('MYSQL_PASSWORD');
    $setFromEnv['password'] = true;
}

// DB_* (DigitalOcean, AWS RDS, local scripts) — only if MYSQL_* did not set the field
if (!isset($setFromEnv['host'])) {
    $applyEnv('DB_HOST', 'host');
}
if (!isset($setFromEnv['port'])) {
    $applyEnv('DB_PORT', 'port', true);
}
if (!isset($setFromEnv['database'])) {
    $applyEnv('DB_NAME', 'database');
}
if (!isset($setFromEnv['username'])) {
    $applyEnv('DB_USER', 'username');
    if (!isset($setFromEnv['username'])) {
        $applyEnv('DB_USERNAME', 'username');
    }
}
if (!isset($setFromEnv['password'])) {
    if (getenv('DB_PASS') !== false) {
        $config['password'] = (string)getenv('DB_PASS');
        $setFromEnv['password'] = true;
    } elseif (getenv('DB_PASSWORD') !== false) {
        $config['password'] = (string)getenv('DB_PASSWORD');
        $setFromEnv['password'] = true;
    }
}

$localPath = __DIR__ . '/database.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        foreach ($local as $key => $value) {
            if (!array_key_exists($key, $config)) {
                continue;
            }
            if (isset($setFromEnv[$key])) {
                continue;
            }
            if ($key === 'port') {
                $config['port'] = (int)$value;
            } else {
                $config[$key] = $value;
            }
        }
    }
}

return $config;
