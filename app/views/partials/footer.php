<?php
/** @var array $app */
?>
<footer id="contact" class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <div class="grid gap-10 md:grid-cols-12">
      <div class="md:col-span-5">
        <div class="flex items-center gap-2">
          <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 text-sm font-semibold text-slate-950">
            <?= htmlspecialchars(substr($app['site']['shortName'], 0, 2)) ?>
          </span>
          <div class="leading-tight">
            <div class="text-sm font-semibold text-slate-50"><?= htmlspecialchars($app['site']['name']) ?></div>
            <div class="text-xs text-slate-400"><?= htmlspecialchars($app['site']['tagline']) ?></div>
          </div>
        </div>

        <p class="mt-4 max-w-md text-sm text-slate-300">
          A homepage-first build. Next we’ll add MySQL + CSV import and real pages (programs, departments, dashboards).
        </p>

        <div class="mt-6 space-y-2 text-sm text-slate-300">
          <div><span class="text-slate-400">Email:</span> admissions@northbridge.test</div>
          <div><span class="text-slate-400">Phone:</span> (555) 010-2030</div>
          <div><span class="text-slate-400">Address:</span> 100 Campus Way, Northbridge</div>
        </div>
      </div>

      <div class="md:col-span-7">
        <div class="grid gap-8 sm:grid-cols-3">
          <div>
            <div class="text-sm font-semibold text-slate-50">Explore</div>
            <ul class="mt-3 space-y-2 text-sm">
              <li><a class="text-slate-300 hover:text-white" href="#programs">Programs</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#departments">Departments</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#news">News</a></li>
            </ul>
          </div>
          <div>
            <div class="text-sm font-semibold text-slate-50">Admissions</div>
            <ul class="mt-3 space-y-2 text-sm">
              <li><a class="text-slate-300 hover:text-white" href="#admissions">How to apply</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#admissions">Tuition & aid</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#admissions">Visit campus</a></li>
            </ul>
          </div>
          <div>
            <div class="text-sm font-semibold text-slate-50">For students</div>
            <ul class="mt-3 space-y-2 text-sm">
              <li><a class="text-slate-300 hover:text-white" href="#">Portal (coming soon)</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#">Academic calendar</a></li>
              <li><a class="text-slate-300 hover:text-white" href="#">Support services</a></li>
            </ul>
          </div>
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-xs text-slate-400">
            © <?= date('Y') ?> <?= htmlspecialchars($app['site']['name']) ?>. All rights reserved.
          </div>
          <div class="flex items-center gap-4 text-xs">
            <a class="text-slate-400 hover:text-slate-200" href="#">Privacy</a>
            <a class="text-slate-400 hover:text-slate-200" href="#">Terms</a>
            <a class="text-slate-400 hover:text-slate-200" href="#">Accessibility</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>

