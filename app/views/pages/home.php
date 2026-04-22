<?php
/** @var array $app */
?>
<section class="relative overflow-hidden">
  <div class="pointer-events-none absolute inset-0 -z-10">
    <div class="absolute -top-24 left-1/2 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-sky-500/20 blur-3xl"></div>
    <div class="absolute top-32 left-1/3 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-indigo-500/20 blur-3xl"></div>
  </div>

  <div class="mx-auto max-w-6xl px-4 pt-14 pb-10 sm:px-6 sm:pt-20">
    <div class="grid items-center gap-10 lg:grid-cols-12">
      <div class="lg:col-span-7">
        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-200">
          <span class="inline-block h-1.5 w-1.5 rounded-full bg-sky-400"></span>
          Admissions open for Fall <?= date('Y') ?>
        </div>

        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
          Learn with clarity.
          <span class="bg-gradient-to-r from-sky-300 to-indigo-300 bg-clip-text text-transparent">Build your future</span>.
        </h1>

        <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
          <?= htmlspecialchars($app['site']['description']) ?>
        </p>

        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
          <a href="<?= htmlspecialchars(nav_url($app['cta']['primary']['href'])) ?>" class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-5 py-3 text-sm font-semibold text-slate-950 hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/60">
            <?= htmlspecialchars($app['cta']['primary']['label']) ?>
          </a>
          <a href="#programs" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-semibold text-slate-100 hover:bg-white/10">
            Explore programs
          </a>
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-300">
          <div class="inline-flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">🎓</span>
            Career-focused programs
          </div>
          <div class="inline-flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">🧑‍🏫</span>
            Supportive faculty
          </div>
          <div class="inline-flex items-center gap-2">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/5 ring-1 ring-white/10">🧪</span>
            Hands-on learning
          </div>
        </div>
      </div>

      <div class="lg:col-span-5">
        <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-white/10 to-white/5 p-6 shadow-2xl shadow-sky-500/10">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-white">Next steps</div>
              <div class="mt-1 text-sm text-slate-300">Get started in under 10 minutes.</div>
            </div>
            <span class="rounded-full bg-sky-500/15 px-3 py-1 text-xs font-semibold text-sky-200 ring-1 ring-sky-400/20">Homepage phase</span>
          </div>

          <ol class="mt-5 space-y-3 text-sm">
            <li class="flex gap-3 rounded-2xl border border-white/10 bg-black/20 p-3">
              <span class="grid h-7 w-7 flex-none place-items-center rounded-xl bg-sky-500 text-xs font-bold text-slate-950">1</span>
              <div>
                <div class="font-semibold text-white">Explore programs</div>
                <div class="text-slate-300">See departments and career tracks.</div>
              </div>
            </li>
            <li class="flex gap-3 rounded-2xl border border-white/10 bg-black/20 p-3">
              <span class="grid h-7 w-7 flex-none place-items-center rounded-xl bg-sky-500 text-xs font-bold text-slate-950">2</span>
              <div>
                <div class="font-semibold text-white">Prepare documents</div>
                <div class="text-slate-300">Transcript, ID, and references.</div>
              </div>
            </li>
            <li class="flex gap-3 rounded-2xl border border-white/10 bg-black/20 p-3">
              <span class="grid h-7 w-7 flex-none place-items-center rounded-xl bg-sky-500 text-xs font-bold text-slate-950">3</span>
              <div>
                <div class="font-semibold text-white">Submit application</div>
                <div class="text-slate-300">Fast online form (coming next).</div>
              </div>
            </li>
          </ol>

          <div class="mt-6 grid grid-cols-3 gap-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <div class="text-xs text-slate-400">Students</div>
              <div class="mt-1 text-lg font-semibold text-white">7,800</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <div class="text-xs text-slate-400">Programs</div>
              <div class="mt-1 text-lg font-semibold text-white">48</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
              <div class="text-xs text-slate-400">Clubs</div>
              <div class="mt-1 text-lg font-semibold text-white">120+</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="programs" class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">Programs</div>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Choose a path that fits your goals</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">A balanced mix of fundamentals and real-world projects.</p>
      </div>
      <a href="#admissions" class="text-sm font-semibold text-slate-200 hover:text-white">See admissions →</a>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php
        $cards = [
          ['title' => 'Computer Science', 'desc' => 'Software engineering, data, and systems.'],
          ['title' => 'Business Administration', 'desc' => 'Operations, leadership, and entrepreneurship.'],
          ['title' => 'Health Sciences', 'desc' => 'Hands-on labs and community partnerships.'],
          ['title' => 'Engineering', 'desc' => 'Design, build, test—industry-ready skills.'],
          ['title' => 'Arts & Media', 'desc' => 'Storytelling, design thinking, production.'],
          ['title' => 'Education', 'desc' => 'Modern teaching methods and field practice.'],
        ];
      ?>
      <?php foreach ($cards as $c): ?>
        <div class="group rounded-3xl border border-white/10 bg-white/5 p-5 hover:bg-white/10">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-base font-semibold text-white"><?= htmlspecialchars($c['title']) ?></div>
              <div class="mt-1 text-sm text-slate-300"><?= htmlspecialchars($c['desc']) ?></div>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-black/20 ring-1 ring-white/10">↗</span>
          </div>
          <div class="mt-4 text-sm font-semibold text-sky-200 group-hover:text-sky-100">Learn more</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="departments" class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="grid gap-10 lg:grid-cols-12">
      <div class="lg:col-span-5">
        <div class="text-sm font-semibold text-sky-200">Departments</div>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Support from day one</h2>
        <p class="mt-3 text-sm text-slate-300">
          Advising, tutoring, labs, and student services—organized so you can focus on learning.
        </p>
        <div class="mt-6 grid gap-3">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="text-sm font-semibold text-white">Academic advising</div>
            <div class="mt-1 text-sm text-slate-300">Degree plans, scheduling, and goal tracking.</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="text-sm font-semibold text-white">Student success center</div>
            <div class="mt-1 text-sm text-slate-300">Tutoring, writing lab, and study groups.</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
            <div class="text-sm font-semibold text-white">Career services</div>
            <div class="mt-1 text-sm text-slate-300">Internships, resume review, interview prep.</div>
          </div>
        </div>
      </div>

      <div class="lg:col-span-7">
        <div class="rounded-3xl border border-white/10 bg-gradient-to-b from-white/10 to-white/5 p-6">
          <div class="flex items-center justify-between gap-4">
            <div>
              <div class="text-sm font-semibold text-white">What you’ll get</div>
              <div class="mt-1 text-sm text-slate-300">A clean foundation for your full system.</div>
            </div>
            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-white/10">PHP + JS</span>
          </div>

          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
              <div class="text-sm font-semibold text-white">Reusable layouts</div>
              <div class="mt-1 text-sm text-slate-300">Navbar/footer live once, used everywhere.</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
              <div class="text-sm font-semibold text-white">Config-driven branding</div>
              <div class="mt-1 text-sm text-slate-300">Rename the college in one place.</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
              <div class="text-sm font-semibold text-white">Room for MySQL</div>
              <div class="mt-1 text-sm text-slate-300">Add models + imports without rewrites.</div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-black/20 p-4">
              <div class="text-sm font-semibold text-white">Modern UI</div>
              <div class="mt-1 text-sm text-slate-300">Responsive, accessible, fast.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="admissions" class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="rounded-3xl border border-white/10 bg-gradient-to-r from-sky-500/15 to-indigo-500/15 p-7 sm:p-10">
      <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
        <div class="lg:col-span-7">
          <div class="text-sm font-semibold text-sky-200">Admissions</div>
          <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Apply with confidence</h2>
          <p class="mt-3 text-sm text-slate-300">
            We’ll connect this CTA to a real form once MySQL is ready. For now, the homepage nails the look and structure.
          </p>
          <ul class="mt-5 grid gap-2 text-sm text-slate-200 sm:grid-cols-2">
            <li class="flex items-center gap-2"><span class="text-sky-300">✓</span> Clear requirements</li>
            <li class="flex items-center gap-2"><span class="text-sky-300">✓</span> Fast response</li>
            <li class="flex items-center gap-2"><span class="text-sky-300">✓</span> Financial aid guidance</li>
            <li class="flex items-center gap-2"><span class="text-sky-300">✓</span> Campus visit options</li>
          </ul>
        </div>
        <div class="lg:col-span-5">
          <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
            <div class="text-sm font-semibold text-white">Quick actions</div>
            <div class="mt-4 grid gap-3">
              <a class="rounded-2xl bg-sky-500 px-4 py-3 text-center text-sm font-semibold text-slate-950 hover:bg-sky-400" href="#">
                Start application (next step)
              </a>
              <a class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-slate-100 hover:bg-white/10" href="#news">
                View campus updates
              </a>
              <a class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-center text-sm font-semibold text-slate-100 hover:bg-white/10" href="#contact">
                Contact admissions
              </a>
            </div>
            <div class="mt-4 text-xs text-slate-400">Tip: Later we’ll connect your CSV to MySQL and power real data here.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="news" class="border-t border-white/10 bg-slate-950">
  <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <div class="text-sm font-semibold text-sky-200">News</div>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Latest updates</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-300">Static for now—later it becomes a real MySQL-powered feed.</p>
      </div>
      <a href="#contact" class="text-sm font-semibold text-slate-200 hover:text-white">Subscribe →</a>
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-3">
      <?php
        $news = [
          ['title' => 'New labs opening this semester', 'date' => 'Apr 2026', 'desc' => 'Upgraded spaces for engineering and health sciences.'],
          ['title' => 'Internship week: meet hiring partners', 'date' => 'Mar 2026', 'desc' => 'Resume review, mock interviews, and company booths.'],
          ['title' => 'Student clubs showcase', 'date' => 'Feb 2026', 'desc' => 'Find your community and build leadership skills.'],
        ];
      ?>
      <?php foreach ($news as $n): ?>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-5 hover:bg-white/10">
          <div class="flex items-center justify-between gap-4">
            <div class="text-xs font-semibold text-slate-400"><?= htmlspecialchars($n['date']) ?></div>
            <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-white/10">Update</span>
          </div>
          <h3 class="mt-3 text-base font-semibold text-white"><?= htmlspecialchars($n['title']) ?></h3>
          <p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars($n['desc']) ?></p>
          <div class="mt-4 text-sm font-semibold text-sky-200">Read more</div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

