<?php
/** @var array $app */
?>
<header class="sticky top-0 z-40 border-b border-fuchsia-500/25 bg-[#0a0f1f]/92 backdrop-blur">
  <div class="border-b border-fuchsia-500/20 bg-gradient-to-r from-indigo-950 via-violet-950 to-fuchsia-950 text-xs text-slate-200">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6">
      <div class="hidden sm:flex flex-wrap items-center gap-x-3 gap-y-1">
        <span class="font-semibold text-slate-100">Information for:</span>
        <a class="transition-colors hover:text-cyan-200" href="#admissions">Students</a>
        <span class="text-fuchsia-400/40">·</span>
        <a class="transition-colors hover:text-cyan-200" href="/login.php">Faculty &amp; Staff</a>
        <span class="text-fuchsia-400/40">·</span>
        <a class="transition-colors hover:text-cyan-200" href="#visit">Families</a>
        <span class="text-fuchsia-400/40">·</span>
        <a class="transition-colors hover:text-cyan-200" href="#visit">Visitors</a>
        <span class="text-fuchsia-400/40">·</span>
        <a class="transition-colors hover:text-cyan-200" href="#news">Alumni</a>
      </div>

      <div class="flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:hidden">
          <span class="font-semibold text-slate-100">For:</span>
          <a class="transition-colors hover:text-cyan-200" href="#admissions">Students</a>
          <span class="text-fuchsia-400/40">·</span>
          <a class="transition-colors hover:text-cyan-200" href="/login.php">Staff</a>
        </div>

        <a class="inline-flex items-center gap-2 font-semibold text-cyan-100/90 transition-colors hover:text-cyan-50" href="#programs">
          <svg class="h-4 w-4 text-fuchsia-300/90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M11 19a7 7 0 1 0 0-14 7 7 0 0 0 0 14Z" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Search
        </a>
      </div>
    </div>
  </div>

  <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
    <a href="<?= htmlspecialchars(url('/')) ?>" class="group inline-flex items-center gap-3">
      <img
        src="<?= htmlspecialchars(url('/assets/img/northbridge_university_icon.svg')) ?>"
        alt="<?= htmlspecialchars($app['site']['name']) ?>"
        width="48"
        height="48"
        class="h-11 w-11 shrink-0 rounded-2xl object-cover shadow-md shadow-black/25 ring-1 ring-white/15 sm:h-12 sm:w-12"
        loading="eager"
        decoding="async"
      />
    </a>

    <nav class="hidden items-center gap-5 lg:flex" aria-label="Primary">
      <?php foreach ($app['nav'] as $item): ?>
        <a class="text-sm font-semibold text-slate-200 transition-colors hover:text-cyan-200" href="<?= htmlspecialchars(nav_url($item['href'])) ?>">
          <?= htmlspecialchars($item['label']) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="hidden items-center gap-2 lg:flex">
      <a class="rounded-xl px-3 py-2 text-sm font-semibold text-cyan-200/90 transition-colors hover:text-cyan-50" href="<?= htmlspecialchars(nav_url('/login.php')) ?>">
        Login
      </a>
      <a class="rounded-xl bg-gradient-to-r from-fuchsia-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-fuchsia-900/35 transition hover:from-fuchsia-500 hover:to-violet-500" href="<?= htmlspecialchars(nav_url($app['cta']['primary']['href'])) ?>">
        <?= htmlspecialchars($app['cta']['primary']['label']) ?>
      </a>
    </div>

    <button
      id="mobileMenuButton"
      class="inline-flex items-center justify-center rounded-xl border border-fuchsia-500/30 bg-fuchsia-950/30 p-2 text-cyan-100 shadow-sm hover:bg-fuchsia-900/40 lg:hidden"
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

  <div id="mobileMenu" class="hidden border-t border-fuchsia-500/20 bg-[#0a0f1f] lg:hidden">
    <div class="mx-auto max-w-6xl px-4 py-3 sm:px-6">
      <div class="flex flex-col gap-2">
        <?php foreach ($app['nav'] as $item): ?>
          <a class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-fuchsia-950/50 hover:text-cyan-100" href="<?= htmlspecialchars(nav_url($item['href'])) ?>">
            <?= htmlspecialchars($item['label']) ?>
          </a>
        <?php endforeach; ?>
        <div class="mt-2 grid grid-cols-2 gap-2">
          <a class="rounded-xl border border-cyan-500/25 bg-cyan-950/25 px-3 py-2 text-center text-sm font-semibold text-cyan-100 shadow-sm hover:bg-cyan-900/35" href="<?= htmlspecialchars(nav_url('/login.php')) ?>">
            Login
          </a>
          <a class="rounded-xl bg-gradient-to-r from-fuchsia-600 to-violet-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-lg shadow-fuchsia-900/30 hover:from-fuchsia-500 hover:to-violet-500" href="<?= htmlspecialchars(nav_url($app['cta']['primary']['href'])) ?>">
            <?= htmlspecialchars($app['cta']['primary']['label']) ?>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>
