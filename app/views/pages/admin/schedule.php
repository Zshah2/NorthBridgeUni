<?php
/** @var array $app */
/** @var array $terms */
/** @var int|null $term_id */
/** @var string $dept_id */
/** @var array $sections */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <?php require view_path('partials/admin_nav.php'); ?>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Admin</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Master schedule</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">Sections by term (read-only). Data comes from <code class="text-sky-200/90">terms</code>, <code class="text-sky-200/90">sections</code>, <code class="text-sky-200/90">courses</code>.</p>
      </div>
    </div>

    <?php if (!$terms): ?>
      <div class="mt-8 rounded-3xl border border-white/10 bg-white/5 px-5 py-6 text-sm text-slate-300">
        No terms in the database yet. Run <code class="text-sky-200/90">php scripts/seed_demo_registration.php</code> after migrate and import.
      </div>
    <?php else: ?>
    <form class="mt-8 flex flex-wrap items-end gap-4 rounded-3xl border border-white/10 bg-white/5 p-5" method="get" action="/admin/schedule">
      <div>
        <label class="block text-sm font-medium text-slate-200" for="term_id">Term</label>
        <select class="mt-2 rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100" id="term_id" name="term_id">
          <?php foreach ($terms as $t): ?>
            <option value="<?= (int)$t['term_id'] ?>" <?= ((int)$t['term_id'] === (int)$term_id) ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['code']) ?> — <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-200" for="dept_id">Dept filter</label>
        <input
          class="mt-2 w-40 rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500"
          id="dept_id"
          name="dept_id"
          value="<?= htmlspecialchars($dept_id) ?>"
          placeholder="e.g. ENG"
        />
      </div>
      <button class="rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
        Apply
      </button>
    </form>

    <?php if (!$sections): ?>
      <div class="mt-8 rounded-3xl border border-white/10 bg-white/5 px-5 py-6 text-sm text-slate-300">
        No sections for this term<?= $dept_id !== '' ? ' and department filter' : '' ?>. Seed demo data or add rows in MySQL.
      </div>
    <?php else: ?>
      <div class="mt-8 overflow-x-auto rounded-3xl border border-white/10">
        <table class="min-w-full divide-y divide-white/10 text-left text-sm">
          <thead class="bg-white/5 text-xs font-semibold uppercase tracking-wide text-slate-400">
            <tr>
              <th class="px-4 py-3">Course</th>
              <th class="px-4 py-3">Section</th>
              <th class="px-4 py-3">Dept</th>
              <th class="px-4 py-3">Faculty</th>
              <th class="px-4 py-3">When / where</th>
              <th class="px-4 py-3">Cap</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/10 text-slate-200">
            <?php foreach ($sections as $row): ?>
              <tr class="hover:bg-white/5">
                <td class="px-4 py-3">
                  <div class="font-semibold text-white"><?= htmlspecialchars($row['course_id']) ?></div>
                  <div class="text-xs text-slate-400"><?= htmlspecialchars($row['course_name']) ?> (<?= (int)$row['credits'] ?> cr)</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs"><?= (int)$row['section_id'] ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars((string)($row['dept_id'] ?? '')) ?></td>
                <td class="px-4 py-3 text-sm">
                  <?= htmlspecialchars(trim(($row['fac_first'] ?? '') . ' ' . ($row['fac_last'] ?? ''))) ?: '—' ?>
                </td>
                <td class="px-4 py-3 text-xs text-slate-300">
                  <?= htmlspecialchars(trim(($row['meeting_days'] ?? '') . ' ' . ($row['meeting_time'] ?? ''))) ?>
                  <?php if (!empty($row['room'])): ?>
                    <span class="text-slate-500">·</span> <?= htmlspecialchars((string)$row['room']) ?>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3"><?= (int)$row['capacity'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
