<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

$dir = __DIR__ . '/../database/migrations';
$files = glob($dir . '/*.sql');
sort($files);

if (!$files) {
    fwrite(STDERR, "No migration files found in $dir\n");
    exit(1);
}

$pdo = db();

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Failed to read: $file\n");
        exit(1);
    }
    $pdo->exec($sql);
    fwrite(STDOUT, "Applied: " . basename($file) . "\n");
}

fwrite(STDOUT, "Done.\n");

