<?php

declare(strict_types=1);

function redirect(string $to): void
{
    if ($to !== '' && !preg_match('#^[a-z][a-z0-9+.-]*://#i', $to) && str_starts_with($to, '/')) {
        $to = url($to);
    }
    header('Location: ' . $to);
    exit;
}

function audit_admin(PDO $pdo, string $action, string $details): void
{
    auth_start_session();
    $aid = (int)($_SESSION['auth']['id'] ?? 0);
    if ($aid < 1) {
        return;
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO admin_audit_log (admin_auth_id, action, details) VALUES (?, ?, ?)');
        $stmt->execute([$aid, $action, $details]);
    } catch (Throwable) {
        // avoid breaking UX if audit table missing in older DBs
    }
}

function handler_home(array $params): void
{
    global $app;
    render('pages/home.php', ['app' => $app]);
}

function handler_health(array $params): void
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = db();
        $pdo->query('SELECT 1')->fetchColumn();
        echo json_encode(['ok' => true, 'database' => true], JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'database' => false], JSON_THROW_ON_ERROR);
    }
}

function handler_admin_login_form(array $params): void
{
    global $app;
    if (auth_is_portal_user()) {
        redirect('/admin.php');
    }
    render('pages/admin/login.php', [
        'app' => $app,
        'error' => null,
        'csrf' => csrf_token(),
        'pageTitle' => 'Sign in',
    ], 'layouts/main.php');
}

function handler_admin_login_submit(array $params): void
{
    global $app;
    csrf_require_valid();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $ok = $username !== '' && $password !== '' && auth_login_portal_user($username, $password);
    if ($ok) {
        redirect('/admin.php');
    }

    render('pages/admin/login.php', [
        'app' => $app,
        'error' => 'Invalid username or password.',
        'csrf' => csrf_token(),
        'pageTitle' => 'Sign in',
    ], 'layouts/main.php');
}

function handler_admin_signup_form(array $params): void
{
    global $app;
    if (auth_is_portal_user()) {
        redirect('/admin.php');
    }
    render('pages/admin/signup.php', [
        'app' => $app,
        'error' => null,
        'csrf' => csrf_token(),
        'pageTitle' => 'Create account',
    ], 'layouts/main.php');
}

