<?php
/** @var array $app */
/** @var string $student_id */
?>

<h1 class="text-2xl font-semibold text-slate-900 dark:text-white">ID lookup</h1>
<p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Enter an exact student ID to view live data from MySQL.</p>

<div class="mt-8 max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
  <form class="flex flex-col gap-3 sm:flex-row sm:items-end" method="get" action="<?= htmlspecialchars(url('/admin/students/show')) ?>">
    <div class="flex-1">
      <label class="block text-sm font-medium text-slate-700 dark:text-slate-200" for="student_id">Student ID</label>
      <input
        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
        id="student_id"
        name="student_id"
        inputmode="numeric"
        pattern="[0-9]+"
        value="<?= htmlspecialchars($student_id) ?>"
        placeholder="e.g. 123123"
        required
      />
    </div>
    <button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500" type="submit">
      Search
    </button>
  </form>
</div>
