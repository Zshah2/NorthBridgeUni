<?php
/** @var string $admin_nav_active */
$active = $admin_nav_active ?? '';
$isViewer = auth_is_viewer();
$isLimited = auth_is_limited();

$navItem = static function (string $href, string $label, bool $isActive): string {
    $cls = $isActive
        ? 'block rounded-xl px-3 py-2 font-semibold text-indigo-950 bg-indigo-50 ring-1 ring-indigo-200'
        : 'block rounded-xl px-3 py-2 font-semibold text-slate-700 hover:bg-slate-100';

    return '<a class="' . $cls . '" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a>';
};
?>
<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Navigation</div>
  <nav class="mt-4 space-y-1 text-sm">
    <?= $navItem(url('/admin.php?view=dashboard'), 'Dashboard', $active === 'dashboard') ?>
    <div class="pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">People &amp; directory</div>
    <?= $navItem(url('/admin.php?view=people'), 'ID lookup', $active === 'people') ?>
    <?= $navItem(url('/admin.php?view=schedule'), 'Master schedule', $active === 'schedule') ?>
    <div class="pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Scheduling &amp; enrollment</div>
    <?= $navItem(url('/admin.php?view=courses'), 'Courses', $active === 'courses' || $active === 'course') ?>
    <?= $navItem(url('/admin.php?view=enrollment'), 'Directory', $active === 'enrollment') ?>
    <?= $navItem(url('/admin.php?view=registration'), 'Registration', $active === 'registration') ?>
  </nav>
  <p class="mt-4 text-xs leading-relaxed text-slate-500">
    <?php if ($isViewer): ?>
      <strong class="text-slate-600">Viewer:</strong> browse only. No add/drop or hold changes.
    <?php elseif ($isLimited): ?>
      <strong class="text-slate-600">Limited:</strong> holds, registration add/drop. No grade import.
    <?php else: ?>
      <strong class="text-slate-600">Admin:</strong> full access.
    <?php endif; ?>
  </p>
</div>
