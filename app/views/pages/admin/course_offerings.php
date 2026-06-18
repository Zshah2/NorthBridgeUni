<?php
/** @var array $app */
/** @var list<array<string, mixed>> $terms */
/** @var int|null $term_id */
/** @var list<array<string, mixed>> $dept_rows */
/** @var string $dept_id */
/** @var string $q */
/** @var list<array<string, mixed>> $course_sections */
/** @var int $course_sections_total */
/** @var int $page */
/** @var int $per_page */
/** @var int $total_pages */

$terms = $terms ?? [];
$term_id = $term_id ?? null;
$dept_rows = $dept_rows ?? [];
$dept_id = $dept_id ?? '';
$q = $q ?? '';
$course_sections = $course_sections ?? [];
$course_sections_total = (int)($course_sections_total ?? 0);
$page = max(1, (int)($page ?? 1));
$per_page = (int)($per_page ?? 50);
if (!in_array($per_page, [25, 50, 100, 200], true)) {
    $per_page = 50;
}
$total_pages = max(1, (int)($total_pages ?? 1));

$from = $course_sections_total > 0 ? (($page - 1) * $per_page + 1) : 0;
$to = min($course_sections_total, ($page - 1) * $per_page + count($course_sections));

$pagerUrl = static function (int $p) use ($term_id, $dept_id, $q, $per_page): string {
    $qparams = ['view' => 'courses', 'page' => max(1, $p)];
    if ($term_id !== null) {
        $qparams['term_id'] = (string)$term_id;
    }
    if ($dept_id !== '') {
        $qparams['dept_id'] = $dept_id;
    }
    if ($q !== '') {
        $qparams['q'] = $q;
    }
    if ($per_page !== 50) {
        $qparams['per_page'] = (string)$per_page;
    }

    return url('/admin.php?' . http_build_query($qparams));
};

$fmtInstr = static function (?string $first, ?string $last): string {
    $f = trim((string)$first);
    $l = trim((string)$last);
    if ($f === '' && $l === '') {
        return '—';
    }

    return htmlspecialchars(trim($f . ' ' . $l), ENT_QUOTES, 'UTF-8');
};

/** @var int|null $term_id */
$courseDetailHref = static function (string $courseId, ?int $sectionId = null) use ($term_id): string {
    $q = ['view' => 'course', 'course_id' => trim($courseId)];
    if ($term_id !== null) {
        $q['term_id'] = (string)$term_id;
    }
    if ($sectionId !== null && $sectionId > 0) {
        $q['highlight_section'] = (string)$sectionId;
    }

    return url('/admin.php?' . http_build_query($q));
};
?>
<h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Courses</h1>
<p class="mt-2 text-sm text-slate-600">
  Scheduled sections for the selected term: course, credits, instructor, meeting pattern, and enrollment.
  For the student and faculty directory (ID search), use <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=schedule')) ?>">Master schedule</a>.
</p>

