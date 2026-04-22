<?php
/** @var array $app */
/** @var ?string $error */
/** @var string $csrf */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="mx-auto max-w-md">
      <div class="rounded-3xl border border-white/10 bg-white/5 p-6 sm:p-8">
        <div class="text-sm font-semibold text-sky-200">Login</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">Sign in</h1>
        <p class="mt-2 text-sm text-slate-300">Sign in to access your dashboard.</p>

        <?php if ($error): ?>
          <div class="mt-5 rounded-2xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-100">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" method="post" action="/login">
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
              autocomplete="current-password"
              required
            />
          </div>

          <button class="w-full rounded-xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
            Sign in
          </button>
        </form>

        <div class="mt-5 text-xs text-slate-400">
          Don’t have an account? <a class="font-semibold text-sky-200 hover:text-sky-100" href="/signup">Create one</a>.
        </div>
      </div>
    </div>
  </div>
</section>

