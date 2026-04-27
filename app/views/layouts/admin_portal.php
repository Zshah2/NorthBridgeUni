<?php
/** @var array $app */
/** @var string $content */
/** @var string|null $pageTitle */
/** @var string $admin_username */
/** @var string $admin_role_label */
/** @var string $csrf */
/** @var string|null $admin_nav_active */
$docTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
    ? $pageTitle
    : 'Administration — Northbridge College';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($docTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
  <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] } } } };</script>
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">
  <header class="relative border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 text-sm font-bold text-white">NB</span>
        <div>
          <div class="text-sm font-semibold text-slate-900">Northbridge Admin</div>
          <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span><?= htmlspecialchars($admin_username ?? '') ?></span>
            <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700"><?= htmlspecialchars($admin_role_label ?? '') ?></span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars(url('/')) ?>" class="text-sm text-slate-600 hover:text-slate-900">Site home</a>
        <form method="post" action="<?= htmlspecialchars(url('/logout.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Log out</button>
        </form>
      </div>
    </div>
  </header>

  <main class="relative mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="grid gap-6 lg:grid-cols-12">
      <aside class="lg:col-span-3">
        <?php require view_path('partials/admin_portal_sidebar.php'); ?>
      </aside>
      <div class="lg:col-span-9">
        <?= $content ?>
      </div>
    </div>
  </main>
</body>
</html>
