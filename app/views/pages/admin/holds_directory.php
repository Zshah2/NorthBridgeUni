<?php
/** @var array<int, array<string, mixed>> $holdRows */
$holdRows = $holdRows ?? [];
?>
<h1 class="text-2xl font-semibold text-slate-900">Active holds</h1>
<p class="mt-2 text-sm text-slate-600">Students with at least one active hold — registration may be blocked until cleared.</p>

<div id="admin-active-holds-list" class="scroll-mt-28 mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-left text-sm">
    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
      <tr>
        <th class="px-4 py-3">Student ID</th>
        <th class="px-4 py-3">Name</th>
        <th class="px-4 py-3">Hold type</th>
        <th class="px-4 py-3">Note</th>
        <th class="px-4 py-3">Since</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php foreach ($holdRows as $h): ?>
        <tr class="hover:bg-slate-50/70">
          <td class="px-4 py-3 font-mono text-xs">
            <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$h['student_id'] . '&people_panel=hold')) ?>"><?= (int)$h['student_id'] ?></a>
          </td>
          <?php $holdName = trim((string)($h['first_name'] ?? '') . ' ' . (string)($h['last_name'] ?? '')); ?>
          <td class="px-4 py-3"><?= $holdName !== '' ? htmlspecialchars($holdName) : '<span class="text-slate-400">—</span>' ?></td>
          <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string)($h['hold_type'] ?? '')) ?></td>
          <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($h['note'] ?? '')) ?></td>
          <td class="px-4 py-3 text-xs text-slate-500"><?= htmlspecialchars((string)($h['created_at'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$holdRows): ?>
        <tr><td class="px-4 py-8 text-center text-slate-500" colspan="5">No active holds.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