<div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <form class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" method="get">
    <input type="hidden" name="view" value="courses" />
    <div class="sm:w-56">
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="co-term">Term</label>
      <select id="co-term" name="term_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        <?php foreach ($terms as $t): $tid = (int)($t['term_id'] ?? 0); ?>
          <option value="<?= (int)$tid ?>" <?= $term_id !== null && $tid === $term_id ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($t['code'] ?? '')) ?> — <?= htmlspecialchars((string)($t['name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="sm:w-52">
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="co-dept">Department</label>
      <select id="co-dept" name="dept_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        <option value="">All departments</option>
        <?php foreach ($dept_rows as $d): $did = (string)($d['dept_id'] ?? ''); ?>
          <option value="<?= htmlspecialchars($did) ?>" <?= $did !== '' && $did === $dept_id ? 'selected' : '' ?>>
            <?= htmlspecialchars($did) ?> — <?= htmlspecialchars((string)($d['dept_name'] ?? '')) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="min-w-0 flex-1">
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="co-q">Search</label>
      <input id="co-q" name="q" value="<?= htmlspecialchars($q) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Course ID, title, instructor, section ID, room…" />
    </div>
    <div class="sm:w-36">
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="co-per">Rows</label>
      <select id="co-per" name="per_page" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        <?php foreach ([25, 50, 100, 200] as $pp): ?>
          <option value="<?= (int)$pp ?>" <?= $per_page === $pp ? 'selected' : '' ?>><?= (int)$pp ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Apply</button>
    <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=courses')) ?>">Reset</a>
  </form>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="text-sm font-semibold text-slate-800">
      Sections
      <?php if ($course_sections_total > 0): ?>
        <span class="font-normal text-slate-500">· <?= (int)$course_sections_total ?> total · rows <?= (int)$from ?>–<?= (int)$to ?></span>
      <?php endif; ?>
    </div>
    <?php if ($total_pages > 1): ?>
      <nav class="flex flex-wrap items-center gap-1 text-sm" aria-label="Pagination">
        <?php if ($page > 1): ?>
          <a class="rounded-lg border border-slate-200 px-2.5 py-1 font-semibold text-indigo-700 hover:bg-slate-50" href="<?= htmlspecialchars($pagerUrl($page - 1)) ?>">Prev</a>
        <?php endif; ?>
        <span class="px-2 text-slate-600">Page <?= (int)$page ?> / <?= (int)$total_pages ?></span>
        <?php if ($page < $total_pages): ?>
          <a class="rounded-lg border border-slate-200 px-2.5 py-1 font-semibold text-indigo-700 hover:bg-slate-50" href="<?= htmlspecialchars($pagerUrl($page + 1)) ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr>
          <th class="px-4 py-3">Section</th>
          <th class="px-4 py-3">Course</th>
          <th class="px-4 py-3">Credits</th>
          <th class="px-4 py-3">Instructor</th>
          <th class="px-4 py-3">Schedule</th>
          <th class="px-4 py-3">Room</th>
          <th class="px-4 py-3 text-right">Enrolled</th>
          <th class="px-4 py-3 text-right">Cap</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php if ($terms === []): ?>
          <tr><td class="px-4 py-6 text-slate-600" colspan="8">No terms configured yet.</td></tr>
        <?php elseif ($course_sections === []): ?>
          <tr><td class="px-4 py-6 text-slate-600" colspan="8">No sections match these filters.</td></tr>
        <?php else: ?>
          <?php foreach ($course_sections as $r): ?>
            <?php $cidRow = (string)($r['course_id'] ?? ''); $secRow = (int)($r['section_id'] ?? 0); ?>
            <tr class="hover:bg-slate-50/60">
              <td class="px-4 py-3 font-mono text-xs font-semibold">
                <a class="text-indigo-700 hover:text-indigo-900 hover:underline" href="<?= htmlspecialchars($courseDetailHref($cidRow, $secRow > 0 ? $secRow : null)) ?>"><?= $secRow ?></a>
              </td>
              <td class="px-4 py-3">
                <a class="block rounded-lg outline-none ring-indigo-600/0 hover:bg-indigo-50/80 hover:ring-1 hover:ring-indigo-200 focus-visible:ring-2 focus-visible:ring-indigo-500" href="<?= htmlspecialchars($courseDetailHref($cidRow)) ?>">
                  <span class="font-semibold text-indigo-900"><?= htmlspecialchars($cidRow) ?></span>
                  <span class="block text-slate-600"><?= htmlspecialchars((string)($r['course_name'] ?? '')) ?></span>
                </a>
              </td>
              <td class="px-4 py-3 tabular-nums"><?= htmlspecialchars((string)($r['credits'] ?? '—')) ?></td>
              <td class="px-4 py-3"><?= $fmtInstr($r['fac_first'] ?? null, $r['fac_last'] ?? null) ?></td>
              <td class="px-4 py-3 text-slate-700">
                <?= htmlspecialchars(trim((string)($r['meeting_days'] ?? ''))) ?>
                <?php if (trim((string)($r['meeting_time'] ?? '')) !== ''): ?>
                  <span class="text-slate-500"><?= htmlspecialchars(trim((string)($r['meeting_time'] ?? ''))) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3"><?= htmlspecialchars(trim((string)($r['room'] ?? '')) !== '' ? (string)$r['room'] : '—') ?></td>
              <td class="px-4 py-3 text-right tabular-nums"><?= (int)($r['enrolled_count'] ?? 0) ?></td>
              <td class="px-4 py-3 text-right tabular-nums"><?= htmlspecialchars((string)($r['capacity'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
