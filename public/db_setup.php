<?php

declare(strict_types=1);

$secret = getenv('SETUP_SECRET');

if (!isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    die('Access Denied.');
}

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/db.php';

header('Content-Type: text/plain; charset=utf-8');

if ($secret === false || $secret === '') {
    http_response_code(500);
    echo "SETUP_SECRET is not set.\n";
    exit;
}

$host = getenv('MYSQL_HOST');
$port = getenv('MYSQL_PORT');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');
$db = getenv('MYSQL_DATABASE');

if ($host === false || $host === '' || $db === false || $db === '') {
    http_response_code(500);
    echo "MYSQL_* environment variables are not set (need MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE).\n";
    exit;
}

function setup_split_sql(string $sql): array
{
    // Remove /* */ block comments.
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;

    $out = [];
    $buf = '';
    $inSingle = false;
    $inDouble = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = $i + 1 < $len ? $sql[$i + 1] : '';

        // Line comments: -- ... and # ...
        if (!$inSingle && !$inDouble) {
            if ($ch === '-' && $next === '-') {
                // Skip until end of line.
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $buf .= "\n";
                continue;
            }
            if ($ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                $buf .= "\n";
                continue;
            }
        }

        if ($ch === "'" && !$inDouble) {
            // Toggle single quotes unless escaped by backslash.
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
        } elseif ($ch === '"' && !$inSingle) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
        }

        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($buf);
            if ($stmt !== '') {
                $out[] = $stmt;
            }
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    $stmt = trim($buf);
    if ($stmt !== '') {
        $out[] = $stmt;
    }

    return $out;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . ($port !== false && $port !== '' ? $port : '3306') . ';dbname=' . $db . ';charset=utf8mb4',
        $user !== false ? (string)$user : '',
        $pass !== false ? (string)$pass : ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {
    http_response_code(500);
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Track applied migration files so re-runs are safe.
$pdo->exec('
  CREATE TABLE IF NOT EXISTS setup_migrations (
    filename VARCHAR(255) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
');

$dir = __DIR__ . '/../database/migrations';
$files = glob($dir . '/*.sql');
sort($files);

if (!$files) {
    http_response_code(500);
    echo "No migration files found in $dir\n";
    exit;
}

echo "Applying migrations from: $dir\n\n";

foreach ($files as $file) {
    $name = basename($file);

    $already = $pdo->prepare('SELECT 1 FROM setup_migrations WHERE filename = ? LIMIT 1');
    $already->execute([$name]);
    if ($already->fetchColumn()) {
        echo "Skipped (already applied): $name\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        http_response_code(500);
        echo "Failed to read: $name\n";
        exit;
    }

    $sql = trim($sql);
    if ($sql === '') {
        $pdo->prepare('INSERT INTO setup_migrations (filename) VALUES (?)')->execute([$name]);
        echo "Applied (empty): $name\n";
        continue;
    }

    $pdo->beginTransaction();
    try {
        foreach (setup_split_sql($sql) as $stmt) {
            $pdo->exec($stmt);
        }
        $pdo->prepare('INSERT INTO setup_migrations (filename) VALUES (?)')->execute([$name]);
        $pdo->commit();
        echo "Applied: $name\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "FAILED on $name: " . $e->getMessage() . "\n";
        exit;
    }
}

// Ensure OTP table exists (also created by migration 019, but requested explicitly).
$pdo->exec('
  CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_otp_identifier_used (identifier, used, expires_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
');

echo "\nSuccess: database schema created/updated.\n";
echo "Recommended: delete public/db_setup.php after running once.\n";
