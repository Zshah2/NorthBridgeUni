<?php
/** @var array $app */
/** @var string $student_id */
/** @var array|null $student */
/** @var array $holds */
/** @var ?string $error */
/** @var string $csrf */
/** @var array $hold_types */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <?php require view_path('partials/admin_nav.php'); ?>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Admin</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Holds for student</h1>
        <p class="mt-2 text-sm text-slate-300">ID: <span class="font-mono text-sky-200/90"><?= htmlspecialchars($student_id) ?></span></p>
      </div>
      <a class="text-sm font-semibold text-slate-200 hover:text-white" href="<?= htmlspecialchars(url('/admin/holds')) ?>">Another student →</a>
    </div>

    <?php if ($error): ?>
      <div class="mt-6 rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-100">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php elseif ($student): ?>
      <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-200">
        <?= htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name'])) ?>
        <span class="text-slate-400">· <?= htmlspecialchars($student['user_type']) ?></span>
      </div>

      <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="text-sm font-semibold text-white">Active and past holds</div>
          <div class="mt-4 space-y-3">
            <?php if (!$holds): ?>
              <div class="rounded-2xl border border-white/10 bg-black/20 px-4 py-3 text-sm text-slate-300">No hold rows for this student.</div>
            <?php else: ?>
              <?php foreach ($holds as $h): ?>
                <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <div class="text-sm font-semibold text-white"><?= htmlspecialchars($h['hold_type']) ?></div>
                      <?php if (!empty($h['note'])): ?>
                        <div class="mt-1 text-xs text-slate-400"><?= htmlspecialchars($h['note']) ?></div>
                      <?php endif; ?>
                      <div class="mt-2 text-xs text-slate-500">Created <?= htmlspecialchars((string)$h['created_at']) ?></div>
                    </div>
                    <div class="text-right">
                      <?php if ((int)$h['is_active'] === 1): ?>
                        <span class="inline-flex rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-100">Active</span>
                        <form class="mt-3 inline" method="post" action="<?= htmlspecialchars(url('/admin/holds/clear')) ?>" onsubmit="return confirm('Clear this hold?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                          <input type="hidden" name="hold_id" value="<?= (int)$h['hold_id'] ?>" />
                          <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>" />
                          <button class="rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-white/10" type="submit">
                            Clear hold
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-300">Cleared</span>
                        <div class="mt-2 text-xs text-slate-500"><?= htmlspecialchars((string)($h['cleared_at'] ?? '')) ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="text-sm font-semibold text-white">Add hold</div>
          <form class="mt-4 space-y-4" method="post" action="<?= htmlspecialchars(url('/admin/holds/add')) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>" />
            <div>
              <label class="block text-sm font-medium text-slate-200" for="hold_type">Type</label>
              <select class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100" id="hold_type" name="hold_type" required>
                <?php foreach ($hold_types as $ht): ?>
                  <option value="<?= htmlspecialchars($ht) ?>"><?= htmlspecialchars($ht) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-200" for="note">Note (optional)</label>
              <textarea class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500" id="note" name="note" rows="3" maxlength="500" placeholder="Short reason shown to staff"></textarea>
            </div>
            <button class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
              Place hold
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
