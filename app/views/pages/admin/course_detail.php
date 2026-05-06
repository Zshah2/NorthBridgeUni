<?php
/** @var array $app */
/** @var string $course_id_param */
/** @var array<string, mixed>|null $course */
/** @var list<array<string, mixed>> $prereqs */
/** @var list<array<string, mixed>> $terms_with_offerings */
/** @var int|null $term_id */
/** @var list<array<string, mixed>> $sections */
/** @var list<array<string, mixed>> $roster */
/** @var int|null $highlight_section */

$course_id_param = $course_id_param ?? '';
$course = $course ?? null;
$prereqs = $prereqs ?? [];
$terms_with_offerings = $terms_with_offerings ?? [];
$term_id = $term_id ?? null;
$sections = $sections ?? [];
$roster = $roster ?? [];
$highlight_section = isset($highlight_section) ? (int)$highlight_section : null;

/** @var bool $can_edit_catalog */
$can_edit_catalog = !empty($can_edit_catalog);

$peopleHref = static function (int $sid): string {
    return url('/admin.php?view=people&id=' . $sid);
};

$fmtInstr = static function (?string $first, ?string $last): string {
    $f = trim((string)$first);
    $l = trim((string)$last);
    if ($f === '' && $l === '') {
        return '—';
    }

    return htmlspecialchars(trim($f . ' ' . $l), ENT_QUOTES, 'UTF-8');
};

$statusBadge = static function (string $status): string {
    $s = strtolower(trim($status));

    return match ($s) {
        'enrolled' => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
        'waitlisted' => 'bg-amber-100 text-amber-900 ring-amber-200',
        default => 'bg-slate-100 text-slate-800 ring-slate-200',
    };
};
?>
<?php if ($course === null): ?>
  <h1 class="text-2xl font-semibold text-slate-900">Course</h1>
  <p class="mt-2 text-sm text-slate-600">
    <?php if ($course_id_param === ''): ?>
      No course was specified.
    <?php else: ?>
      No catalog row found for <span class="font-mono font-semibold"><?= htmlspecialchars($course_id_param) ?></span>.
    <?php endif; ?>
  </p>
  <p class="mt-4">
    <a class="text-sm font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=courses')) ?>">← Back to Courses</a>
  </p>
