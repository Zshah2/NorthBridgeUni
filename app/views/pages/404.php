<?php
/** @var array $app */
/** @var string $path */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
    <div class="mx-auto max-w-lg text-center">
      <p class="text-sm font-semibold text-sky-200">404</p>
      <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Page not found</h1>
      <p class="mt-3 text-sm text-slate-400">
        No route matches <code class="rounded bg-white/5 px-1.5 py-0.5 text-sky-200/90"><?= htmlspecialchars($path) ?></code>.
        Valid staff URLs include <code class="text-sky-200/90">/login</code>, <code class="text-sky-200/90">/admin</code>, and paths under <code class="text-sky-200/90">/admin/…</code>.
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a class="rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" href="<?= htmlspecialchars(url('/')) ?>">Home</a>
        <a class="rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100 hover:bg-white/10" href="<?= htmlspecialchars(url('/login')) ?>">Staff login</a>
      </div>
    </div>
  </div>
</section>