function handler_admin_signup_submit(array $params): void
{
    global $app;
    csrf_require_valid();

    if (auth_is_portal_user()) {
        redirect('/admin.php');
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($password !== $confirm) {
        render('pages/admin/signup.php', [
            'app' => $app,
            'error' => 'Passwords do not match.',
            'csrf' => csrf_token(),
            'pageTitle' => 'Create account',
        ], 'layouts/main.php');

        return;
    }

    [$ok, $err] = auth_create_admin($username, $password);
    if (!$ok) {
        render('pages/admin/signup.php', [
            'app' => $app,
            'error' => $err ?: 'Sign up failed.',
            'csrf' => csrf_token(),
            'pageTitle' => 'Create account',
        ], 'layouts/main.php');

        return;
    }

    redirect('/login.php');
}

function handler_admin_logout(array $params): void
{
    csrf_require_valid();
    auth_logout();
    redirect('/login.php');
}

function handler_admin_dashboard(array $params): void
{
    auth_require_portal_user();
    redirect('/admin.php');
}

function handler_admin_student_search(array $params): void
{
    global $app;
    auth_require_portal_user();
    render('pages/admin/student_search.php', [
        'app' => $app,
        'student_id' => trim((string)($_GET['student_id'] ?? '')),
    ], 'layouts/main.php');
}

function handler_admin_student_show(array $params): void
{
    global $app;
    auth_require_portal_user();

    $studentIdRaw = trim((string)($_GET['student_id'] ?? ''));
    $studentId = ctype_digit($studentIdRaw) ? (int)$studentIdRaw : null;

    $student = null;
    $departments = [];
    $enrollments = [];
    $holds = [];

    if ($studentId !== null) {
        $pdo = db();

        $stmt = $pdo->prepare('
          SELECT u.*
          FROM users u
          WHERE u.user_id = ?
          LIMIT 1
        ');
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        $stmt = $pdo->prepare('
          SELECT sd.dept_id, d.dept_name, sd.date_of_declaration
          FROM student_departments sd
          JOIN departments d ON d.dept_id = sd.dept_id
          WHERE sd.student_id = ?
          ORDER BY d.dept_name
        ');
        $stmt->execute([$studentId]);
        $departments = $stmt->fetchAll();

        $stmt = $pdo->prepare('
          SELECT
            e.status,
            e.created_at,
            s.section_id,
            c.course_id,
            c.course_name,
            c.credits,
            t.code AS term_code,
            t.name AS term_name,
            s.meeting_days,
            s.meeting_time,
            s.room
          FROM enrollments e
          JOIN sections s ON s.section_id = e.section_id
          JOIN courses c ON c.course_id = s.course_id
          JOIN terms t ON t.term_id = s.term_id
          WHERE e.student_id = ?
          ORDER BY e.created_at DESC
        ');
        $stmt->execute([$studentId]);
        $enrollments = $stmt->fetchAll();

        try {
            $stmt = $pdo->prepare('
              SELECT hold_id, hold_type, note, is_active, created_at, cleared_at
              FROM student_holds
              WHERE student_id = ?
              ORDER BY created_at DESC
            ');
            $stmt->execute([$studentId]);
            $holds = $stmt->fetchAll();
        } catch (Throwable) {
            $holds = [];
        }
    }

    render('pages/admin/student_detail.php', [
        'app' => $app,
        'student_id' => $studentIdRaw,
        'student' => $student,
        'departments' => $departments,
        'enrollments' => $enrollments,
        'holds' => $holds,
    ], 'layouts/main.php');
}

function handler_admin_schedule(array $params): void
{
    global $app;
    auth_require_portal_user();

    $pdo = db();
    $terms = $pdo->query('SELECT term_id, code, name, start_date FROM terms ORDER BY start_date DESC, term_id DESC')->fetchAll();

    $termId = null;
    if ($terms) {
        $validTermIds = array_map(static fn($t) => (int)$t['term_id'], $terms);
        if (isset($_GET['term_id']) && ctype_digit((string)$_GET['term_id']) && in_array((int)$_GET['term_id'], $validTermIds, true)) {
            $termId = (int)$_GET['term_id'];
        } else {
            $termId = (int)$terms[0]['term_id'];
        }
    }

    $deptFilter = trim((string)($_GET['dept_id'] ?? ''));
    $sections = [];

    if ($termId !== null) {
        $sql = '
          SELECT
            s.section_id,
            c.course_id,
            c.course_name,
            c.credits,
            c.dept_id,
            t.code AS term_code,
            t.name AS term_name,
            u.first_name AS fac_first,
            u.last_name AS fac_last,
            s.meeting_days,
            s.meeting_time,
            s.room,
            s.capacity
          FROM sections s
          JOIN courses c ON c.course_id = s.course_id
          JOIN terms t ON t.term_id = s.term_id
          LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
          LEFT JOIN users u ON u.user_id = f.faculty_id
          WHERE s.term_id = ?
        ';
        $bind = [$termId];
        if ($deptFilter !== '') {
            $sql .= ' AND c.dept_id = ?';
            $bind[] = $deptFilter;
        }
        $sql .= ' ORDER BY c.course_id, s.section_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($bind);
        $sections = $stmt->fetchAll();
    }

    render('pages/admin/schedule.php', [
        'app' => $app,
        'terms' => $terms,
        'term_id' => $termId,
        'dept_id' => $deptFilter,
        'sections' => $sections,
    ], 'layouts/main.php');
}

function handler_admin_holds_index(array $params): void
{
    global $app;
    auth_require_portal_user();
    render('pages/admin/holds_search.php', [
        'app' => $app,
        'student_id' => trim((string)($_GET['student_id'] ?? '')),
    ], 'layouts/main.php');
}

function handler_admin_holds_show(array $params): void
{
    global $app;
    auth_require_portal_user();

    $studentIdRaw = trim((string)($_GET['student_id'] ?? ''));
    $studentId = ctype_digit($studentIdRaw) ? (int)$studentIdRaw : null;

    $student = null;
    $holds = [];
    $error = null;

    if ($studentId === null) {
        $error = 'Enter a numeric student ID.';
    } else {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT u.* FROM users u JOIN students s ON s.student_id = u.user_id WHERE u.user_id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        if (!$student) {
            $error = 'No student record for that ID.';
        } else {
            try {
                $stmt = $pdo->prepare('
                  SELECT hold_id, hold_type, note, is_active, created_at, cleared_at
                  FROM student_holds
                  WHERE student_id = ?
                  ORDER BY is_active DESC, created_at DESC
                ');
                $stmt->execute([$studentId]);
                $holds = $stmt->fetchAll();
            } catch (Throwable) {
                $error = 'Holds table missing — run php scripts/migrate.php (includes 002_holds_audit.sql).';
            }
        }
    }

    render('pages/admin/holds_show.php', [
        'app' => $app,
        'student_id' => $studentIdRaw,
        'student' => $student,
        'holds' => $holds,
        'error' => $error,
        'csrf' => csrf_token(),
        'hold_types' => ['Bursar', 'Academic', 'Registration', 'Other'],
    ], 'layouts/main.php');
}

function handler_admin_holds_add(array $params): void
{
    auth_require_portal_user();
    csrf_require_valid();

    $studentId = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
    $holdType = trim((string)($_POST['hold_type'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));
    $allowed = ['Bursar', 'Academic', 'Registration', 'Other'];

    if ($studentId === null || !in_array($holdType, $allowed, true)) {
        redirect('/admin/holds?error=invalid');
    }

    $pdo = db();
    $chk = $pdo->prepare('SELECT 1 FROM students WHERE student_id = ?');
    $chk->execute([$studentId]);
    if (!$chk->fetchColumn()) {
        redirect('/admin/holds?error=nostudent');
    }

    $stmt = $pdo->prepare('
      INSERT INTO student_holds (student_id, hold_type, note, is_active)
      VALUES (?, ?, ?, 1)
    ');
    $stmt->execute([
        $studentId,
        $holdType,
        $note !== '' ? substr($note, 0, 500) : null,
    ]);

    audit_admin($pdo, 'hold_add', 'student_id=' . $studentId . ';type=' . $holdType);

    redirect('/admin/holds/show?student_id=' . $studentId);
}

function handler_admin_holds_clear(array $params): void
{
    auth_require_portal_user();
    csrf_require_valid();

    $holdId = isset($_POST['hold_id']) && ctype_digit((string)$_POST['hold_id']) ? (int)$_POST['hold_id'] : null;
    $studentId = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;

    if ($holdId === null || $studentId === null) {
        redirect('/admin/holds');
    }

    $pdo = db();
    $stmt = $pdo->prepare('
      UPDATE student_holds
      SET is_active = 0, cleared_at = CURRENT_TIMESTAMP
      WHERE hold_id = ? AND student_id = ? AND is_active = 1
    ');
    $stmt->execute([$holdId, $studentId]);

    audit_admin($pdo, 'hold_clear', 'student_id=' . $studentId . ';hold_id=' . $holdId);

    redirect('/admin/holds/show?student_id=' . $studentId);
}
