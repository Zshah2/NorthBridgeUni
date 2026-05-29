<?php
/** Optional extra classes on the button element. */
$themeToggleClass = isset($themeToggleClass) && is_string($themeToggleClass) ? $themeToggleClass : '';
?>
<button
  type="button"
  class="theme-toggle inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200/80 bg-white/90 text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white <?= htmlspecialchars($themeToggleClass) ?>"
  aria-label="Toggle light or dark theme"
  title="Light / dark theme"
>
  <svg class="theme-ic-moon h-[1.125rem] w-[1.125rem] dark:hidden" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M21 12.8A8.5 8.5 0 0 1 11.2 3a6.5 6.5 0 1 0 9.8 9.8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>
  <svg class="theme-ic-sun hidden h-[1.125rem] w-[1.125rem] dark:block" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 18a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z" stroke="currentColor" stroke-width="2"/>
    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  </svg>
</button>
