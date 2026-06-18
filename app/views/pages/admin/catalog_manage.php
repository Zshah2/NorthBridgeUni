<?php
/** @var array<int, array<string, mixed>> $catalogCourses */
/** @var array<int, array<string, mixed>> $departments */
/** @var array<string, mixed>|null $editCourse */
/** @var list<string> $editPrereqs */
/** @var list<string> $allCourseIds */
/** @var string $csrf */
$catalogCourses = $catalogCourses ?? [];
$departments = $departments ?? [];
$editCourse = $editCourse ?? null;
$editPrereqs = $editPrereqs ?? [];
$allCourseIds = $allCourseIds ?? [];
?>
<h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Course catalog</h1>
<p class="mt-2 text-sm text-slate-600">Create and edit catalog courses, departments, and prerequisites. Sections (schedule, capacity per offering) are managed under <strong class="font-semibold">Courses</strong> (master schedule).</p>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <h2 class="text-sm font-semibold text-slate-900"><?= $editCourse ? 'Edit course' : 'Add course' ?></h2>
  <form class="mt-4 grid gap-4 md:grid-cols-2" method="post" action="<?= htmlspecialchars(url('/admin.php?view=catalog')) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
    <input type="hidden" name="action" value="catalog_course_save" />
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cat-course-id">Course ID</label>
      <input id="cat-course-id" name="course_id" value="<?= htmlspecialchars((string)($editCourse['course_id'] ?? '')) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-sm uppercase" placeholder="CS101" <?= $editCourse ? 'readonly' : '' ?> required />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cat-name">Course name</label>
      <input id="cat-name" name="course_name" value="<?= htmlspecialchars((string)($editCourse['course_name'] ?? '')) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cat-cr">Credits</label>
      <input id="cat-cr" name="credits" type="number" min="0" max="30" value="<?= (int)($editCourse['credits'] ?? 3) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cat-dept">Department</label>
      <select id="cat-dept" name="dept_id" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        <option value="">— None —</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= htmlspecialchars((string)$d['dept_id']) ?>" <?= (($editCourse['dept_id'] ?? '') === (string)$d['dept_id']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$d['dept_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="cat-desc">Description</label>
      <textarea id="cat-desc" name="description" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Optional catalog description"><?= htmlspecialchars((string)($editCourse['description'] ?? '')) ?></textarea>
    </div>
    <div class="md:col-span-2 flex flex-wrap items-center gap-4">
      <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" <?= ((int)($editCourse['is_active'] ?? 1) === 1) ? 'checked' : '' ?> />
        Active in catalog
      </label>
      <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save course</button>
      <?php if ($editCourse): ?>
        <a class="text-sm font-semibold text-slate-600 hover:text-slate-900" href="<?= htmlspecialchars(url('/admin.php?view=catalog')) ?>">Cancel edit</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ($editCourse): ?>
  <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <h2 class="text-sm font-semibold text-slate-900">Prerequisites for <?= htmlspecialchars((string)$editCourse['course_id']) ?></h2>
    <p class="mt-1 text-xs text-slate-500">Select courses that must be completed (with a passing grade) before enrolling in this course.</p>
    <form class="mt-4" method="post" action="<?= htmlspecialchars(url('/admin.php?view=catalog&edit=' . rawurlencode((string)$editCourse['course_id']))) ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
      <input type="hidden" name="action" value="catalog_prereqs_save" />
      <input type="hidden" name="course_id" value="<?= htmlspecialchars((string)$editCourse['course_id']) ?>" />
      <div class="max-h-64 overflow-y-auto rounded-xl border border-slate-200 p-3">
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($allCourseIds as $pcid): ?>
            <?php if ($pcid === (string)$editCourse['course_id']) {
                continue;
            } ?>
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="prereq_ids[]" value="<?= htmlspecialchars($pcid) ?>" <?= in_array($pcid, $editPrereqs, true) ? 'checked' : '' ?> />
              <span class="font-mono text-xs"><?= htmlspecialchars($pcid) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="mt-4 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save prerequisites</button>
    </form>
  </div>
<?php endif; ?>

<div class="mt-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <table class="min-w-full text-left text-sm">
    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
      <tr>
        <th class="px-4 py-3">ID</th>
        <th class="px-4 py-3">Name</th>
        <th class="px-4 py-3">Cr</th>
        <th class="px-4 py-3">Dept</th>
        <th class="px-4 py-3">Active</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php foreach ($catalogCourses as $c): ?>
        <tr class="hover:bg-slate-50/70">
          <td class="px-4 py-3 font-mono text-xs font-semibold">
            <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?' . http_build_query(['view' => 'course', 'course_id' => (string)$c['course_id']]))) ?>"><?= htmlspecialchars((string)$c['course_id']) ?></a>
          </td>
          <td class="px-4 py-3">
            <a class="text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?' . http_build_query(['view' => 'course', 'course_id' => (string)$c['course_id']]))) ?>"><?= htmlspecialchars((string)$c['course_name']) ?></a>
          </td>
          <td class="px-4 py-3"><?= (int)$c['credits'] ?></td>
          <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string)($c['dept_name'] ?? '—')) ?></td>
          <td class="px-4 py-3"><?= (int)($c['is_active'] ?? 1) === 1 ? 'Yes' : 'No' ?></td>
          <td class="px-4 py-3 text-right">
            <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=catalog&edit=' . rawurlencode((string)$c['course_id']))) ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
