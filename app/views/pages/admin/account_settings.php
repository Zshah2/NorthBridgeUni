<?php
/** @var array<string, mixed> $selfAccount */
/** @var string $csrf */
/** @var bool $isAdmin */
$selfAccount = $selfAccount ?? [];
$isAdmin = (bool)($isAdmin ?? false);
$selfEmail = (string)($selfAccount['email'] ?? '');
$selfName = trim((string)($selfAccount['display_name'] ?? ''));
if ($selfName === '') {
    $selfName = auth_resolve_display_name($selfAccount);
}
?>
<section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Your login</h2>
  <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Name, email, and password used to sign in. Verification codes go to your email when 2FA is enabled.</p>
  <form class="mt-5 max-w-md space-y-4" method="post" action="<?= htmlspecialchars(url('/admin.php?view=settings')) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
    <input type="hidden" name="action" value="auth_self_creds_save" />
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="self_display_name">Your name</label>
      <input
        id="self_display_name"
        name="display_name"
        type="text"
        required
        autocomplete="name"
        value="<?= htmlspecialchars($selfName) ?>"
        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
      />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="self_email">Sign-in email</label>
      <input
        id="self_email"
        name="email"
        type="email"
        required
        autocomplete="email"
        value="<?= htmlspecialchars($selfEmail) ?>"
        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
      />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="self_current_password">Current password</label>
      <input
        id="self_current_password"
        name="current_password"
        type="password"
        required
        autocomplete="current-password"
        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
      />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="self_new_password">New password <span class="font-normal normal-case text-slate-400">(optional)</span></label>
      <input
        id="self_new_password"
        name="new_password"
        type="password"
        autocomplete="new-password"
        minlength="8"
        placeholder="Leave blank to keep current"
        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
      />
    </div>
    <div>
      <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="self_confirm_password">Confirm new password</label>
      <input
        id="self_confirm_password"
        name="confirm_password"
        type="password"
        autocomplete="new-password"
        minlength="8"
        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"
      />
    </div>
    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save your login</button>
  </form>
</section>

<?php if ($isAdmin): ?>
  <p class="mt-4 text-sm text-slate-600">
    To change other users’ logins, open
    <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=accounts')) ?>">Accounts</a>.
  </p>
<?php endif; ?>
