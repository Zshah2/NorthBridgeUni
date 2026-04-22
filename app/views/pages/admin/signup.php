<?php
/** @var array $app */
/** @var ?string $error */
/** @var string $csrf */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="mx-auto max-w-md">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-6 sm:p-8">
        <div class="flex flex-wrap items-center gap-2">
          <div class="text-sm font-semibold text-sky-200">Staff</div>
          <span class="rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Admin</span>
        </div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">Create account</h1>
        <p class="mt-2 text-sm text-slate-300">Creates an <strong class="font-semibold text-slate-200">admin</strong> user in the database so you can open the staff dashboard. Password must be at least 8 characters.</p>

        <?php if ($error): ?>
          <div class="mt-5 rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="<?= htmlspecialchars(url('/signup')) ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <div>
            <label class="block text-sm font-medium text-slate-200" for="username">Username</label>
            <input
              class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
              id="username"
              name="username"
              autocomplete="username"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-200" for="password">Password</label>
            <input
              class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
              id="password"
              name="password"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-200" for="confirm_password">Confirm password</label>
            <input
              class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
              id="confirm_password"
              name="confirm_password"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            />
          </div>

          <button class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
            Create admin account
          </button>
        </form>

        <div class="mt-5 flex flex-col gap-2 text-xs text-slate-400">
          <div>Already have an account? <a class="font-semibold text-sky-200 hover:text-sky-100" href="<?= htmlspecialchars(url('/login')) ?>">Sign in</a>.</div>
          <div><a class="font-semibold text-slate-300 hover:text-white" href="<?= htmlspecialchars(url('/')) ?>">Back to college site</a></div>
        </div>
      </div>
    </div>
  </div>
</section>

