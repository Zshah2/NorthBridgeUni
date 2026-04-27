<?php
/** @var array $app */
/** @var string $student_id */
/** @var array|null $student */
/** @var array $departments */
/** @var array $enrollments */
/** @var array $holds */
/** @var bool $can_manage_holds */
$can_manage_holds = $can_manage_holds ?? false;
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <?php require view_path('partials/admin_nav.php'); ?>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Admin</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Student detail</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">Live data from MySQL.</p>
      </div>
      <a class="text-sm font-semibold text-slate-200 hover:text-white" href="<?= htmlspecialchars(url('/admin/students/search')) ?>">New search →</a>
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-12">
      <div class="lg:col-span-5">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="text-sm font-semibold text-white">Profile</div>
          <div class="mt-1 text-sm text-slate-400">Student ID: <?= htmlspecialchars($student_id) ?></div>

          <?php if (!$student): ?>
            <div class="mt-5 rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
              No user found for this ID (import `users` and `students` first).
            </div>
          <?php else: ?>
            <div class="mt-5 space-y-2 text-sm text-slate-200">
              <div><span class="text-slate-400">Name:</span> <?= htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name'])) ?></div>
              <div><span class="text-slate-400">Type:</span> <?= htmlspecialchars($student['user_type']) ?></div>
              <div><span class="text-slate-400">DOB:</span> <?= htmlspecialchars((string)($student['dob'] ?? '')) ?></div>
              <div><span class="text-slate-400">Gender:</span> <?= htmlspecialchars((string)($student['gender'] ?? '')) ?></div>
              <div class="pt-2 text-slate-300">
                <div class="text-xs font-semibold text-slate-400">Address</div>
                <div class="mt-1">
                  <?= htmlspecialchars(trim(($student['apt_no'] ?? '') . ' ' . ($student['street'] ?? ''))) ?><br />
                  <?= htmlspecialchars(trim(($student['city'] ?? '') . ', ' . ($student['state'] ?? '') . ' ' . ($student['zip_code'] ?? ''))) ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="flex items-start justify-between gap-3">
            <div class="text-sm font-semibold text-white">Holds</div>
            <?php if ($student && ctype_digit((string)$student_id)): ?>
              <a class="text-xs font-semibold text-sky-200 hover:text-sky-100" href="<?= htmlspecialchars(url('/admin/holds/show?student_id=' . rawurlencode($student_id))) ?>"><?= $can_manage_holds ? 'Manage holds →' : 'View holds →' ?></a>
            <?php endif; ?>
          </div>
          <div class="mt-4 space-y-2">
            <?php if (!$student): ?>
              <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">Look up a valid student to see holds.</div>
            <?php elseif (!$holds): ?>
              <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">No hold rows for this student.</div>
            <?php else: ?>
              <?php foreach ($holds as $h): ?>
                <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                  <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-white"><?= htmlspecialchars($h['hold_type']) ?></span>
                    <?php if ((int)$h['is_active'] === 1): ?>
                      <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-xs font-semibold text-amber-100">Active</span>
                    <?php else: ?>
                      <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-xs font-semibold text-slate-400">Cleared</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($h['note'])): ?>
                    <div class="mt-1 text-xs text-slate-400"><?= htmlspecialchars($h['note']) ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-4 rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="text-sm font-semibold text-white">Departments</div>
          <div class="mt-4 space-y-2">
            <?php if (!$departments): ?>
              <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                No department declarations found.
              </div>
            <?php else: ?>
              <?php foreach ($departments as $d): ?>
                <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3">
                  <div class="text-sm font-semibold text-white"><?= htmlspecialchars($d['dept_name']) ?> <span class="text-slate-400">(<?= htmlspecialchars($d['dept_id']) ?>)</span></div>
                  <div class="mt-1 text-xs text-slate-400">Declared: <?= htmlspecialchars((string)($d['date_of_declaration'] ?? '')) ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="lg:col-span-7">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-white">Enrollments</div>
              <div class="mt-1 text-sm text-slate-300">Requires `terms`, `courses`, `sections`, `enrollments` data.</div>
            </div>
          </div>

          <div class="mt-5 space-y-3">
            <?php if (!$enrollments): ?>
              <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">
                No enrollments found.
              </div>
            <?php else: ?>
              <?php foreach ($enrollments as $e): ?>
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <div class="text-sm font-semibold text-white">
                        <?= htmlspecialchars($e['course_id']) ?> — <?= htmlspecialchars($e['course_name']) ?>
                      </div>
                      <div class="mt-1 text-xs text-slate-400">
                        Term: <?= htmlspecialchars($e['term_code']) ?> (<?= htmlspecialchars($e['term_name']) ?>) · Credits: <?= htmlspecialchars((string)$e['credits']) ?>
                      </div>
                      <div class="mt-1 text-xs text-slate-400">
                        Section #<?= htmlspecialchars((string)$e['section_id']) ?>
                        <?php if ($e['meeting_days'] || $e['meeting_time'] || $e['room']): ?>
                          · <?= htmlspecialchars(trim(($e['meeting_days'] ?? '') . ' ' . ($e['meeting_time'] ?? ''))) ?>
                          <?php if ($e['room']): ?> · Room <?= htmlspecialchars($e['room']) ?><?php endif; ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="text-right">
                      <div class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200">
                        <?= htmlspecialchars($e['status']) ?>
                      </div>
                      <div class="mt-2 text-xs text-slate-400">Added: <?= htmlspecialchars((string)$e['created_at']) ?></div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

