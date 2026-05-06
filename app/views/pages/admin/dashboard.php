<?php
/** @var array $app */
/** @var string $csrf */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <?php require view_path('partials/admin_nav.php'); ?>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Admin</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Dashboard</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">Search students and view enrollment data directly from MySQL.</p>
      </div>
      <form method="post" action="<?= htmlspecialchars(url('/admin/logout')) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <button class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-slate-100 hover:bg-white/10" type="submit">
          Logout
        </button>
      </form>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <a class="rounded-3xl border border-white/10 bg-white/5 p-5 hover:bg-white/10" href="<?= htmlspecialchars(url('/admin/students/search')) ?>">
        <div class="text-base font-semibold text-white">Student lookup</div>
        <div class="mt-1 text-sm text-slate-300">Search by student_id and view live enrollments.</div>
        <div class="mt-4 text-sm font-semibold text-sky-200">Open →</div>
      </a>
      <a class="rounded-3xl border border-white/10 bg-white/5 p-5 hover:bg-white/10" href="<?= htmlspecialchars(url('/admin.php?view=courses')) ?>">
        <div class="text-base font-semibold text-white">Course offerings</div>
        <div class="mt-1 text-sm text-slate-300">Browse sections by term with instructor and enrollment counts.</div>
        <div class="mt-4 text-sm font-semibold text-sky-200">Open →</div>
      </a>
      <a class="rounded-3xl border border-white/10 bg-white/5 p-5 hover:bg-white/10" href="<?= htmlspecialchars(url('/admin/holds')) ?>">
        <div class="text-base font-semibold text-white">Student holds</div>
        <div class="mt-1 text-sm text-slate-300">Add or clear registration holds by student_id.</div>
        <div class="mt-4 text-sm font-semibold text-sky-200">Open →</div>
      </a>
      <div class="rounded-3xl border border-white/10 bg-white/5 p-5 sm:col-span-2 lg:col-span-3">
        <div class="text-base font-semibold text-white">Setup</div>
        <div class="mt-1 text-sm text-slate-300">Run migrations, seed an admin, and import CSVs.</div>
        <ul class="mt-4 space-y-2 text-sm text-slate-300">
          <li><span class="text-slate-400">1)</span> `php scripts/migrate.php`</li>
          <li><span class="text-slate-400">2)</span> `php scripts/seed_admin.php admin yourPassword`</li>
          <li><span class="text-slate-400">3)</span> `php scripts/import_all.php`</li>
          <li><span class="text-slate-400">4)</span> `php scripts/seed_demo_registration.php` (demo term, sections, enrollments, sample hold)</li>
        </ul>
      </div>
    </div>
  </div>
</section>

