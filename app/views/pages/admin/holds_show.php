<?php
/** @var array $app */
/** @var string $student_id */
/** @var array|null $student */
/** @var array $holds */
/** @var ?string $error */
/** @var string $csrf */
/** @var array $hold_types */
/** @var bool $can_manage_holds */
$can_manage_holds = $can_manage_holds ?? false;
?>

<div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Holds for student</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">ID: <span class="font-mono font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($student_id) ?></span></p>
  </div>
  <a class="text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300" href="<?= htmlspecialchars(url('/admin/holds')) ?>">Another student →</a>
</div>

<?php if ($error): ?>
  <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-950 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-100">
    <?= htmlspecialchars($error) ?>
  </div>
<?php elseif ($student): ?>
  <div class="mt-6 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
    <?= htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name'])) ?>
    <span class="text-slate-500">· <?= htmlspecialchars($student['user_type']) ?></span>
  </div>

  <div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="text-sm font-semibold text-slate-900 dark:text-white">Active and past holds</div>
      <div class="mt-4 space-y-3">
        <?php if (!$holds): ?>
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">No hold rows for this student.</div>
        <?php else: ?>
          <?php foreach ($holds as $h): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <div class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($h['hold_type']) ?></div>
                  <?php if (!empty($h['note'])): ?>
                    <div class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($h['note']) ?></div>
                  <?php endif; ?>
                  <div class="mt-2 text-xs text-slate-500">Created <?= htmlspecialchars((string)$h['created_at']) ?></div>
                </div>
                <div class="text-right">
                  <?php if ((int)$h['is_active'] === 1): ?>
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-950/60 dark:text-amber-200">Active</span>
                    <?php if ($can_manage_holds): ?>
                    <form class="mt-3 inline" method="post" action="<?= htmlspecialchars(url('/admin/holds/clear')) ?>" onsubmit="return confirm('Clear this hold?');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                      <input type="hidden" name="hold_id" value="<?= (int)$h['hold_id'] ?>" />
                      <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>" />
                      <button class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" type="submit">
                        Clear hold
                      </button>
                    </form>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">Cleared</span>
                    <div class="mt-2 text-xs text-slate-500"><?= htmlspecialchars((string)($h['cleared_at'] ?? '')) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="text-sm font-semibold text-slate-900 dark:text-white">Add hold</div>
      <?php if ($can_manage_holds): ?>
      <form class="mt-4 space-y-4" method="post" action="<?= htmlspecialchars(url('/admin/holds/add')) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>" />
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" for="hold_type">Type</label>
          <select class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" id="hold_type" name="hold_type" required>
            <?php foreach ($hold_types as $ht): ?>
              <option value="<?= htmlspecialchars($ht) ?>"><?= htmlspecialchars($ht) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" for="note">Note (optional)</label>
          <textarea class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100" id="note" name="note" rows="3" maxlength="500" placeholder="Short reason shown to staff"></textarea>
        </div>
        <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" type="submit">
          Place hold
        </button>
      </form>
      <?php else: ?>
      <p class="mt-3 text-sm text-slate-500">Your role can view holds only. Ask an admin or limited staff member to add or clear holds.</p>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
