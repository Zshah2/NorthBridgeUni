<?php
/** @var array $app */
?>
<header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/70 backdrop-blur">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
    <a href="<?= htmlspecialchars(url('/')) ?>" class="group inline-flex items-center gap-2">
      <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 text-sm font-semibold text-slate-950">
        <?= htmlspecialchars(substr($app['site']['shortName'], 0, 2)) ?>
      </span>
      <span class="flex flex-col leading-tight">
        <span class="text-sm font-semibold text-slate-50 group-hover:text-white"><?= htmlspecialchars($app['site']['name']) ?></span>
        <span class="text-xs text-slate-400">CollegeWeb</span>
      </span>
    </a>

    <nav class="hidden items-center gap-6 md:flex" aria-label="Primary">
      <?php foreach ($app['nav'] as $item): ?>
        <a class="text-sm font-medium text-slate-300 hover:text-white" href="<?= htmlspecialchars(nav_url($item['href'])) ?>">
          <?= htmlspecialchars($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="hidden items-center gap-3 md:flex">
      <a class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-200 hover:text-white" href="<?= htmlspecialchars(nav_url($app['cta']['secondary']['href'])) ?>">
        <?= htmlspecialchars($app['cta']['secondary']['label']) ?>
      </a>
      <a class="rounded-xl bg-sky-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/60" href="<?= htmlspecialchars(nav_url($app['cta']['primary']['href'])) ?>">
        <?= htmlspecialchars($app['cta']['primary']['label']) ?>
      </a>
    </div>

    <button
      id="mobileMenuButton"
      class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-slate-200 hover:bg-white/10 hover:text-white md:hidden"
      type="button"
      aria-label="Open menu"
      aria-expanded="false"
      aria-controls="mobileMenu"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>
  </div>

  <div id="mobileMenu" class="hidden border-t border-white/10 bg-slate-950 md:hidden">
    <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
      <div class="flex flex-col gap-2">
        <?php foreach ($app['nav'] as $item): ?>
          <a class="rounded-xl px-3 py-2 text-sm font-medium text-slate-200 hover:bg-white/5 hover:text-white" href="<?= htmlspecialchars(nav_url($item['href'])) ?>">
            <?= htmlspecialchars($item['label']) ?>
          </a>
        <?php endforeach; ?>
        <div class="mt-2 grid grid-cols-2 gap-2">
          <a class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-center text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white" href="<?= htmlspecialchars(nav_url($app['cta']['secondary']['href'])) ?>">
            <?= htmlspecialchars($app['cta']['secondary']['label']) ?>
          </a>
          <a class="rounded-xl bg-sky-500 px-3 py-2 text-center text-sm font-semibold text-slate-950 hover:bg-sky-400" href="<?= htmlspecialchars(nav_url($app['cta']['primary']['href'])) ?>">
            <?= htmlspecialchars($app['cta']['primary']['label']) ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

