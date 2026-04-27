<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/url.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/auth.php';
require __DIR__ . '/../app/lib/csrf.php';

header('Content-Type: text/html; charset=utf-8');

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';

$dbOk = false;
try {
    db();
    $dbOk = true;
} catch (Throwable) {
}

auth_start_session();

if ($dbOk && auth_is_portal_user() && !$isPost) {
    header('Location: ' . url('/admin.php'));
    exit;
}

$loginError = null;
$registerError = null;
$registered = isset($_GET['registered']) && (string)$_GET['registered'] !== '0';
$lastIntent = '';

if ($isPost && $dbOk) {
    try {
        csrf_require_valid();
        $intent = (string)($_POST['intent'] ?? '');
        $lastIntent = $intent;
        if ($intent === 'login') {
            $u = trim((string)($_POST['username'] ?? ''));
            $p = (string)($_POST['password'] ?? '');
            if ($u === '' || $p === '') {
                $loginError = 'Enter username and password.';
            } elseif (auth_login_portal_user($u, $p)) {
                header('Location: ' . url('/admin.php'));
                exit;
            } else {
                $loginError = 'Invalid username or password.';
            }
        } elseif ($intent === 'register') {
            $u = trim((string)($_POST['username'] ?? ''));
            $p = (string)($_POST['password'] ?? '');
            $c = (string)($_POST['confirm_password'] ?? '');
            if ($p !== $c) {
                $registerError = 'Passwords do not match.';
            } else {
                [$ok, $err] = auth_create_admin($u, $p);
                if ($ok) {
                    header('Location: ' . url('/login.php?registered=1'));
                    exit;
                }
                $registerError = $err ?: 'Registration failed.';
            }
        } else {
            $loginError = 'Invalid form submission.';
        }
    } catch (Throwable $e) {
        $msg = app_debug() ? $e->getMessage() : 'Database error. Run php scripts/migrate.php.';
        if ($lastIntent === 'register') {
            $registerError = $msg;
        } else {
            $loginError = $msg;
        }
    }
} elseif ($isPost && !$dbOk) {
    $loginError = 'Database is not available.';
}

$csrf = csrf_token();
$initialRegister = $registerError !== null
    || (isset($_GET['view']) && (string)$_GET['view'] === 'register')
    || (isset($_GET['register']) && (string)$_GET['register'] !== '0');

$pageTitle = 'Staff sign in — Northbridge College';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
  <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] } } } };</script>
</head>
<body class="min-h-full bg-slate-950 font-sans text-slate-100 antialiased">
  <div class="pointer-events-none fixed inset-0" aria-hidden="true">
    <div class="absolute left-1/2 top-0 h-96 w-96 -translate-x-1/2 rounded-full bg-sky-600/20 blur-3xl"></div>
  </div>

  <header class="relative z-10 border-b border-white/10 bg-slate-950/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
      <a href="<?= htmlspecialchars(url('/')) ?>" class="text-sm text-slate-400 hover:text-white">← Site home</a>
    </div>
  </header>

  <main class="relative z-10 mx-auto max-w-md px-4 py-10 sm:px-6">
    <?php if (!$dbOk): ?>
      <div class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
        <strong class="block text-amber-50">Cannot connect to MySQL.</strong>
        Start MySQL, create the DB, copy <code class="rounded bg-black/30 px-1 text-xs">app/config/database.local.php.example</code> → <code class="rounded bg-black/30 px-1 text-xs">database.local.php</code> with your user/password, then run <code class="rounded bg-black/30 px-1 text-xs">php scripts/migrate.php</code>.
      </div>
    <?php endif; ?>

    <div id="panelLogin" class="rounded-3xl border border-white/10 bg-white/[0.05] p-8 shadow-xl <?= $initialRegister ? 'hidden' : '' ?>">
      <div class="text-center">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-sky-400 to-indigo-500 text-lg font-bold text-slate-950">NB</div>
        <h1 class="mt-4 text-2xl font-semibold text-white">Northbridge College</h1>
        <p class="mt-1 text-sm text-slate-400">Staff portal</p>
      </div>
      <?php if ($registered): ?>
        <div class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">Account created. Sign in below.</div>
      <?php endif; ?>
      <?php if ($loginError): ?>
        <div class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>
      <form class="mt-6 space-y-4" method="post" action="<?= htmlspecialchars(url('/login.php')) ?>" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="intent" value="login" />
        <div>
          <label class="text-sm font-medium text-slate-300" for="username">Username</label>
          <input id="username" name="username" type="text" autocomplete="username" required
            class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
            placeholder="Username" <?= $dbOk ? '' : 'disabled' ?> />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-300" for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required
            class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
            placeholder="Password" <?= $dbOk ? '' : 'disabled' ?> />
        </div>
        <button type="submit" class="w-full rounded-xl bg-sky-500 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400 disabled:opacity-50" <?= $dbOk ? '' : 'disabled' ?>>Sign in</button>
      </form>
      <p class="mt-6 text-center text-sm text-slate-400">
        No account? <button type="button" id="btnShowRegister" class="font-semibold text-sky-300 hover:text-sky-200">Register</button>
      </p>
    </div>

    <div id="panelRegister" class="rounded-3xl border border-white/10 bg-white/[0.05] p-8 shadow-xl <?= $initialRegister ? '' : 'hidden' ?>">
      <div class="text-center">
        <h2 class="text-xl font-semibold text-white">Create admin account</h2>
        <p class="mt-1 text-sm text-slate-400">Full admin role (seed other roles via scripts).</p>
      </div>
      <?php if ($registerError): ?>
        <div class="mt-5 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-100"><?= htmlspecialchars($registerError) ?></div>
      <?php endif; ?>
      <form class="mt-6 space-y-4" method="post" action="<?= htmlspecialchars(url('/login.php?view=register')) ?>" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="intent" value="register" />
        <div>
          <label class="text-sm font-medium text-slate-300" for="reg_username">Username</label>
          <input id="reg_username" name="username" type="text" required class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white" <?= $dbOk ? '' : 'disabled' ?> />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-300" for="reg_password">Password</label>
          <input id="reg_password" name="password" type="password" minlength="8" required class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white" <?= $dbOk ? '' : 'disabled' ?> />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-300" for="reg_confirm">Confirm</label>
          <input id="reg_confirm" name="confirm_password" type="password" minlength="8" required class="mt-1 w-full rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 text-sm text-white" <?= $dbOk ? '' : 'disabled' ?> />
        </div>
        <button type="submit" class="w-full rounded-xl border border-sky-400/40 bg-sky-500/15 py-3 text-sm font-semibold text-sky-100 hover:bg-sky-500/25" <?= $dbOk ? '' : 'disabled' ?>>Create account</button>
      </form>
      <p class="mt-6 text-center text-sm text-slate-400">
        Have an account? <button type="button" id="btnShowLogin" class="font-semibold text-sky-300 hover:text-sky-200">Sign in</button>
      </p>
    </div>
  </main>
  <script>
    (function () {
      var base = <?= json_encode(url('/login.php'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) ?>;
      var loginPanel = document.getElementById('panelLogin');
      var regPanel = document.getElementById('panelRegister');
      document.getElementById('btnShowRegister')?.addEventListener('click', function () {
        loginPanel.classList.add('hidden');
        regPanel.classList.remove('hidden');
        history.replaceState(null, '', base + '?view=register');
      });
      document.getElementById('btnShowLogin')?.addEventListener('click', function () {
        regPanel.classList.add('hidden');
        loginPanel.classList.remove('hidden');
        history.replaceState(null, '', base);
      });
    })();
  </script>
</body>
</html>
