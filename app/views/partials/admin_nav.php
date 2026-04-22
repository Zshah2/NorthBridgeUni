<?php
/** @var array $app */
?>
<div class="mb-8 flex flex-wrap items-center gap-2 border-b border-white/10 pb-4">
  <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white" href="<?= htmlspecialchars(url('/admin')) ?>">Dashboard</a>
  <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white" href="<?= htmlspecialchars(url('/admin/students/search')) ?>">Students</a>
  <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white" href="<?= htmlspecialchars(url('/admin/schedule')) ?>">Schedule</a>
  <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 hover:text-white" href="<?= htmlspecialchars(url('/admin/holds')) ?>">Holds</a>
</div>
