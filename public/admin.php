<?php

declare(strict_types=1);

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/bootstrap.php';
bootstrap_app();
require __DIR__ . '/../app/lib/url.php';
require __DIR__ . '/../app/lib/db.php';
require __DIR__ . '/../app/lib/auth.php';
require __DIR__ . '/../app/lib/csrf.php';

header('Content-Type: text/html; charset=utf-8');

auth_start_session();
auth_require_portal_user();

$user = (string)($_SESSION['auth']['username'] ?? '');
$isAdmin = auth_is_admin();
$isLimited = auth_is_limited();
$isViewer = auth_is_viewer();
$roleLabel = $isViewer ? 'Viewer' : ($isLimited ? 'Limited Admin' : 'Admin');
$canRegister = auth_can_manage_registration();
$canManageHolds = auth_can_manage_holds();
$canPostGrades = $isAdmin;

$csrf = csrf_token();
$pageTitle = 'Administration — Northbridge College';
$pdo = db();

$view = (string)($_GET['view'] ?? 'dashboard');
$validViews = ['dashboard', 'people', 'schedule', 'enrollment', 'registration'];
if (!in_array($view, $validViews, true)) {
    $view = 'dashboard';
}

$peopleIdRaw = trim((string)($_GET['id'] ?? ''));
$peopleId = ctype_digit($peopleIdRaw) ? (int)$peopleIdRaw : null;

$currentTerm = null;
$currentTermCode = null;
$currentTermId = null;
try {
    $currentTerm = $pdo->query('SELECT term_id, code, name FROM terms ORDER BY start_date DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($currentTerm) {
        $currentTermCode = (string)$currentTerm['code'];
        $currentTermId = (int)$currentTerm['term_id'];
    }
} catch (Throwable) {
}

function admin_audit(PDO $pdo, string $action, string $details): void
{
    auth_start_session();
    $aid = (int)($_SESSION['auth']['id'] ?? 0);
    if ($aid < 1) {
        return;
    }
    try {
        $pdo->prepare('INSERT INTO admin_audit_log (admin_auth_id, action, details) VALUES (?, ?, ?)')->execute([$aid, $action, $details]);
    } catch (Throwable) {
    }
}

function schedule_conflicts(?string $daysA, ?string $timeA, ?string $daysB, ?string $timeB): bool
{
    $daysA = strtoupper(trim((string)$daysA));
    $daysB = strtoupper(trim((string)$daysB));
    $timeA = trim((string)$timeA);
    $timeB = trim((string)$timeB);
    if ($daysA === '' || $daysB === '' || $timeA === '' || $timeB === '') {
        return false;
    }
    $setA = array_unique(str_split(preg_replace('/[^MTWRFSU]/', '', $daysA) ?? ''));
    $setB = array_unique(str_split(preg_replace('/[^MTWRFSU]/', '', $daysB) ?? ''));
    if (!$setA || !$setB || !array_intersect($setA, $setB)) {
        return false;
    }
    $parse = static function (string $t): ?array {
        if (!preg_match('/^\s*(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})\s*$/', $t, $m)) {
            return null;
        }
        $s = ((int)$m[1]) * 60 + (int)$m[2];
        $e = ((int)$m[3]) * 60 + (int)$m[4];

        return $e <= $s ? null : [$s, $e];
    };
    $a = $parse($timeA);
    $b = $parse($timeB);

    return $a !== null && $b !== null && $a[0] < $b[1] && $b[0] < $a[1];
}

// POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($isViewer) {
        header('Location: ' . url('/admin.php?view=' . rawurlencode($view) . '&msg=readonly'));
        exit;
    }
    if ($isLimited) {
        $blocked = ['grade_upsert'];
        $act = (string)($_POST['action'] ?? '');
        if (in_array($act, $blocked, true)) {
            header('Location: ' . url('/admin.php?view=' . rawurlencode($view) . '&msg=forbidden'));
            exit;
        }
    }

    csrf_require_valid();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'hold_clear' && $canManageHolds) {
        $holdId = isset($_POST['hold_id']) && ctype_digit((string)$_POST['hold_id']) ? (int)$_POST['hold_id'] : null;
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        if ($holdId !== null && $sid !== null) {
            $pdo->prepare('UPDATE student_holds SET is_active = 0, cleared_at = CURRENT_TIMESTAMP WHERE hold_id = ? AND student_id = ? AND is_active = 1')->execute([$holdId, $sid]);
        }
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&sub=holds'));
        exit;
    }
    if ($action === 'hold_clear_people' && $canManageHolds) {
        $holdId = isset($_POST['hold_id']) && ctype_digit((string)$_POST['hold_id']) ? (int)$_POST['hold_id'] : null;
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        if ($holdId !== null && $sid !== null) {
            $pdo->prepare('UPDATE student_holds SET is_active = 0, cleared_at = CURRENT_TIMESTAMP WHERE hold_id = ? AND student_id = ? AND is_active = 1')->execute([$holdId, $sid]);
        }
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&sub=holds'));
        exit;
    }
    if ($action === 'hold_add_people' && $canManageHolds) {
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $type = trim((string)($_POST['hold_type'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));
        if ($sid !== null && $type !== '') {
            $pdo->prepare('INSERT INTO student_holds (student_id, hold_type, note, is_active) VALUES (?, ?, ?, 1)')->execute([$sid, $type, $note !== '' ? $note : null]);
            admin_audit($pdo, 'hold_add', 'student_id=' . $sid);
        }
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&sub=holds'));
        exit;
    }

    if ($action === 'reg_add' && $canRegister) {
        $studentId = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $sectionId = isset($_POST['section_id']) && ctype_digit((string)$_POST['section_id']) ? (int)$_POST['section_id'] : null;
        $termCode = trim((string)($_POST['term'] ?? ''));
        if ($studentId === null || $sectionId === null || $termCode === '') {
            header('Location: ' . url('/admin.php?view=registration&msg=invalid'));
            exit;
        }
        $hc = $pdo->prepare('SELECT 1 FROM student_holds WHERE student_id = ? AND is_active = 1 LIMIT 1');
        $hc->execute([$studentId]);
        if ($hc->fetchColumn()) {
            header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=hold'));
            exit;
        }
        $sec = $pdo->prepare('
          SELECT s.section_id, s.term_id, s.course_id, s.meeting_days, s.meeting_time, s.capacity, c.credits, t.code AS term_code
          FROM sections s
          JOIN courses c ON c.course_id = s.course_id
          JOIN terms t ON t.term_id = s.term_id
          WHERE s.section_id = ? LIMIT 1
        ');
        $sec->execute([$sectionId]);
        $section = $sec->fetch(PDO::FETCH_ASSOC);
        if (!$section || (string)$section['term_code'] !== $termCode) {
            header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=wrongterm'));
            exit;
        }
        $termId = (int)$section['term_id'];
        $courseId = (string)$section['course_id'];
        $credits = (int)$section['credits'];

        $dup = $pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = ? AND section_id = ? AND status IN ("enrolled","waitlisted") LIMIT 1');
        $dup->execute([$studentId, $sectionId]);
        if ($dup->fetchColumn()) {
            header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=duplicate'));
            exit;
        }
        $dc = $pdo->prepare('
          SELECT 1 FROM enrollments e JOIN sections s ON s.section_id = e.section_id
          WHERE e.student_id = ? AND s.term_id = ? AND s.course_id = ? AND e.status IN ("enrolled","waitlisted") LIMIT 1
        ');
        $dc->execute([$studentId, $termId, $courseId]);
        if ($dc->fetchColumn()) {
            header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=dupecourse'));
            exit;
        }

        $ex = $pdo->prepare('
          SELECT s.meeting_days, s.meeting_time FROM enrollments e
          JOIN sections s ON s.section_id = e.section_id
          WHERE e.student_id = ? AND e.status = "enrolled" AND s.term_id = ?
        ');
        $ex->execute([$studentId, $termId]);
        foreach ($ex->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (schedule_conflicts($section['meeting_days'] ?? null, $section['meeting_time'] ?? null, $row['meeting_days'] ?? null, $row['meeting_time'] ?? null)) {
                header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=conflict'));
                exit;
            }
        }

        $maxCredits = 18;
        try {
            $mx = $pdo->prepare('SELECT max_credit FROM ug_credit_limits WHERE student_id = ? LIMIT 1');
            $mx->execute([$studentId]);
            $v = $mx->fetchColumn();
            if ($v !== false && $v !== null && is_numeric($v) && (int)$v > 0) {
                $maxCredits = (int)$v;
            }
        } catch (Throwable) {
        }
        $cur = $pdo->prepare('
          SELECT COALESCE(SUM(c.credits),0) FROM enrollments e
          JOIN sections s ON s.section_id = e.section_id
          JOIN courses c ON c.course_id = s.course_id
          WHERE e.student_id = ? AND e.status = "enrolled" AND s.term_id = ?
        ');
        $cur->execute([$studentId, $termId]);
        $currentCredits = (int)$cur->fetchColumn();
        if ($currentCredits + $credits > $maxCredits) {
            header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=credit'));
            exit;
        }

        try {
            $pre = $pdo->prepare('SELECT prereq_course_id FROM course_prereqs WHERE course_id = ?');
            $pre->execute([$courseId]);
            $prereqs = $pre->fetchAll(PDO::FETCH_COLUMN);
            if ($prereqs) {
                $in = implode(',', array_fill(0, count($prereqs), '?'));
                $stmt = $pdo->prepare("SELECT DISTINCT course_id FROM student_course_results WHERE student_id = ? AND course_id IN ($in) AND grade_points > 0");
                $stmt->execute(array_merge([$studentId], $prereqs));
                $done = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $missing = array_values(array_diff($prereqs, $done));
                if ($missing) {
                    header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=prereq'));
                    exit;
                }
            }
        } catch (Throwable) {
        }

        $cnt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE section_id = ? AND status = "enrolled"');
        $cnt->execute([$sectionId]);
        $enrolled = (int)$cnt->fetchColumn();
        $cap = (int)$section['capacity'];
        $status = ($enrolled >= $cap) ? 'waitlisted' : 'enrolled';
        $pdo->prepare('INSERT INTO enrollments (student_id, section_id, status) VALUES (?, ?, ?)')->execute([$studentId, $sectionId, $status]);
        admin_audit($pdo, 'reg_' . $status, 'student=' . $studentId . ';section=' . $sectionId);
        header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=' . ($status === 'enrolled' ? 'enrolled' : 'waitlisted')));
        exit;
    }

    if ($action === 'reg_drop' && $canRegister) {
        $studentId = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $sectionId = isset($_POST['section_id']) && ctype_digit((string)$_POST['section_id']) ? (int)$_POST['section_id'] : null;
        $termCode = trim((string)($_POST['term'] ?? ''));
        if ($studentId === null || $sectionId === null) {
            header('Location: ' . url('/admin.php?view=registration&msg=invalid'));
            exit;
        }
        $pdo->prepare('DELETE FROM enrollments WHERE student_id = ? AND section_id = ? AND status IN ("enrolled","waitlisted")')->execute([$studentId, $sectionId]);

        $cnt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE section_id = ? AND status = "enrolled"');
        $cnt->execute([$sectionId]);
        $enrolled = (int)$cnt->fetchColumn();
        $capStmt = $pdo->prepare('SELECT capacity FROM sections WHERE section_id = ?');
        $capStmt->execute([$sectionId]);
        $cap = (int)$capStmt->fetchColumn();
        if ($enrolled < $cap) {
            $w = $pdo->prepare('SELECT enrollment_id, student_id FROM enrollments WHERE section_id = ? AND status = "waitlisted" ORDER BY created_at ASC LIMIT 1');
            $w->execute([$sectionId]);
            $row = $w->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $pdo->prepare('UPDATE enrollments SET status = "enrolled" WHERE enrollment_id = ?')->execute([(int)$row['enrollment_id']]);
            }
        }
        admin_audit($pdo, 'reg_drop', 'student=' . $studentId . ';section=' . $sectionId);
        header('Location: ' . url('/admin.php?view=registration&student_id=' . $studentId . '&term=' . rawurlencode($termCode) . '&msg=dropped'));
        exit;
    }

    header('Location: ' . url('/admin.php?view=' . rawurlencode($view)));
    exit;
}

$counts = ['students' => 0, 'faculty' => 0, 'holds_active' => 0];
try {
    $counts['students'] = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $counts['faculty'] = (int)$pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
    $counts['holds_active'] = (int)$pdo->query('SELECT COUNT(*) FROM student_holds WHERE is_active = 1')->fetchColumn();
} catch (Throwable) {
}

function nav_item(string $href, string $label, bool $active): string
{
    $cls = $active
        ? 'block rounded-xl px-3 py-2 font-semibold text-indigo-950 bg-indigo-50 ring-1 ring-indigo-200'
        : 'block rounded-xl px-3 py-2 font-semibold text-slate-700 hover:bg-slate-100';

    return '<a class="' . $cls . '" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a>';
}

?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
  <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['DM Sans', 'system-ui', 'sans-serif'] } } } };</script>
