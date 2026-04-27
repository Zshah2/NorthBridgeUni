<?php

declare(strict_types=1);

/**
 * Local diagnostics: REQUEST_URI / SCRIPT_NAME vs url() output.
 *
 * Allowed only when APP_DEBUG=1, SHOW_ROUTING_TEST=1, or REMOTE_ADDR is loopback.
 * Remove or tighten before public deployment.
 */

require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/url.php';

$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$localhost = $ip === '127.0.0.1' || $ip === '::1';
$allowed = app_debug()
    || getenv('SHOW_ROUTING_TEST') === '1'
    || getenv('SHOW_ROUTING_TEST') === 'true'
    || $localhost;

if (!$allowed) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not available. Set APP_DEBUG=1 or SHOW_ROUTING_TEST=1, or open from localhost.';

    exit;
}

header('Content-Type: text/html; charset=utf-8');

$serverSubset = [];
foreach ([
    'REQUEST_URI',
    'SCRIPT_NAME',
    'SCRIPT_FILENAME',
    'DOCUMENT_ROOT',
    'PHP_SELF',
    'PATH_INFO',
    'HTTP_HOST',
    'REMOTE_ADDR',
    'REQUEST_METHOD',
    'QUERY_STRING',
] as $key) {
    $serverSubset[$key] = $_SERVER[$key] ?? null;
}

$urlSamples = [
    '/' => url('/'),
    '/index.php' => url('/index.php'),
    '/login' => url('/login'),
    '/login.php' => url('/login.php'),
    '/signup' => url('/signup'),
    '/admin.php' => url('/admin.php'),
    '/logout.php' => url('/logout.php'),
    '/assets/css/app.css' => url('/assets/css/app.css'),
];

$fns = [
    'app_script_name()' => app_script_name(),
    'app_effective_script_name()' => app_effective_script_name(),
    'app_front_controller()' => app_front_controller(),
    'app_base_path()' => app_base_path(),
    'app_use_index_php_in_links()' => app_use_index_php_in_links() ? 'true' : 'false',
];

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Routing test — CollegeWeb</title>
  <style>
    body { font-family: ui-sans-serif, system-ui, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 1.5rem; line-height: 1.5; }
    h1 { font-size: 1.25rem; margin: 0 0 1rem; }
    p.note { font-size: 0.875rem; color: #94a3b8; margin-bottom: 1.5rem; }
    table { border-collapse: collapse; width: 100%; max-width: 56rem; font-size: 0.8125rem; }
    th, td { border: 1px solid #334155; padding: 0.5rem 0.625rem; text-align: left; vertical-align: top; word-break: break-all; }
    th { background: #1e293b; color: #cbd5e1; }
    code { font-size: 0.8125rem; }
  </style>
</head>
<body>
  <h1>Routing / URL diagnostics</h1>
  <p class="note">Open this page from the same host you use for the app (e.g. PhpStorm <code>:63342</code>). Values update per request.</p>

  <h2 style="font-size:1rem;margin:1.25rem 0 0.5rem">Computed helpers</h2>
  <table>
    <thead><tr><th>Helper</th><th>Value</th></tr></thead>
    <tbody>
    <?php foreach ($fns as $k => $v): ?>
      <tr><td><code><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></code></td><td><code><?= htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') ?></code></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h2 style="font-size:1rem;margin:1.25rem 0 0.5rem"><code>url()</code> samples</h2>
  <table>
    <thead><tr><th>Argument</th><th>Result</th></tr></thead>
    <tbody>
    <?php foreach ($urlSamples as $arg => $result): ?>
      <tr><td><code><?= htmlspecialchars($arg, ENT_QUOTES, 'UTF-8') ?></code></td><td><code><?= htmlspecialchars($result, ENT_QUOTES, 'UTF-8') ?></code></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <h2 style="font-size:1rem;margin:1.25rem 0 0.5rem"><code>$_SERVER</code> (subset)</h2>
  <table>
    <thead><tr><th>Key</th><th>Value</th></tr></thead>
    <tbody>
    <?php foreach ($serverSubset as $k => $v): ?>
      <tr><td><code><?= htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') ?></code></td><td><code><?= htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <p class="note" style="margin-top:1.5rem"><a href="<?= htmlspecialchars(url('/'), ENT_QUOTES, 'UTF-8') ?>" style="color:#38bdf8">Home</a>
    · <a href="<?= htmlspecialchars(url('/login'), ENT_QUOTES, 'UTF-8') ?>" style="color:#38bdf8">Staff route /login</a>
    · <a href="<?= htmlspecialchars(url('/login.php'), ENT_QUOTES, 'UTF-8') ?>" style="color:#38bdf8">Standalone login.php</a></p>
</body>
</html>
