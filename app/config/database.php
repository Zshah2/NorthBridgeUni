<?php

$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'collegeweb',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '',
    'charset' => 'utf8mb4',
];

$localPath = __DIR__ . '/database.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        foreach ($local as $key => $value) {
            if ($key === 'port') {
                $config['port'] = (int)$value;

                continue;
            }
            $config[$key] = $value;
        }
    }
}

// Env wins over file (easy override in PhpStorm run config)
if (getenv('DB_HOST') !== false) {
    $config['host'] = (string)getenv('DB_HOST');
}
if (getenv('DB_PORT') !== false) {
    $config['port'] = (int)getenv('DB_PORT');
}
if (getenv('DB_NAME') !== false) {
    $config['database'] = (string)getenv('DB_NAME');
}
if (getenv('DB_USER') !== false) {
    $config['username'] = (string)getenv('DB_USER');
}
if (getenv('DB_PASS') !== false) {
    $config['password'] = (string)getenv('DB_PASS');
}

return $config;
