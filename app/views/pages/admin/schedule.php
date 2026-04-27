<?php
/** @var array $app */
/** @var array<string, bool> $schedule_panels */
/** @var bool $schedule_embed_preservation */
/** @var string $search_q */
/** @var string $course_q */
/** @var string $catalog_dept */
/** @var string $sec_q */
/** @var list<string>|array $valid_dept_ids_for_select */
/** @var array $student_rows */
/** @var array $faculty_rows */
/** @var array $terms */
/** @var int|null $term_id */
/** @var string $dept_id */
/** @var array $sections */
/** @var array $dept_rows */
/** @var array $course_rows */
$schedule_panels = $schedule_panels ?? [];
$schedule_embed_preservation = $schedule_embed_preservation ?? false;
$search_q = $search_q ?? '';
$course_q = $course_q ?? '';
$catalog_dept = $catalog_dept ?? '';
$sec_q = $sec_q ?? '';
$student_rows = $student_rows ?? [];
$faculty_rows = $faculty_rows ?? [];
$dept_rows = $dept_rows ?? [];
$course_rows = $course_rows ?? [];
$terms = $terms ?? [];
$sections = $sections ?? [];
$valid_dept_ids_for_select = $valid_dept_ids_for_select ?? [];
/** GET form target: routed URL, or admin.php?view=schedule when embedded in public/admin.php */
$schedule_form_action = isset($schedule_form_action) && is_string($schedule_form_action) && $schedule_form_action !== ''
    ? $schedule_form_action
    : url('/admin/schedule');
$schedule_reset_href = str_contains((string)$schedule_form_action, 'admin.php')
    ? url('/admin.php?view=schedule')
    : url('/admin/schedule');
?>

<h1 class="text-2xl font-semibold text-slate-900">Master schedule</h1>
<p class="mt-2 text-sm text-slate-600">
  Filter students and faculty by ID or name; narrow departments and courses; choose which tables to show; refine sections by term, department, and text (course, instructor, room, time).
</p>

<form class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" method="get" action="<?= htmlspecialchars($schedule_form_action) ?>">
  <input type="hidden" name="sched_filter" value="1" />
  <?php if (($term_id ?? null) !== null): ?>
    <input type="hidden" name="term_id" value="<?= (int)$term_id ?>" />
  <?php endif; ?>
  <?php if (($dept_id ?? '') !== ''): ?>
    <input type="hidden" name="dept_id" value="<?= htmlspecialchars((string)$dept_id, ENT_QUOTES, 'UTF-8') ?>" />
  <?php endif; ?>
  <?php if (($sec_q ?? '') !== ''): ?>
    <input type="hidden" name="sec_q" value="<?= htmlspecialchars((string)$sec_q, ENT_QUOTES, 'UTF-8') ?>" />
  <?php endif; ?>

  <div class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-slate-700">
    <?php foreach (['students' => 'Students', 'faculty' => 'Faculty', 'terms' => 'Terms', 'departments' => 'Departments', 'courses' => 'Courses', 'sections' => 'Sections'] as $pk => $pl): ?>
      <label class="flex cursor-pointer items-center gap-2">
        <input
          type="checkbox"
          name="panels[<?= htmlspecialchars($pk) ?>]"
          value="1"
          class="rounded border-slate-300 text-indigo-600"
          <?= !empty($schedule_panels[$pk]) ? ' checked' : '' ?>
        />
        <?= htmlspecialchars($pl) ?>
      </label>
    <?php endforeach; ?>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="min-w-0 lg:col-span-2">
      <label class="block text-xs font-semibold uppercase text-slate-500" for="schedule-q">People search (students &amp; faculty)</label>
      <input
        type="search"
        id="schedule-q"
        name="q"
        value="<?= htmlspecialchars($search_q) ?>"
        placeholder="ID or name"
        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm"
        autocomplete="off"
      />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase text-slate-500" for="catalog_dept">Catalog department</label>
      <select class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" id="catalog_dept" name="catalog_dept">
        <option value="">All</option>
        <?php foreach ($valid_dept_ids_for_select as $did): ?>
          <option value="<?= htmlspecialchars((string)$did) ?>" <?= (string)$did === $catalog_dept ? ' selected' : '' ?>><?= htmlspecialchars((string)$did) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase text-slate-500" for="course_q">Course catalog search</label>
      <input
        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
        id="course_q"
        name="course_q"
        value="<?= htmlspecialchars($course_q) ?>"
        placeholder="ID or title"
      />
    </div>
  </div>

  <div class="flex flex-wrap items-center gap-3">
    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" type="submit">Apply filters</button>
    <a href="<?= htmlspecialchars($schedule_reset_href) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset everything</a>
  </div>
