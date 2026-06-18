<?php

declare(strict_types=1);

/** Page title (h1). */
function ui_h1(): string
{
    return 'text-2xl font-semibold text-slate-900 dark:text-white';
}

/** Card / panel surface. */
function ui_card(string $extra = ''): string
{
    $base = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900';

    return trim($base . ' ' . $extra);
}

/** Secondary outline button. */
function ui_btn_secondary(): string
{
    return 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700';
}

/** Muted body text. */
function ui_muted(): string
{
    return 'text-sm text-slate-600 dark:text-slate-400';
}

/** Table wrapper. */
function ui_table_wrap(): string
{
    return 'overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900';
}

/** Table header row. */
function ui_thead(): string
{
    return 'border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400';
}

/** Alert pill on dashboard (amber). */
function ui_alert_pill(): string
{
    return 'rounded-full bg-amber-50 px-3 py-1.5 font-semibold text-amber-950 ring-1 ring-amber-200 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-100 dark:ring-amber-800 dark:hover:bg-amber-900/50';
}