</head>
<body class="min-h-full bg-slate-50 font-sans text-slate-900 antialiased">
  <header class="relative border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
      <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 text-sm font-bold text-white">NB</span>
        <div>
          <div class="text-sm font-semibold text-slate-900">Northbridge Admin</div>
          <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span><?= htmlspecialchars($user) ?></span>
            <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700"><?= htmlspecialchars($roleLabel) ?></span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <a href="<?= htmlspecialchars(url('/')) ?>" class="text-sm text-slate-600 hover:text-slate-900">Site home</a>
        <form method="post" action="<?= htmlspecialchars(url('/logout.php')) ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
          <button type="submit" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Log out</button>
        </form>
      </div>
    </div>
  </header>

  <main class="relative mx-auto max-w-6xl px-4 py-10 sm:px-6">
    <?php
    $flashMsg = trim((string)($_GET['msg'] ?? ''));
    $flashMap = [
        'readonly' => ['warn', 'Your role is read-only; that action was not applied.'],
        'forbidden' => ['error', 'Your role cannot perform that action.'],
    ];
    if ($flashMsg !== '' && isset($flashMap[$flashMsg])) {
        [$ftone, $ftext] = $flashMap[$flashMsg];
        $fcls = $ftone === 'error' ? 'border-rose-200 bg-rose-50 text-rose-950' : 'border-amber-200 bg-amber-50 text-amber-950';
        echo '<div class="mb-6 rounded-2xl border ' . $fcls . ' px-4 py-3 text-sm font-medium">' . htmlspecialchars($ftext) . '</div>';
    }
    ?>
    <div class="grid gap-6 lg:grid-cols-12">
      <aside class="lg:col-span-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Navigation</div>
          <nav class="mt-4 space-y-1 text-sm">
            <?= nav_item(url('/admin.php?view=dashboard'), 'Dashboard', $view === 'dashboard') ?>
            <?= nav_item(url('/admin.php?view=people'), 'People lookup', $view === 'people') ?>
            <?= nav_item(url('/admin.php?view=schedule'), 'Master schedule', $view === 'schedule') ?>
            <?= nav_item(url('/admin.php?view=enrollment'), 'Directory', $view === 'enrollment') ?>
            <?= nav_item(url('/admin.php?view=registration'), 'Registration', $view === 'registration') ?>
          </nav>
          <p class="mt-4 text-xs leading-relaxed text-slate-500">
            <?php if ($isViewer): ?>
              <strong class="text-slate-600">Viewer:</strong> browse only. No add/drop or hold changes.
            <?php elseif ($isLimited): ?>
              <strong class="text-slate-600">Limited:</strong> holds, registration add/drop. No grade import.
            <?php else: ?>
              <strong class="text-slate-600">Admin:</strong> full access.
            <?php endif; ?>
          </p>
        </div>
      </aside>
      <div class="lg:col-span-9">
        <?php if ($view === 'dashboard'): ?>
          <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
          <p class="mt-2 text-sm text-slate-600">Overview of the institutional dataset.</p>
          <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Students</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['students'] ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Faculty</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['faculty'] ?></div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Active holds</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['holds_active'] ?></div>
            </div>
          </div>
          <?php if ($currentTermCode): ?>
            <p class="mt-6 text-sm text-slate-600">Current term (latest by start date): <strong><?= htmlspecialchars($currentTermCode) ?></strong></p>
          <?php endif; ?>

        <?php elseif ($view === 'people'): ?>
          <h1 class="text-2xl font-semibold text-slate-900">People lookup</h1>
          <form class="mt-4 flex flex-wrap gap-2" method="get" action="<?= htmlspecialchars(url('/admin.php')) ?>">
            <input type="hidden" name="view" value="people" />
            <input name="id" value="<?= htmlspecialchars($peopleIdRaw) ?>" placeholder="Student or faculty ID" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" />
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Search</button>
          </form>
          <?php if ($peopleId !== null): ?>
            <?php
            $stChk = $pdo->prepare('SELECT COUNT(*) FROM students WHERE student_id = ?');
            $stChk->execute([$peopleId]);
            $isStu = (int)$stChk->fetchColumn() > 0;
            $fcChk = $pdo->prepare('SELECT COUNT(*) FROM faculty WHERE faculty_id = ?');
            $fcChk->execute([$peopleId]);
            $isFac = (int)$fcChk->fetchColumn() > 0;
            $urow = $pdo->prepare('SELECT first_name, last_name, user_type FROM users WHERE user_id = ?');
            $urow->execute([$peopleId]);
            $ur = $urow->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
              <?php if (!$ur): ?>
                <p class="text-sm text-slate-600">No user found for this ID.</p>
              <?php else: ?>
                <p class="text-lg font-semibold"><?= htmlspecialchars(trim($ur['first_name'] . ' ' . $ur['last_name'])) ?></p>
                <p class="text-sm text-slate-500">ID <?= (int)$peopleId ?> · <?= htmlspecialchars((string)$ur['user_type']) ?></p>
                <?php if ($isStu): ?>
                  <?php
                  $en = $pdo->prepare('
                    SELECT t.code, c.course_id, c.course_name, e.status, s.section_id
                    FROM enrollments e
                    JOIN sections s ON s.section_id = e.section_id
                    JOIN courses c ON c.course_id = s.course_id
                    JOIN terms t ON t.term_id = s.term_id
                    WHERE e.student_id = ?
                    ORDER BY t.start_date DESC, c.course_id
                    LIMIT 40
                  ');
                  $en->execute([$peopleId]);
                  $rows = $en->fetchAll(PDO::FETCH_ASSOC);
                  ?>
                  <h3 class="mt-4 text-sm font-semibold text-slate-800">Enrollments</h3>
                  <ul class="mt-2 space-y-1 text-sm">
                    <?php foreach ($rows as $r): ?>
                      <li><?= htmlspecialchars((string)$r['code']) ?> · <?= htmlspecialchars((string)$r['course_id']) ?> — <?= htmlspecialchars((string)$r['course_name']) ?> <span class="text-slate-500">(<?= htmlspecialchars((string)$r['status']) ?>)</span></li>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><li class="text-slate-500">None</li><?php endif; ?>
                  </ul>
                  <?php
                  $holdRows = [];
                  try {
                      $hh = $pdo->prepare('SELECT hold_id, hold_type, note, is_active FROM student_holds WHERE student_id = ? ORDER BY created_at DESC LIMIT 20');
                      $hh->execute([$peopleId]);
                      $holdRows = $hh->fetchAll(PDO::FETCH_ASSOC);
                  } catch (Throwable) {
                  }
                  ?>
                  <h3 class="mt-6 text-sm font-semibold text-slate-800">Holds</h3>
                  <?php if ($canManageHolds): ?>
                    <form class="mt-2 flex flex-wrap gap-2" method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId)) ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                      <input type="hidden" name="action" value="hold_add_people" />
                      <input type="hidden" name="student_id" value="<?= (int)$peopleId ?>" />
                      <input name="hold_type" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Hold type" required />
                      <input name="note" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Note (optional)" />
                      <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Add hold</button>
                    </form>
                  <?php endif; ?>
                  <ul class="mt-3 space-y-2 text-sm">
                    <?php foreach ($holdRows as $h): ?>
                      <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                        <span><?= htmlspecialchars((string)$h['hold_type']) ?><?= (int)$h['is_active'] === 1 ? '' : ' (cleared)' ?></span>
                        <?php if ((int)$h['is_active'] === 1 && $canManageHolds): ?>
                          <form method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId)) ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="action" value="hold_clear_people" />
                            <input type="hidden" name="hold_id" value="<?= (int)$h['hold_id'] ?>" />
                            <input type="hidden" name="student_id" value="<?= (int)$peopleId ?>" />
                            <button type="submit" class="text-xs font-semibold text-amber-800 hover:underline">Clear</button>
                          </form>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                    <?php if (!$holdRows): ?><li class="text-slate-500">No holds.</li><?php endif; ?>
                  </ul>
                <?php elseif ($isFac): ?>
                  <?php
                  $sec = $pdo->prepare('
                    SELECT t.code, s.section_id, c.course_id, c.course_name
                    FROM sections s
                    JOIN courses c ON c.course_id = s.course_id
                    JOIN terms t ON t.term_id = s.term_id
                    WHERE s.faculty_id = ?
                    ORDER BY t.start_date DESC
                    LIMIT 40
                  ');
                  $sec->execute([$peopleId]);
                  $srows = $sec->fetchAll(PDO::FETCH_ASSOC);
                  ?>
                  <h3 class="mt-4 text-sm font-semibold text-slate-800">Sections teaching</h3>
                  <ul class="mt-2 space-y-1 text-sm">
                    <?php foreach ($srows as $r): ?>
                      <li><?= htmlspecialchars((string)$r['code']) ?> · #<?= (int)$r['section_id'] ?> <?= htmlspecialchars((string)$r['course_id']) ?></li>
                    <?php endforeach; ?>
                    <?php if (!$srows): ?><li class="text-slate-500">None</li><?php endif; ?>
                  </ul>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        <?php elseif ($view === 'schedule'): ?>
          <?php
          require_once __DIR__ . '/../app/lib/admin_schedule.php';
          $scheduleState = admin_schedule_state($pdo, $_GET);
          $schedule_form_action = url('/admin.php?view=schedule');
          extract($scheduleState, EXTR_SKIP);
          require view_path('pages/admin/schedule.php');
          ?>

        <?php elseif ($view === 'enrollment'): ?>
          <h1 class="text-2xl font-semibold text-slate-900">Directory</h1>
          <p class="mt-2 text-sm text-slate-600">All students in the database.</p>
          <?php
          $dir = $pdo->query('
            SELECT u.user_id, u.first_name, u.last_name
            FROM users u
            JOIN students s ON s.student_id = u.user_id
            ORDER BY u.last_name, u.first_name
          ')->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
              <thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Name</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                <?php foreach ($dir as $r): ?>
                  <tr><td class="px-4 py-3 font-mono"><?= (int)$r['user_id'] ?></td><td class="px-4 py-3"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <?php else: /* registration */ ?>
          <?php
          $msg = trim((string)($_GET['msg'] ?? ''));
          $banners = [
              'readonly' => ['warn', 'Read-only for this action.'],
              'forbidden' => ['error', 'Your role cannot perform this action.'],
              'enrolled' => ['success', 'Enrolled.'],
              'waitlisted' => ['warn', 'Added to waitlist.'],
              'dropped' => ['success', 'Dropped.'],
              'hold' => ['error', 'Student has an active hold.'],
              'conflict' => ['error', 'Schedule conflict.'],
              'credit' => ['error', 'Exceeds credit limit.'],
              'prereq' => ['error', 'Prerequisite not met.'],
              'duplicate' => ['error', 'Already in section.'],
              'dupecourse' => ['error', 'Already in course this term.'],
              'wrongterm' => ['error', 'Wrong term.'],
              'invalid' => ['error', 'Invalid request.'],
          ];
          if ($msg !== '' && isset($banners[$msg])) {
              [$tone, $text] = $banners[$msg];
              $cls = $tone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : ($tone === 'error' ? 'border-rose-200 bg-rose-50 text-rose-950' : 'border-amber-200 bg-amber-50 text-amber-950');
              echo '<div class="mb-4 rounded-2xl border ' . $cls . ' px-4 py-3 text-sm font-medium">' . htmlspecialchars($text) . '</div>';
          }
          $regStudentRaw = trim((string)($_GET['student_id'] ?? ''));
          $regStudentId = ctype_digit($regStudentRaw) ? (int)$regStudentRaw : null;
          $termCode = trim((string)($_GET['term'] ?? ''));
          if ($termCode === '' && $currentTermCode !== null) {
              $termCode = $currentTermCode;
          }
          $terms = $pdo->query('SELECT code, name FROM terms ORDER BY start_date DESC')->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <h1 class="text-2xl font-semibold text-slate-900">Registration</h1>
          <form class="mt-4 flex flex-wrap gap-2" method="get">
            <input type="hidden" name="view" value="registration" />
            <input name="student_id" value="<?= htmlspecialchars($regStudentRaw) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Student ID" />
            <select name="term" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
              <?php foreach ($terms as $t): $c = (string)$t['code']; ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $c === $termCode ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Load</button>
          </form>
          <?php
          $creditSummary = ['courses' => 0, 'credits' => 0, 'max' => 18];
          $enrolledNow = [];
          if ($regStudentId !== null && $termCode !== '') {
              try {
                  $mx = $pdo->prepare('SELECT max_credit FROM ug_credit_limits WHERE student_id = ? LIMIT 1');
                  $mx->execute([$regStudentId]);
                  $v = $mx->fetchColumn();
                  if ($v !== false && $v !== null && is_numeric($v) && (int)$v > 0) {
                      $creditSummary['max'] = (int)$v;
                  }
              } catch (Throwable) {
              }
              $st = $pdo->prepare('
                SELECT s.section_id, c.course_id, c.course_name, c.credits
                FROM enrollments e
                JOIN sections s ON s.section_id = e.section_id
                JOIN courses c ON c.course_id = s.course_id
                JOIN terms t ON t.term_id = s.term_id
                WHERE e.student_id = ? AND e.status = "enrolled" AND t.code = ?
              ');
              $st->execute([$regStudentId, $termCode]);
              $enrolledNow = $st->fetchAll(PDO::FETCH_ASSOC);
              foreach ($enrolledNow as $r) {
                  $creditSummary['courses']++;
                  $creditSummary['credits'] += (int)($r['credits'] ?? 0);
              }
          }
          ?>
          <?php if ($regStudentId !== null): ?>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
              <div class="text-sm font-semibold">Load: <?= (int)$creditSummary['credits'] ?> / <?= (int)$creditSummary['max'] ?> credits · <?= (int)$creditSummary['courses'] ?> course(s)</div>
              <ul class="mt-2 text-sm text-slate-600">
                <?php foreach ($enrolledNow as $e): ?>
                  <li><?= htmlspecialchars((string)$e['course_id']) ?> (#<?= (int)$e['section_id'] ?>)</li>
                <?php endforeach; ?>
              </ul>
              <?php if ($canRegister): ?>
                <form class="mt-4 flex flex-wrap gap-2" method="post" action="<?= htmlspecialchars(url('/admin.php?view=registration&student_id=' . $regStudentId . '&term=' . rawurlencode($termCode))) ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="action" value="reg_add" />
                  <input type="hidden" name="student_id" value="<?= (int)$regStudentId ?>" />
                  <input type="hidden" name="term" value="<?= htmlspecialchars($termCode) ?>" />
                  <input name="section_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Section ID" />
                  <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" type="submit">Add</button>
                </form>
                <form class="mt-2 flex flex-wrap gap-2" method="post">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                  <input type="hidden" name="action" value="reg_drop" />
                  <input type="hidden" name="student_id" value="<?= (int)$regStudentId ?>" />
                  <input type="hidden" name="term" value="<?= htmlspecialchars($termCode) ?>" />
                  <input name="section_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Section ID" />
                  <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm" type="submit">Drop</button>
                </form>
              <?php else: ?>
                <p class="mt-4 text-sm text-slate-500">Viewer: use read-only; switch to Limited or Admin to add/drop.</p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
