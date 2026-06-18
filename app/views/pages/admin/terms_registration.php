<?php
/** @var array<int, array<string, mixed>> $termsRows */
/** @var string $csrf */
$termsRows = $termsRows ?? [];
?>
<h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Terms &amp; registration windows</h1>
<p class="mt-2 text-sm text-slate-600">Control whether students may register for each term and optional open/close dates. Staff overrides remain available to admins during registration.</p>

<div class="mt-6 space-y-6">
  <?php if (!$termsRows): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-600">No terms found.</div>
  <?php endif; ?>
  <?php foreach ($termsRows as $tr): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <div class="text-lg font-semibold text-slate-900"><?= htmlspecialchars((string)$tr['code']) ?></div>
          <div class="text-sm text-slate-600"><?= htmlspecialchars((string)$tr['name']) ?></div>
        </div>
      </div>
      <form class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" method="post" action="<?= htmlspecialchars(url('/admin.php?view=terms')) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="action" value="term_registration_save" />
        <input type="hidden" name="term_id" value="<?= (int)$tr['term_id'] ?>" />
        <div class="flex items-center gap-2 sm:col-span-2">
          <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
            <input type="checkbox" name="registration_open" value="1" <?= ((int)($tr['registration_open'] ?? 1) === 1) ? 'checked' : '' ?> />
            Registration open
          </label>
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-500" for="rs-<?= (int)$tr['term_id'] ?>">Start date</label>
          <input id="rs-<?= (int)$tr['term_id'] ?>" type="date" name="registration_start" value="<?= htmlspecialchars((string)($tr['registration_start'] ?? '')) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-500" for="re-<?= (int)$tr['term_id'] ?>">End date</label>
          <input id="re-<?= (int)$tr['term_id'] ?>" type="date" name="registration_end" value="<?= htmlspecialchars((string)($tr['registration_end'] ?? '')) ?>" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
        </div>
        <div class="sm:col-span-2 lg:col-span-4">
          <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Save term</button>
        </div>
      </form>
      <p class="mt-3 text-xs text-slate-500">Leave dates blank for no date restriction (only the “open” flag applies).</p>
    </div>
  <?php endforeach; ?>
</div>