<?php else: ?>
  <?php
      $cid = (string)$course['course_id'];
  ?>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Course record</p>
      <h1 class="mt-1 text-2xl font-semibold text-slate-900">
        <span class="font-mono"><?= htmlspecialchars($cid) ?></span>
        <span class="font-normal text-slate-600"> — <?= htmlspecialchars((string)($course['course_name'] ?? '')) ?></span>
      </h1>
      <p class="mt-2 text-sm text-slate-600">
        <?= (int)($course['credits'] ?? 0) ?> credits ·
        <?= htmlspecialchars((string)($course['dept_name'] ?? $course['dept_id'] ?? '—')) ?>
        <?php if ((int)($course['is_active'] ?? 1) !== 1): ?>
          · <span class="font-semibold text-amber-800">Inactive in catalog</span>
        <?php endif; ?>
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=courses')) ?>">All offerings</a>
      <?php if ($can_edit_catalog): ?>
        <a class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" href="<?= htmlspecialchars(url('/admin.php?view=catalog&edit=' . rawurlencode($cid))) ?>">Edit in catalog</a>
      <?php endif; ?>
    </div>
  </div>

  <?php
      $courseDesc = trim((string)($course['description'] ?? ''));
  ?>
  <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-900">Course information</h2>
    <p class="mt-1 text-xs text-slate-500">Catalog summary — what this course covers and how it fits in the curriculum.</p>
    <?php if ($courseDesc !== ''): ?>
      <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
        <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800"><?= htmlspecialchars($courseDesc) ?></p>
      </div>
    <?php else: ?>
      <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-600">
        No catalog description on file yet.
        <?php if ($can_edit_catalog): ?>
          Add narrative text under <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=catalog&edit=' . rawurlencode($cid))) ?>">Catalog → Edit <?= htmlspecialchars($cid) ?></a> so this page reads well for faculty and accreditation reviews.
        <?php else: ?>
          Ask an administrator to add a description in <strong class="font-semibold text-slate-700">Catalog</strong>.
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-900">Prerequisites</h2>
    <p class="mt-1 text-xs text-slate-500">Required prior coursework (typically with a passing grade) before students may register.</p>
    <?php if ($prereqs === []): ?>
      <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-sm text-slate-600">
        <p>No prerequisites are linked to this course in the catalog.</p>
        <?php if ($can_edit_catalog): ?>
          <p class="mt-2">To showcase prereqs here, open <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=catalog&edit=' . rawurlencode($cid))) ?>">Catalog → Edit <?= htmlspecialchars($cid) ?></a> and save prerequisite courses.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <ul class="mt-4 space-y-4">
        <?php foreach ($prereqs as $p): ?>
          <?php
              $pid = (string)($p['course_id'] ?? '');
              $pname = (string)($p['course_name'] ?? '');
              $pcr = (int)($p['credits'] ?? 0);
              $pdesc = trim((string)($p['prereq_description'] ?? ''));
              $pdept = trim((string)($p['prereq_dept_name'] ?? ''));
              if ($pdept === '') {
                  $pdept = trim((string)($p['prereq_dept_id'] ?? ''));
              }
              $pdeptDisp = $pdept !== '' ? $pdept : '—';
          ?>
          <li class="rounded-xl border border-slate-200 bg-gradient-to-b from-white to-slate-50/90 p-4 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <a class="text-base font-semibold text-indigo-800 hover:text-indigo-950 hover:underline" href="<?= htmlspecialchars(url('/admin.php?' . http_build_query(['view' => 'course', 'course_id' => $pid]))) ?>">
                  <span class="font-mono tracking-tight"><?= htmlspecialchars($pid) ?></span>
                  <span class="font-semibold text-slate-800"> — <?= htmlspecialchars($pname) ?></span>
                </a>
                <p class="mt-1 text-xs font-medium text-slate-500">
                  <?= $pcr ?> credit<?= $pcr === 1 ? '' : 's' ?> · <?= htmlspecialchars($pdeptDisp) ?>
                </p>
              </div>
              <a class="shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline" href="<?= htmlspecialchars(url('/admin.php?' . http_build_query(['view' => 'course', 'course_id' => $pid]))) ?>">View prerequisite course →</a>
            </div>
            <?php if ($pdesc !== ''): ?>
              <div class="mt-3 border-t border-slate-100 pt-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">What this course is</p>
                <p class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-slate-700"><?= htmlspecialchars($pdesc) ?></p>
              </div>
            <?php else: ?>
              <p class="mt-3 text-xs italic text-slate-500">No separate catalog description for this prerequisite — open the course above to see catalog details.</p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <?php if ($terms_with_offerings === []): ?>
    <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-5">
      <h2 class="text-sm font-semibold text-slate-900">Sections</h2>
      <p class="mt-2 text-sm text-slate-600">This course has no scheduled sections yet.</p>
    </div>
  <?php else: ?>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 class="text-sm font-semibold text-slate-900">Sections &amp; roster</h2>
          <p class="mt-1 text-xs text-slate-500">Pick a term to see offerings and enrolled students for this course.</p>
        </div>
        <form method="get" class="flex flex-wrap items-end gap-2">
          <input type="hidden" name="view" value="course" />
          <input type="hidden" name="course_id" value="<?= htmlspecialchars($cid) ?>" />
          <?php if ($highlight_section !== null && $highlight_section > 0): ?>
            <input type="hidden" name="highlight_section" value="<?= (int)$highlight_section ?>" />
          <?php endif; ?>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cd-term">Term</label>
            <select id="cd-term" name="term_id" class="mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" onchange="this.form.submit()">
              <?php foreach ($terms_with_offerings as $t): $tid = (int)($t['term_id'] ?? 0); ?>
                <option value="<?= (int)$tid ?>" <?= $term_id !== null && $tid === $term_id ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)($t['code'] ?? '')) ?> — <?= htmlspecialchars((string)($t['name'] ?? '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <noscript><button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Apply</button></noscript>
        </form>
      </div>

      <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
              <th class="px-4 py-3">Section</th>
              <th class="px-4 py-3">Instructor</th>
              <th class="px-4 py-3">When / where</th>
              <th class="px-4 py-3 text-right">Enrolled</th>
              <th class="px-4 py-3 text-right">Waitlist</th>
              <th class="px-4 py-3 text-right">Cap</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <?php foreach ($sections as $srow): $sid = (int)($srow['section_id'] ?? 0); $isHl = $highlight_section !== null && $highlight_section === $sid; ?>
              <tr id="section-<?= (int)$sid ?>" class="scroll-mt-28 <?= $isHl ? 'bg-indigo-50/80 ring-1 ring-inset ring-indigo-200' : 'hover:bg-slate-50/60' ?>">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-900"><?= (int)$sid ?></td>
                <td class="px-4 py-3"><?= $fmtInstr($srow['fac_first'] ?? null, $srow['fac_last'] ?? null) ?></td>
                <td class="px-4 py-3 text-slate-700">
                  <?= htmlspecialchars(trim((string)($srow['meeting_days'] ?? ''))) ?>
                  <?php if (trim((string)($srow['meeting_time'] ?? '')) !== ''): ?>
                    <span class="text-slate-500"><?= htmlspecialchars(trim((string)($srow['meeting_time'] ?? ''))) ?></span>
                  <?php endif; ?>
                  <?php if (trim((string)($srow['room'] ?? '')) !== ''): ?>
                    <span class="block text-xs text-slate-500"><?= htmlspecialchars((string)$srow['room']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-right tabular-nums"><?= (int)($srow['enrolled_count'] ?? 0) ?></td>
                <td class="px-4 py-3 text-right tabular-nums"><?= (int)($srow['waitlisted_count'] ?? 0) ?></td>
                <td class="px-4 py-3 text-right tabular-nums"><?= htmlspecialchars((string)($srow['capacity'] ?? '—')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-8">
        <h3 class="text-sm font-semibold text-slate-900">Students (<?= count($roster) ?><?= count($roster) >= 500 ? '+' : '' ?>)</h3>
        <p class="mt-1 text-xs text-slate-500">Enrolled and waitlisted for the selected term. Student IDs link to the directory record.</p>
        <?php if ($roster === []): ?>
          <p class="mt-3 text-sm text-slate-600">No enrollments for this term.</p>
        <?php else: ?>
          <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full text-left text-sm">
              <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                <tr>
                  <th class="px-4 py-3">Student ID</th>
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Email</th>
                  <th class="px-4 py-3">Section</th>
                  <th class="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <?php foreach ($roster as $row): $stid = (int)($row['student_id'] ?? 0); ?>
                  <tr class="hover:bg-slate-50/60">
                    <td class="px-4 py-3">
                      <a class="inline-flex rounded-md bg-sky-100 px-2 py-0.5 font-mono text-xs font-semibold tabular-nums text-sky-950 ring-1 ring-inset ring-sky-200/90 hover:bg-sky-200/90" href="<?= htmlspecialchars($peopleHref($stid)) ?>"><?= (int)$stid ?></a>
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900">
                      <?= htmlspecialchars(trim((string)($row['last_name'] ?? '') . ', ' . (string)($row['first_name'] ?? ''))) ?>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($row['email'] ?? '—')) ?></td>
                    <td class="px-4 py-3 font-mono text-xs"><?= (int)($row['section_id'] ?? 0) ?></td>
                    <td class="px-4 py-3">
                      <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset <?= $statusBadge((string)($row['status'] ?? '')) ?>">
                        <?= htmlspecialchars((string)($row['status'] ?? '')) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <script>
    (function () {
      var id = <?= json_encode($highlight_section !== null && $highlight_section > 0 ? 'section-' . $highlight_section : null) ?>;
      if (!id) return;
      var el = document.getElementById(id);
      if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    })();
  </script>
<?php endif; ?>
