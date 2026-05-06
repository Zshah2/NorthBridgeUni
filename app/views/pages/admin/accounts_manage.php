<?php
/** @var array<int, array<string, mixed>> $authRows */
/** @var string $csrf */
/** @var int $currentAuthId */
$authRows = $authRows ?? [];
$currentAuthId = (int)($currentAuthId ?? 0);
?>
<h1 class="text-2xl font-semibold text-slate-900">Staff accounts</h1>
<p class="mt-2 text-sm text-slate-600">Portal users for this admin site. Reset passwords and deactivate access (cannot deactivate your own account).</p>

<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full text-left text-sm">
    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
      <tr>
        <th class="px-4 py-3">User</th>
        <th class="px-4 py-3">Role</th>
        <th class="px-4 py-3">Active</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php foreach ($authRows as $a): ?>
        <?php
          $aid = (int)($a['id'] ?? 0);
          $isSelf = $aid === $currentAuthId;
        ?>
        <tr class="hover:bg-slate-50/70">
          <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string)($a['username'] ?? '')) ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars((string)($a['role'] ?? '')) ?></td>
          <td class="px-4 py-3"><?= ((int)($a['is_active'] ?? 1) === 1) ? 'Yes' : 'No' ?></td>
          <td class="px-4 py-3">
            <div class="flex flex-wrap gap-2">
              <form class="inline-flex flex-wrap items-end gap-2" method="post" action="<?= htmlspecialchars(url('/admin.php?view=accounts')) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                <input type="hidden" name="action" value="auth_password_reset" />
                <input type="hidden" name="auth_id" value="<?= $aid ?>" />
                <input type="password" name="new_password" autocomplete="new-password" placeholder="New password (8+)" class="min-w-[10rem] rounded-lg border border-slate-200 px-2 py-1.5 text-xs" />
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Set password</button>
              </form>
              <?php if (!$isSelf): ?>
                <form method="post" action="<?= htmlspecialchars(url('/admin.php?view=accounts')) ?>" onsubmit="return confirm('Change account access?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="action" value="auth_user_active" />
                  <input type="hidden" name="auth_id" value="<?= $aid ?>" />
                  <input type="hidden" name="is_active" value="<?= ((int)($a['is_active'] ?? 1) === 1) ? '0' : '1' ?>" />
                  <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                    <?= ((int)($a['is_active'] ?? 1) === 1) ? 'Deactivate' : 'Reactivate' ?>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="mt-4 text-xs text-slate-500">Creating brand-new staff users still uses the sign-in page “Create account” flow while logged out, or database seeds.</p>