</form>

<?php if (!empty($schedule_panels['students'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Students <span class="text-sm font-normal text-slate-500">(<?= count($student_rows) ?>)</span></h2>
  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr>
          <th class="px-4 py-3">Student ID</th>
          <th class="px-4 py-3">Last name</th>
          <th class="px-4 py-3">First name</th>
          <th class="px-4 py-3">Middle</th>
          <th class="px-4 py-3">Type</th>
          <th class="px-4 py-3">City</th>
          <th class="px-4 py-3">State</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($student_rows as $r): ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-4 py-3 font-mono font-semibold text-indigo-700"><?= (int)$r['user_id'] ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$r['last_name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$r['first_name']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['middle_name'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$r['user_type']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['city'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['state'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$student_rows): ?>
          <tr>
            <td class="px-4 py-6 text-center text-slate-500" colspan="7">
              <?= $search_q !== '' ? 'No students match this search.' : 'No student rows in the database yet.' ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (!empty($schedule_panels['faculty'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Faculty <span class="text-sm font-normal text-slate-500">(<?= count($faculty_rows) ?>)</span></h2>
  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr>
          <th class="px-4 py-3">Faculty ID</th>
          <th class="px-4 py-3">Last name</th>
          <th class="px-4 py-3">First name</th>
          <th class="px-4 py-3">Middle</th>
          <th class="px-4 py-3">Type</th>
          <th class="px-4 py-3">Office</th>
          <th class="px-4 py-3">Rank</th>
          <th class="px-4 py-3">Faculty type</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($faculty_rows as $r): ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-4 py-3 font-mono font-semibold text-indigo-700"><?= (int)$r['faculty_id'] ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$r['last_name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$r['first_name']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['middle_name'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)$r['user_type']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['office_number'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['faculty_rank'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($r['faculty_type'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$faculty_rows): ?>
          <tr>
            <td class="px-4 py-6 text-center text-slate-500" colspan="8">
              <?= $search_q !== '' ? 'No faculty match this search.' : 'No faculty rows in the database yet.' ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (!empty($schedule_panels['terms'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Terms <span class="text-sm font-normal text-slate-500">(<?= count($terms ?? []) ?>)</span></h2>
  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr><th class="px-4 py-3">Term ID</th><th class="px-4 py-3">Code</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Start</th><th class="px-4 py-3">End</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($terms ?? [] as $tr): ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-4 py-3 font-mono"><?= (int)$tr['term_id'] ?></td>
            <td class="px-4 py-3 font-semibold text-slate-900"><?= htmlspecialchars((string)$tr['code']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$tr['name']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($tr['start_date'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($tr['end_date'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!($terms ?? [])): ?>
          <tr><td class="px-4 py-6 text-center text-slate-500" colspan="5">No terms.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (!empty($schedule_panels['departments'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Departments <span class="text-sm font-normal text-slate-500">(<?= count($dept_rows) ?>)</span></h2>
  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr><th class="px-4 py-3">Dept ID</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Building</th><th class="px-4 py-3">Room</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($dept_rows as $dr): ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-4 py-3 font-mono font-semibold text-indigo-800"><?= htmlspecialchars((string)$dr['dept_id']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$dr['dept_name']) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($dr['email'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($dr['phone_number'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($dr['building_number'] ?? '')) ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($dr['room_number'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$dept_rows): ?>
          <tr><td class="px-4 py-6 text-center text-slate-500" colspan="6">No departments match these filters.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (!empty($schedule_panels['courses'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Courses <span class="text-sm font-normal text-slate-500">(<?= count($course_rows) ?>)</span></h2>
  <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
      <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
        <tr><th class="px-4 py-3">Course ID</th><th class="px-4 py-3">Title</th><th class="px-4 py-3">Credits</th><th class="px-4 py-3">Dept</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($course_rows as $cr): ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-4 py-3 font-mono font-semibold text-indigo-800"><?= htmlspecialchars((string)$cr['course_id']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars((string)$cr['course_name']) ?></td>
            <td class="px-4 py-3"><?= (int)$cr['credits'] ?></td>
            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($cr['dept_id'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$course_rows): ?>
          <tr><td class="px-4 py-6 text-center text-slate-500" colspan="4">No courses match these filters.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($terms === []): ?>
  <div class="mt-10 rounded-2xl border border-slate-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
    No terms in the database yet — offerings cannot be listed. Run migration and seed scripts as needed.
  </div>
<?php elseif (!empty($schedule_panels['sections'])): ?>
  <h2 class="mt-10 text-lg font-semibold text-slate-900">Course sections <span class="text-sm font-normal text-slate-500">(by term)</span></h2>
  <form class="mt-4 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" method="get" action="<?= htmlspecialchars($schedule_form_action) ?>">
    <?php if ($schedule_embed_preservation): ?>
      <input type="hidden" name="sched_filter" value="1" />
      <?php foreach ($schedule_panels as $pk => $on): ?>
        <?php if ($on): ?>
          <input type="hidden" name="panels[<?= htmlspecialchars((string)$pk) ?>]" value="1" />
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if ($search_q !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($search_q, ENT_QUOTES, 'UTF-8') ?>" />
      <?php endif; ?>
      <?php if ($course_q !== ''): ?>
        <input type="hidden" name="course_q" value="<?= htmlspecialchars($course_q, ENT_QUOTES, 'UTF-8') ?>" />
      <?php endif; ?>
      <?php if ($catalog_dept !== ''): ?>
        <input type="hidden" name="catalog_dept" value="<?= htmlspecialchars($catalog_dept, ENT_QUOTES, 'UTF-8') ?>" />
      <?php endif; ?>
    <?php endif; ?>
    <div>
      <label class="block text-xs font-semibold uppercase text-slate-500" for="term_id_sec">Term</label>
      <select class="mt-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" id="term_id_sec" name="term_id" onchange="this.form.submit()">
        <?php foreach ($terms as $t): ?>
          <option value="<?= (int)$t['term_id'] ?>" <?= ((int)$t['term_id'] === (int)$term_id) ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['code']) ?> — <?= htmlspecialchars($t['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase text-slate-500" for="sec_dept_id">Section department</label>
      <select class="mt-1 w-full min-w-[10rem] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" id="sec_dept_id" name="dept_id">
        <option value="">All departments</option>
        <?php foreach ($valid_dept_ids_for_select as $did): ?>
          <option value="<?= htmlspecialchars((string)$did) ?>" <?= (string)$did === $dept_id ? ' selected' : '' ?>><?= htmlspecialchars((string)$did) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="min-w-[14rem] flex-1">
      <label class="block text-xs font-semibold uppercase text-slate-500" for="sec_q">Sections search</label>
      <input
        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
        id="sec_q"
        name="sec_q"
        value="<?= htmlspecialchars($sec_q) ?>"
        placeholder="Course ID, instructor, room, MW 10…"
      />
    </div>
    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500" type="submit">Apply section filters</button>
  </form>

  <?php if (!$sections): ?>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-5 py-6 text-sm text-slate-600 shadow-sm">
      No sections for this term matching<?= ($dept_id !== '' || $sec_q !== '') ? ' these filters.' : '.' ?>
    </div>
  <?php else: ?>
    <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
          <tr>
            <th class="px-4 py-3">Course</th>
            <th class="px-4 py-3">Section</th>
            <th class="px-4 py-3">Dept</th>
            <th class="px-4 py-3">Faculty</th>
            <th class="px-4 py-3">When / where</th>
            <th class="px-4 py-3">Cap</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 text-slate-800">
          <?php foreach ($sections as $row): ?>
            <tr class="hover:bg-slate-50/80">
              <td class="px-4 py-3">
                <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)$row['course_id']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)$row['course_name']) ?> (<?= (int)$row['credits'] ?> cr)</div>
              </td>
              <td class="px-4 py-3 font-mono text-xs"><?= (int)$row['section_id'] ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars((string)($row['dept_id'] ?? '')) ?></td>
              <td class="px-4 py-3 text-sm">
                <?= htmlspecialchars(trim((($row['fac_first'] ?? '') . ' ' . ($row['fac_last'] ?? '')))) ?: '—' ?>
              </td>
              <td class="px-4 py-3 text-xs text-slate-600">
                <?= htmlspecialchars(trim((($row['meeting_days'] ?? '') . ' ' . ($row['meeting_time'] ?? '')))) ?>
                <?php if (!empty($row['room'])): ?>
                  <span class="text-slate-400"> · </span> <?= htmlspecialchars((string)$row['room']) ?>
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
