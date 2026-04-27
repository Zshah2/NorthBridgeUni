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
    $sql = trim($sql);
    if ($sql === '') {
        continue;
    }
    try {
        $pdo->exec($sql);
        fwrite(STDOUT, 'Applied: ' . basename($file) . "\n");
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Idempotent re-runs: duplicate column / duplicate key name / already exists
        if (preg_match('/Duplicate column|duplicate key name|already exists|1060|1061|1050/i', $msg)) {
            fwrite(STDOUT, 'Skipped (already applied): ' . basename($file) . "\n");
            continue;
        }
        fwrite(STDERR, basename($file) . ': ' . $msg . "\n");
        exit(1);
    }
}

fwrite(STDOUT, "Done.\n");
