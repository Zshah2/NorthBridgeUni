<?php
/** @var array $app */
/** @var string $student_id */
?>

<section class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <?php require view_path('partials/admin_nav.php'); ?>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Admin</div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Student lookup</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">Enter an exact `student_id` to view live data.</p>
      </div>
      <a class="text-sm font-semibold text-slate-200 hover:text-white" href="<?= htmlspecialchars(url('/admin')) ?>">Back to dashboard →</a>
    </div>

    <div class="mt-8 max-w-xl rounded-3xl border border-white/10 bg-white/5 p-6">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" method="get" action="<?= htmlspecialchars(url('/admin/students/show')) ?>">
        <div class="flex-1">
          <label class="block text-sm font-medium text-slate-200" for="student_id">Student ID</label>
          <input
            class="mt-2 w-full rounded-xl border border-white/10 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-400/50 focus:outline-none focus:ring-2 focus:ring-sky-400/20"
            id="student_id"
            name="student_id"
            inputmode="numeric"
            pattern="[0-9]+"
            value="<?= htmlspecialchars($student_id) ?>"
            placeholder="e.g. 123485"
            required
          />
        </div>
        <button class="rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400" type="submit">
          Search
        </button>
      </form>
    </div>
  </div>
</section>

