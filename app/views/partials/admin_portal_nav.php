<?php
/** @var string|null $admin_nav_active */
/** @var string|null $admin_nav_layout */
$active = $admin_nav_active ?? '';
$layout = $admin_nav_layout ?? 'horizontal';

$links = [
    ['dashboard', url('/admin'), 'Dashboard'],
    ['lookup', url('/admin/students/search'), 'ID lookup'],
    ['schedule', url('/admin/schedule'), 'Schedule'],
    ['holds', url('/admin/holds'), 'Holds'],
];

$linkClass = static function (bool $isActive) use ($layout): string {
    if ($layout === 'horizontal') {
        return $isActive
            ? 'rounded-lg px-3 py-2 text-sm font-semibold text-indigo-950 bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-100 dark:ring-indigo-500/30'
            : 'rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/5 dark:hover:text-white';
    }

    return $isActive
        ? 'block rounded-xl px-3 py-2 font-semibold text-indigo-950 bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-100 dark:ring-indigo-500/30'
        : 'block rounded-xl px-3 py-2 font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5';
};
?>
<?php if ($layout === 'horizontal'): ?>
<nav class="hidden min-w-0 flex-1 items-center justify-center gap-1 lg:flex" aria-label="Admin">
  <?php foreach ($links as [$key, $href, $label]): ?>
    <a class="<?= $linkClass($active === $key) ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php else: ?>
<nav class="space-y-1 text-sm" aria-label="Admin">
  <?php foreach ($links as [$key, $href, $label]): ?>
    <a class="<?= $linkClass($active === $key) ?>" href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
