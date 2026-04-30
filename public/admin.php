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
        $blocked = ['grade_upsert', 'people_scr_upsert'];
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
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&people_panel=hold'));
        exit;
    }
    if ($action === 'hold_clear_people' && $canManageHolds) {
        $holdId = isset($_POST['hold_id']) && ctype_digit((string)$_POST['hold_id']) ? (int)$_POST['hold_id'] : null;
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        if ($holdId !== null && $sid !== null) {
            $pdo->prepare('UPDATE student_holds SET is_active = 0, cleared_at = CURRENT_TIMESTAMP WHERE hold_id = ? AND student_id = ? AND is_active = 1')->execute([$holdId, $sid]);
        }
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&people_panel=hold'));
        exit;
    }
    if ($action === 'hold_add_people' && $canManageHolds) {
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $presets = admin_people_hold_presets();
        $sel = trim((string)($_POST['hold_type_select'] ?? ''));
        $custom = trim((string)($_POST['hold_type_custom'] ?? ''));
        $type = '';
        if ($sel === '__custom__') {
            $type = $custom;
        } elseif ($sel !== '' && in_array($sel, $presets, true)) {
            $type = $sel;
        }
        $note = trim((string)($_POST['note'] ?? ''));
        if ($sid !== null && $type !== '') {
            $pdo->prepare('INSERT INTO student_holds (student_id, hold_type, note, is_active) VALUES (?, ?, ?, 1)')->execute([$sid, $type, $note !== '' ? $note : null]);
            admin_audit($pdo, 'hold_add', 'student_id=' . $sid);
            header('Location: ' . url('/admin.php?view=people&id=' . $sid . '&people_panel=hold&msg=hold_added'));
            exit;
        }
        header('Location: ' . url('/admin.php?view=people&id=' . ($sid ?? '') . '&people_panel=hold'));
        exit;
    }

    if ($action === 'people_update_student' && $canManageHolds) {
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $redirectId = $sid ?? 0;
        if ($sid === null) {
            header('Location: ' . url('/admin.php?view=people&people_panel=info'));
            exit;
        }
        $stuOk = $pdo->prepare('SELECT 1 FROM students WHERE student_id = ? LIMIT 1');
        $stuOk->execute([$sid]);
        if (!$stuOk->fetchColumn()) {
            header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=forbidden'));
            exit;
        }
        $genderIn = trim((string)($_POST['gender'] ?? ''));
        $stateRaw = trim((string)($_POST['state'] ?? ''));
        $stateIn = strtoupper($stateRaw);
        $emailIn = trim((string)($_POST['email'] ?? ''));
        $phoneIn = trim((string)($_POST['phone'] ?? ''));
        $aptIn = trim((string)($_POST['apt_no'] ?? ''));
        $streetIn = trim((string)($_POST['street'] ?? ''));
        $cityIn = trim((string)($_POST['city'] ?? ''));
        $zipIn = trim((string)($_POST['zip_code'] ?? ''));
        $sets = [];
        $params = [];
        $inputErr = false;
        $allowedG = admin_people_genders();
        if ($genderIn !== '' && $genderIn !== '__keep__' && in_array($genderIn, $allowedG, true)) {
            $sets[] = 'gender = ?';
            $params[] = $genderIn;
        }
        $stateCodes = admin_people_us_state_codes();
        if ($stateRaw !== '' && strcasecmp($stateRaw, '__keep__') !== 0 && in_array($stateIn, $stateCodes, true)) {
            $sets[] = 'state = ?';
            $params[] = $stateIn;
        }
        if ($emailIn !== '') {
            if (filter_var($emailIn, FILTER_VALIDATE_EMAIL)) {
                $sets[] = 'email = ?';
                $params[] = $emailIn;
            } else {
                $inputErr = true;
            }
        }
        if ($phoneIn !== '') {
            if (preg_match('/^[0-9+\-\s().]{7,40}$/', $phoneIn)) {
                $sets[] = 'phone_number = ?';
                $params[] = $phoneIn;
            } else {
                $inputErr = true;
            }
        }
        foreach (['apt_no' => $aptIn, 'street' => $streetIn, 'city' => $cityIn, 'zip_code' => $zipIn] as $col => $val) {
            if ($val !== '') {
                $sets[] = $col . ' = ?';
                $params[] = $val;
            }
        }
        if ($inputErr) {
            header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=profile_invalid'));
            exit;
        }
        if ($sets !== []) {
            $params[] = $sid;
            $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = ?')->execute($params);
        }

        $years = admin_people_academic_year_levels();
        $ints = admin_people_enrollment_intensity_options();
        $ayIn = trim((string)($_POST['academic_year_level'] ?? ''));
        $eiIn = trim((string)($_POST['enrollment_intensity'] ?? ''));
        $newAy = null;
        $newEi = null;
        if ($ayIn !== '' && $ayIn !== '__keep__' && in_array($ayIn, $years, true)) {
            $newAy = $ayIn;
        }
        if ($eiIn !== '' && $eiIn !== '__keep__' && in_array($eiIn, $ints, true)) {
            $newEi = $eiIn;
        }
        $ugRow = $pdo->prepare('SELECT student_id, student_type FROM undergrad_students WHERE student_id = ? LIMIT 1');
        $ugRow->execute([$sid]);
        $ugEx = $ugRow->fetch(PDO::FETCH_ASSOC);
        if ($ugEx) {
            $uq = [];
            $up = [];
            if ($newAy !== null) {
                $uq[] = 'academic_year_level = ?';
                $up[] = $newAy;
            }
            if ($newEi !== null) {
                $uq[] = 'enrollment_intensity = ?';
                $up[] = $newEi;
            }
            if ($uq !== []) {
                $up[] = $sid;
                $pdo->prepare('UPDATE undergrad_students SET ' . implode(', ', $uq) . ' WHERE student_id = ?')->execute($up);
            }
        } elseif ($newAy !== null || $newEi !== null) {
            $pdo->prepare('INSERT INTO undergrad_students (student_id, student_type, academic_year_level, enrollment_intensity) VALUES (?, ?, ?, ?)')->execute([
                $sid,
                'Unknown',
                $newAy,
                $newEi,
            ]);
        }

        $roles = $_POST['declaration_role'] ?? [];
        if (is_array($roles)) {
            foreach ($roles as $deptId => $role) {
                $deptId = trim((string)$deptId);
                $role = trim((string)$role);
                if ($deptId === '' || ($role !== 'major' && $role !== 'minor')) {
                    continue;
                }
                $pdo->prepare('UPDATE student_departments SET declaration_role = ? WHERE student_id = ? AND dept_id = ?')->execute([$role, $sid, $deptId]);
            }
        }

        $addDept = trim((string)($_POST['add_declaration_dept'] ?? ''));
        $addRole = trim((string)($_POST['add_declaration_role'] ?? ''));
        if ($addDept !== '' && ($addRole === 'major' || $addRole === 'minor')) {
            $dchk = $pdo->prepare('SELECT 1 FROM departments WHERE dept_id = ? LIMIT 1');
            $dchk->execute([$addDept]);
            if ($dchk->fetchColumn()) {
                $ex = $pdo->prepare('SELECT 1 FROM student_departments WHERE student_id = ? AND dept_id = ? LIMIT 1');
                $ex->execute([$sid, $addDept]);
                if (!$ex->fetchColumn()) {
                    $pdo->prepare('INSERT INTO student_departments (student_id, dept_id, declaration_role, date_of_declaration) VALUES (?, ?, ?, CURDATE())')->execute([$sid, $addDept, $addRole]);
                }
            }
        }

        if ($isAdmin) {
            $tceRaw = trim((string)($_POST['total_credit_earned'] ?? ''));
            if ($tceRaw !== '' && ctype_digit($tceRaw)) {
                $tce = (int)$tceRaw;
                $hasLim = $pdo->prepare('SELECT 1 FROM ug_credit_limits WHERE student_id = ? LIMIT 1');
                $hasLim->execute([$sid]);
                if ($hasLim->fetchColumn()) {
                    $pdo->prepare('UPDATE ug_credit_limits SET total_credit_earned = ? WHERE student_id = ?')->execute([$tce, $sid]);
                } else {
                    $stLabel = ($ugEx && !empty($ugEx['student_type'])) ? (string)$ugEx['student_type'] : 'Unknown';
                    $yr = (int)date('Y');
                    $band = admin_ug_credit_band_from_student_type($stLabel);
                    $pdo->prepare('INSERT INTO ug_credit_limits (student_id, student_type, year, max_credit, min_credit, total_credit_earned) VALUES (?, ?, ?, ?, ?, ?)')->execute([
                        $sid, $stLabel, $yr, $band['max_credit'], $band['min_credit'], $tce,
                    ]);
                }
            }
        }

        admin_audit($pdo, 'people_update_student', 'student_id=' . $sid);
        header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=profile_saved'));
        exit;
    }

    if ($action === 'people_update_faculty' && $canManageHolds) {
        $fid = isset($_POST['faculty_id']) && ctype_digit((string)$_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null;
        $redirectId = $fid ?? 0;
        if ($fid === null) {
            header('Location: ' . url('/admin.php?view=people&people_panel=info'));
            exit;
        }
        $facOk = $pdo->prepare('SELECT 1 FROM faculty WHERE faculty_id = ? LIMIT 1');
        $facOk->execute([$fid]);
        if (!$facOk->fetchColumn()) {
            header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=forbidden'));
            exit;
        }
        $emailIn = trim((string)($_POST['email'] ?? ''));
        $phoneIn = trim((string)($_POST['phone'] ?? ''));
        $officeIn = trim((string)($_POST['office_number'] ?? ''));
        $inputErr = false;
        $setsU = [];
        $paramsU = [];
        if ($emailIn !== '') {
            if (filter_var($emailIn, FILTER_VALIDATE_EMAIL)) {
                $setsU[] = 'email = ?';
                $paramsU[] = $emailIn;
            } else {
                $inputErr = true;
            }
        }
        if ($phoneIn !== '') {
            if (preg_match('/^[0-9+\-\s().]{7,40}$/', $phoneIn)) {
                $setsU[] = 'phone_number = ?';
                $paramsU[] = $phoneIn;
            } else {
                $inputErr = true;
            }
        }
        if ($inputErr) {
            header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=profile_invalid'));
            exit;
        }
        if ($setsU !== []) {
            $paramsU[] = $fid;
            $pdo->prepare('UPDATE users SET ' . implode(', ', $setsU) . ' WHERE user_id = ?')->execute($paramsU);
        }
        $setsF = [];
        $paramsF = [];
        if ($emailIn !== '') {
            $setsF[] = 'email = ?';
            $paramsF[] = $emailIn;
        }
        if ($phoneIn !== '') {
            $setsF[] = 'phone_number = ?';
            $paramsF[] = $phoneIn;
        }
        if ($officeIn !== '') {
            $setsF[] = 'office_number = ?';
            $paramsF[] = $officeIn;
        }
        if ($setsF !== []) {
            $paramsF[] = $fid;
            $pdo->prepare('UPDATE faculty SET ' . implode(', ', $setsF) . ' WHERE faculty_id = ?')->execute($paramsF);
        }
        admin_audit($pdo, 'people_update_faculty', 'faculty_id=' . $fid);
        header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=faculty_saved'));
        exit;
    }

    if ($action === 'people_scr_upsert' && $isAdmin) {
        $sid = isset($_POST['student_id']) && ctype_digit((string)$_POST['student_id']) ? (int)$_POST['student_id'] : null;
        $redirectId = $sid ?? 0;
        if ($sid === null) {
            header('Location: ' . url('/admin.php?view=people&people_panel=info'));
            exit;
        }
        $courseId = strtoupper(trim((string)($_POST['course_id'] ?? '')));
        $termId = isset($_POST['term_id']) && ctype_digit((string)$_POST['term_id']) ? (int)$_POST['term_id'] : null;
        $letter = strtoupper(trim((string)($_POST['letter_grade'] ?? '')));
        $gpRaw = trim((string)($_POST['grade_points'] ?? ''));
        if ($sid && $courseId !== '' && $termId !== null && $letter !== '') {
            $cchk = $pdo->prepare('SELECT credits FROM courses WHERE course_id = ? LIMIT 1');
            $cchk->execute([$courseId]);
            $crRow = $cchk->fetch(PDO::FETCH_ASSOC);
            $tchk = $pdo->prepare('SELECT 1 FROM terms WHERE term_id = ? LIMIT 1');
            $tchk->execute([$termId]);
            $stuOk = $pdo->prepare('SELECT 1 FROM students WHERE student_id = ? LIMIT 1');
            $stuOk->execute([$sid]);
            if ($crRow && $tchk->fetchColumn() && $stuOk->fetchColumn()) {
                $courseCredits = (int)($crRow['credits'] ?? 0);
                $gp = admin_grade_points_from_letter($letter);
                if ($gp === null && $gpRaw !== '' && is_numeric($gpRaw)) {
                    $gp = round((float)$gpRaw, 2);
                }
                if ($gp !== null) {
                    $creditsEarned = ($gp > 0.0) ? $courseCredits : 0;
                    $pdo->prepare('
                      INSERT INTO student_course_results (student_id, course_id, term_id, letter_grade, grade_points, credits_earned)
                      VALUES (?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE letter_grade = VALUES(letter_grade), grade_points = VALUES(grade_points), credits_earned = VALUES(credits_earned)
                    ')->execute([$sid, $courseId, $termId, $letter, $gp, $creditsEarned]);
                    admin_audit($pdo, 'people_scr_upsert', 'student_id=' . $sid . ' course=' . $courseId);
                    header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=grade_saved'));
                    exit;
                }
            }
        }
        header('Location: ' . url('/admin.php?view=people&id=' . $redirectId . '&people_panel=info&msg=grade_invalid'));
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
$dash = [
    'students_missing_email' => 0,
    'students_missing_phone' => 0,
    'faculty_missing_email' => 0,
    'faculty_missing_phone' => 0,
    'term_sections' => 0,
    'term_enrolled' => 0,
    'term_waitlisted' => 0,
    'term_open_seats' => 0,
    'top_enrolled' => [],
    'top_waitlisted' => [],
];
try {
    $counts['students'] = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $counts['faculty'] = (int)$pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
    $counts['holds_active'] = (int)$pdo->query('SELECT COUNT(*) FROM student_holds WHERE is_active = 1')->fetchColumn();

    // Data-quality signals
    $dash['students_missing_email'] = (int)$pdo->query('
      SELECT COUNT(*)
      FROM users u
      INNER JOIN students s ON s.student_id = u.user_id
      WHERE u.email IS NULL OR TRIM(u.email) = ""
    ')->fetchColumn();
    $dash['students_missing_phone'] = (int)$pdo->query('
      SELECT COUNT(*)
      FROM users u
      INNER JOIN students s ON s.student_id = u.user_id
      WHERE u.phone_number IS NULL OR TRIM(u.phone_number) = ""
    ')->fetchColumn();
    $dash['faculty_missing_email'] = (int)$pdo->query('
      SELECT COUNT(*)
      FROM faculty f
      WHERE f.email IS NULL OR TRIM(f.email) = ""
    ')->fetchColumn();
    $dash['faculty_missing_phone'] = (int)$pdo->query('
      SELECT COUNT(*)
      FROM faculty f
      WHERE f.phone_number IS NULL OR TRIM(f.phone_number) = ""
    ')->fetchColumn();

    // Current-term operational stats (if we have a term)
    if ($currentTermId !== null) {
        $secCnt = $pdo->prepare('SELECT COUNT(*) FROM sections WHERE term_id = ?');
        $secCnt->execute([$currentTermId]);
        $dash['term_sections'] = (int)$secCnt->fetchColumn();

        $st = $pdo->prepare('
          SELECT
            SUM(e.status = "enrolled") AS enrolled_cnt,
            SUM(e.status = "waitlisted") AS waitlisted_cnt
          FROM enrollments e
          INNER JOIN sections s ON s.section_id = e.section_id
          WHERE s.term_id = ?
        ');
        $st->execute([$currentTermId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $dash['term_enrolled'] = (int)($row['enrolled_cnt'] ?? 0);
        $dash['term_waitlisted'] = (int)($row['waitlisted_cnt'] ?? 0);

        $open = $pdo->prepare('
          SELECT
            COALESCE(SUM(GREATEST(s.capacity - COALESCE(en.enrolled_cnt, 0), 0)), 0) AS open_seats
          FROM sections s
          LEFT JOIN (
            SELECT e.section_id, COUNT(*) AS enrolled_cnt
            FROM enrollments e
            WHERE e.status = "enrolled"
            GROUP BY e.section_id
          ) en ON en.section_id = s.section_id
          WHERE s.term_id = ?
        ');
        $open->execute([$currentTermId]);
        $dash['term_open_seats'] = (int)$open->fetchColumn();

        $topEn = $pdo->prepare('
          SELECT
            s.section_id,
            c.course_id,
            c.course_name,
            s.capacity,
            COALESCE(en.enrolled_cnt, 0) AS enrolled_cnt,
            COALESCE(wl.waitlisted_cnt, 0) AS waitlisted_cnt,
            u.first_name AS fac_first,
            u.last_name AS fac_last
          FROM sections s
          INNER JOIN courses c ON c.course_id = s.course_id
          LEFT JOIN (
            SELECT section_id, COUNT(*) AS enrolled_cnt
            FROM enrollments
            WHERE status = "enrolled"
            GROUP BY section_id
          ) en ON en.section_id = s.section_id
          LEFT JOIN (
            SELECT section_id, COUNT(*) AS waitlisted_cnt
            FROM enrollments
            WHERE status = "waitlisted"
            GROUP BY section_id
          ) wl ON wl.section_id = s.section_id
          LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
          LEFT JOIN users u ON u.user_id = f.faculty_id
          WHERE s.term_id = ?
          ORDER BY enrolled_cnt DESC, waitlisted_cnt DESC, s.section_id DESC
          LIMIT 5
        ');
        $topEn->execute([$currentTermId]);
        $dash['top_enrolled'] = $topEn->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $topWl = $pdo->prepare('
          SELECT
            s.section_id,
            c.course_id,
            c.course_name,
            s.capacity,
            COALESCE(en.enrolled_cnt, 0) AS enrolled_cnt,
            COALESCE(wl.waitlisted_cnt, 0) AS waitlisted_cnt,
            u.first_name AS fac_first,
            u.last_name AS fac_last
          FROM sections s
          INNER JOIN courses c ON c.course_id = s.course_id
          LEFT JOIN (
            SELECT section_id, COUNT(*) AS enrolled_cnt
            FROM enrollments
            WHERE status = "enrolled"
            GROUP BY section_id
          ) en ON en.section_id = s.section_id
          LEFT JOIN (
            SELECT section_id, COUNT(*) AS waitlisted_cnt
            FROM enrollments
            WHERE status = "waitlisted"
            GROUP BY section_id
          ) wl ON wl.section_id = s.section_id
          LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
          LEFT JOIN users u ON u.user_id = f.faculty_id
          WHERE s.term_id = ?
          ORDER BY waitlisted_cnt DESC, enrolled_cnt DESC, s.section_id DESC
          LIMIT 5
        ');
        $topWl->execute([$currentTermId]);
        $dash['top_waitlisted'] = $topWl->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable) {
}

function admin_people_hold_presets(): array
{
    return ['Bursar', 'Academic', 'Registration', 'Immunization', 'Financial aid', 'Disciplinary', 'Other'];
}

function admin_people_genders(): array
{
    return ['Male', 'Female', 'Non-binary', 'Prefer not to say', 'Other'];
}

/** US postal abbreviations (including DC) for profile state dropdown */
function admin_people_us_state_codes(): array
{
    return ['AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY', 'DC'];
}

function admin_people_academic_year_levels(): array
{
    return ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Other'];
}

function admin_people_enrollment_intensity_options(): array
{
    return ['Full-time', 'Part-time', 'Other'];
}

/**
 * Enrollment label for display & dropdown hints when enrollment_intensity is unset:
 * maps legacy undergrad_students.student_type (e.g. Fulltime/Parttime from CSV import).
 */
function admin_ug_display_enrollment(?array $ug): string
{
    if (!$ug) {
        return '';
    }
    $ei = trim((string)($ug['enrollment_intensity'] ?? ''));
    if ($ei !== '') {
        return $ei;
    }
    $st = strtolower(trim((string)($ug['student_type'] ?? '')));
    if ($st === 'fulltime' || str_contains($st, 'full')) {
        return 'Full-time';
    }
    if ($st === 'parttime' || str_contains($st, 'part')) {
        return 'Part-time';
    }
    $legacy = trim((string)($ug['student_type'] ?? ''));

    return $legacy !== '' ? $legacy : '';
}

/**
 * Default load bands when admin creates the first ug_credit_limits row (no CSV row yet).
 * Normal installs should populate limits from storage/import UG_fulltime.csv & UG_parttime.csv.
 */
function admin_ug_credit_band_from_student_type(string $studentType): array
{
    $t = strtolower(trim($studentType));
    if ($t === 'parttime' || str_contains($t, 'part')) {
        return ['max_credit' => 12, 'min_credit' => 6];
    }

    return ['max_credit' => 18, 'min_credit' => 12];
}

/** Map common letter grades to quality points (4.0 scale); returns null if unknown */
function admin_grade_points_from_letter(string $letter): ?float
{
    $l = strtoupper(trim($letter));
    $map = [
        'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
        'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
        'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
        'D+' => 1.3, 'D' => 1.0, 'D-' => 0.7,
        'F' => 0.0, 'W' => 0.0, 'WP' => 0.0, 'WF' => 0.0, 'I' => 0.0,
    ];

    return array_key_exists($l, $map) ? $map[$l] : null;
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
    <div class="mx-auto flex max-w-[min(100vw-2rem,96rem)] flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
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

  <main class="relative mx-auto max-w-[min(100vw-2rem,96rem)] px-4 py-10 sm:px-6">
    <?php
    $flashMsg = trim((string)($_GET['msg'] ?? ''));
    $flashMap = [
        'readonly' => ['warn', 'Your role is read-only; that action was not applied.'],
        'forbidden' => ['error', 'Your role cannot perform that action.'],
        'profile_saved' => ['success', 'Student record saved.'],
        'faculty_saved' => ['success', 'Faculty profile saved.'],
        'profile_invalid' => ['error', 'Profile was not updated — check email or phone format.'],
        'hold_added' => ['success', 'Hold added.'],
        'grade_saved' => ['success', 'Transcript grade saved.'],
        'grade_invalid' => ['error', 'Grade was not saved — check course, term, and letter grade.'],
    ];
    if ($flashMsg !== '' && isset($flashMap[$flashMsg])) {
        [$ftone, $ftext] = $flashMap[$flashMsg];
        if ($ftone === 'error') {
            $fcls = 'border-rose-200 bg-rose-50 text-rose-950';
        } elseif ($ftone === 'success') {
            $fcls = 'border-emerald-200 bg-emerald-50 text-emerald-950';
        } else {
            $fcls = 'border-amber-200 bg-amber-50 text-amber-950';
        }
        echo '<div class="mb-6 rounded-2xl border ' . $fcls . ' px-4 py-3 text-sm font-medium">' . htmlspecialchars($ftext) . '</div>';
    }
    ?>
    <div class="grid gap-6 lg:grid-cols-12">
      <aside class="lg:col-span-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Navigation</div>
          <nav class="mt-4 space-y-1 text-sm">
            <?= nav_item(url('/admin.php?view=dashboard'), 'Dashboard', $view === 'dashboard') ?>
            <?= nav_item(url('/admin.php?view=people'), 'ID lookup', $view === 'people') ?>
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
          <p class="mt-2 text-sm text-slate-600">A snapshot of operations, data quality, and current-term activity.</p>

          <div class="mt-6 flex flex-wrap gap-2">
            <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=schedule')) ?>">Master schedule</a>
            <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=people')) ?>">ID lookup</a>
            <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=registration')) ?>">Registration</a>
            <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin/holds')) ?>">Holds</a>
            <a class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50" href="<?= htmlspecialchars(url('/admin.php?view=enrollment')) ?>">Directory</a>
          </div>

          <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Students</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['students'] ?></div>
              <div class="mt-2 text-xs text-slate-500"><?= (int)($dash['students_missing_email'] ?? 0) ?> missing email · <?= (int)($dash['students_missing_phone'] ?? 0) ?> missing phone</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Faculty</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['faculty'] ?></div>
              <div class="mt-2 text-xs text-slate-500"><?= (int)($dash['faculty_missing_email'] ?? 0) ?> missing email · <?= (int)($dash['faculty_missing_phone'] ?? 0) ?> missing phone</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Active holds</div>
              <div class="mt-2 text-3xl font-semibold"><?= (int)$counts['holds_active'] ?></div>
              <div class="mt-2 text-xs text-slate-500">Registration may be blocked</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="text-xs font-semibold uppercase text-slate-500">Current term</div>
              <div class="mt-2 text-lg font-semibold text-slate-900"><?= $currentTermCode ? htmlspecialchars($currentTermCode) : '—' ?></div>
              <div class="mt-2 text-xs text-slate-500">
                <?= (int)($dash['term_sections'] ?? 0) ?> sections ·
                <?= (int)($dash['term_enrolled'] ?? 0) ?> enrolled ·
                <?= (int)($dash['term_waitlisted'] ?? 0) ?> waitlisted ·
                <?= (int)($dash['term_open_seats'] ?? 0) ?> open seats
              </div>
            </div>
          </div>

          <div class="mt-8 grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4">
              <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Needs attention</div>
                <ul class="mt-4 space-y-2 text-sm">
                  <li class="flex items-center justify-between gap-3">
                    <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=schedule&q=%40northbridge.edu')) ?>">Verify school emails</a>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"><?= (int)($dash['students_missing_email'] ?? 0) + (int)($dash['faculty_missing_email'] ?? 0) ?></span>
                  </li>
                  <li class="flex items-center justify-between gap-3">
                    <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=schedule&q=%28')) ?>">Check phone formatting</a>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"><?= (int)($dash['students_missing_phone'] ?? 0) + (int)($dash['faculty_missing_phone'] ?? 0) ?></span>
                  </li>
                  <li class="flex items-center justify-between gap-3">
                    <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin/holds')) ?>">Active holds</a>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900"><?= (int)$counts['holds_active'] ?></span>
                  </li>
                </ul>
                <p class="mt-4 text-xs leading-relaxed text-slate-500">
                  Tip: use Master schedule search to find specific people quickly by ID, name, email, or phone.
                </p>
              </div>
            </div>

            <div class="lg:col-span-8">
              <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                  <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current term spotlight</div>
                    <div class="mt-1 text-sm text-slate-600">
                      <?= $currentTermCode ? ('Top sections for ' . htmlspecialchars($currentTermCode)) : 'No term data yet.' ?>
                    </div>
                  </div>
                  <a class="text-sm font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=schedule')) ?>">Open master schedule →</a>
                </div>

                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800">Top enrolled</div>
                    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                      <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                          <tr>
                            <th class="px-3 py-2">Course</th>
                            <th class="px-3 py-2">Enrolled</th>
                            <th class="px-3 py-2">Cap</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                          <?php foreach (($dash['top_enrolled'] ?? []) as $r): ?>
                            <tr class="hover:bg-slate-50/60">
                              <td class="px-3 py-2">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($r['course_id'] ?? '')) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)($r['course_name'] ?? '')) ?></div>
                              </td>
                              <td class="px-3 py-2 font-semibold text-slate-900"><?= (int)($r['enrolled_cnt'] ?? 0) ?></td>
                              <td class="px-3 py-2 text-slate-600"><?= (int)($r['capacity'] ?? 0) ?></td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!($dash['top_enrolled'] ?? [])): ?>
                            <tr><td class="px-3 py-4 text-center text-slate-500" colspan="3">No data.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800">Top waitlisted</div>
                    <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                      <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                          <tr>
                            <th class="px-3 py-2">Course</th>
                            <th class="px-3 py-2">Waitlist</th>
                            <th class="px-3 py-2">Enrolled</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                          <?php foreach (($dash['top_waitlisted'] ?? []) as $r): ?>
                            <tr class="hover:bg-slate-50/60">
                              <td class="px-3 py-2">
                                <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($r['course_id'] ?? '')) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)($r['course_name'] ?? '')) ?></div>
                              </td>
                              <td class="px-3 py-2 font-semibold text-slate-900"><?= (int)($r['waitlisted_cnt'] ?? 0) ?></td>
                              <td class="px-3 py-2 text-slate-600"><?= (int)($r['enrolled_cnt'] ?? 0) ?></td>
                            </tr>
                          <?php endforeach; ?>
                          <?php if (!($dash['top_waitlisted'] ?? [])): ?>
                            <tr><td class="px-3 py-4 text-center text-slate-500" colspan="3">No data.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        <?php elseif ($view === 'people'): ?>
          <?php
          $peoplePanel = trim((string)($_GET['people_panel'] ?? ''));
          if ($peoplePanel !== 'hold' && $peoplePanel !== 'info') {
              $peoplePanel = '';
          }
          $isStu = false;
          $isFac = false;
          if ($peopleId !== null) {
              try {
                  $stChk = $pdo->prepare('SELECT COUNT(*) FROM students WHERE student_id = ?');
                  $stChk->execute([$peopleId]);
                  $isStu = (int)$stChk->fetchColumn() > 0;
                  $fcChk = $pdo->prepare('SELECT COUNT(*) FROM faculty WHERE faculty_id = ?');
                  $fcChk->execute([$peopleId]);
                  $isFac = (int)$fcChk->fetchColumn() > 0;
              } catch (Throwable) {
              }
          }
          ?>
          <?php $idLookupHasSearch = $peopleId !== null; ?>
          <div class="<?= $idLookupHasSearch ? 'space-y-6' : 'space-y-8' ?>">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <h1 class="text-2xl font-semibold text-slate-900">ID lookup</h1>
                <?php if (!$idLookupHasSearch): ?>
                  <p class="mt-2 text-sm text-slate-600">Enter a student or faculty numeric ID to load their record.</p>
                <?php else: ?>
                  <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <span>Showing the record for</span>
                    <?php if ($isStu): ?>
                      <span class="inline-flex items-center gap-2 rounded-lg bg-sky-50 px-2 py-1 ring-1 ring-sky-200/80">
                        <span class="font-mono text-sm font-semibold tabular-nums text-sky-950"><?= (int)$peopleId ?></span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-sky-800">Student</span>
                      </span>
                    <?php elseif ($isFac): ?>
                      <span class="inline-flex items-center gap-2 rounded-lg bg-violet-50 px-2 py-1 ring-1 ring-violet-200/80">
                        <span class="font-mono text-sm font-semibold tabular-nums text-violet-950"><?= (int)$peopleId ?></span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-violet-800">Faculty</span>
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-2 py-1 ring-1 ring-slate-200">
                        <span class="font-mono text-sm font-semibold tabular-nums text-slate-900"><?= (int)$peopleId ?></span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-slate-600">User</span>
                      </span>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>
              </div>
              <?php if ($idLookupHasSearch): ?>
                <a class="shrink-0 text-sm font-semibold text-indigo-700 hover:text-indigo-900 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=people')) ?>">New search</a>
              <?php endif; ?>
            </div>

            <div class="<?= $idLookupHasSearch
                ? 'rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm'
                : 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm' ?>">
              <form class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" method="get" action="<?= htmlspecialchars(url('/admin.php')) ?>">
                <input type="hidden" name="view" value="people" />
                <div class="min-w-0 flex-1 sm:max-w-xs">
                  <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-id-q"><?= $idLookupHasSearch ? 'Lookup another ID' : 'Numeric ID' ?></label>
                  <input
                    id="people-id-q"
                    name="id"
                    value="<?= htmlspecialchars($peopleIdRaw) ?>"
                    inputmode="numeric"
                    pattern="\d+"
                    autocomplete="off"
                    placeholder="e.g. 12345"
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm text-slate-900 shadow-sm"
                  />
                </div>
                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 sm:shrink-0" type="submit"><?= $idLookupHasSearch ? 'Search again' : 'Search' ?></button>
              </form>
            </div>

            <?php if (!$idLookupHasSearch): ?>
              <div id="add-person" class="scroll-mt-6 rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-emerald-950">Add a person</h2>
                <p class="mt-2 text-sm leading-relaxed text-slate-700">
                  New students and faculty are usually loaded from registrar CSV import. After a record exists, search by ID above for enrollments, teaching assignments, and holds.
                  To add another <strong class="font-semibold text-slate-800">staff portal login</strong>, sign out and use Create account on the sign-in page.
                </p>
              </div>
            <?php endif; ?>

            <?php if ($peopleId !== null): ?>
            <?php
            $ur = null;
            try {
                $urow = $pdo->prepare('SELECT user_id, first_name, middle_name, last_name, user_type, dob, gender, apt_no, street, city, state, zip_code, email, phone_number FROM users WHERE user_id = ?');
                $urow->execute([$peopleId]);
                $ur = $urow->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable) {
                $urow = $pdo->prepare('SELECT user_id, first_name, last_name, user_type FROM users WHERE user_id = ?');
                $urow->execute([$peopleId]);
                $ur = $urow->fetch(PDO::FETCH_ASSOC);
            }

            $displayName = '';
            if ($ur) {
                $mn = trim((string)($ur['middle_name'] ?? ''));
                $displayName = trim((string)($ur['first_name'] ?? '') . ($mn !== '' ? ' ' . $mn . ' ' : ' ') . (string)($ur['last_name'] ?? ''));
            }

            $rows = [];
            $holdRows = [];
            $gradeRows = [];
            $stuGpa = null;
            $stuGradedCredits = 0;
            $stuTermEnrolled = 0;
            $stuActiveHoldsCnt = 0;
            $stuDepts = [];
            $stuUgType = '';
            $stuEnrolledAny = 0;
            $srows = [];
            $facultyMeta = null;
            $facDepts = [];
            $facSectionsThisTerm = 0;
            $facTotalSections = 0;
            $enrollmentByTerm = [];
            $enrollmentTermOrder = [];
            $facSectionsByTerm = [];
            $facSectionTermOrder = [];
            $stuDegreeGaps = [];
            $stuPrereqGaps = [];
            $stuUgLimitRow = null;
            $stuUgDetail = null;
            $peopleTermsList = [];

            if ($ur && $isStu) {
                try {
                    $en = $pdo->prepare('
                      SELECT t.code AS term_code, t.name AS term_name, c.course_id, c.course_name, COALESCE(c.credits, 0) AS course_credits,
                        e.status, s.section_id, s.meeting_days, s.meeting_time, s.room
                      FROM enrollments e
                      JOIN sections s ON s.section_id = e.section_id
                      JOIN courses c ON c.course_id = s.course_id
                      JOIN terms t ON t.term_id = s.term_id
                      WHERE e.student_id = ?
                      ORDER BY t.start_date DESC, c.course_id
                      LIMIT 60
                    ');
                    $en->execute([$peopleId]);
                    $rows = $en->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                foreach ($rows as $er) {
                    if (($er['status'] ?? '') === 'enrolled') {
                        ++$stuEnrolledAny;
                    }
                }

                try {
                    $hh = $pdo->prepare('SELECT hold_id, hold_type, note, is_active FROM student_holds WHERE student_id = ? ORDER BY created_at DESC LIMIT 40');
                    $hh->execute([$peopleId]);
                    $holdRows = $hh->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }
                foreach ($holdRows as $h) {
                    if ((int)($h['is_active'] ?? 0) === 1) {
                        ++$stuActiveHoldsCnt;
                    }
                }

                try {
                    $gpaStmt = $pdo->prepare('
                      SELECT
                        COALESCE(SUM(grade_points * credits_earned), 0) AS quality,
                        COALESCE(SUM(CASE WHEN credits_earned > 0 THEN credits_earned ELSE 0 END), 0) AS cr
                      FROM student_course_results
                      WHERE student_id = ?
                    ');
                    $gpaStmt->execute([$peopleId]);
                    $gpaRow = $gpaStmt->fetch(PDO::FETCH_ASSOC);
                    $qc = isset($gpaRow['quality']) ? (float)$gpaRow['quality'] : 0.0;
                    $credSum = isset($gpaRow['cr']) ? (float)$gpaRow['cr'] : 0.0;
                    $stuGradedCredits = (int)round($credSum);
                    if ($credSum > 0.0) {
                        $stuGpa = round($qc / $credSum, 2);
                    }
                } catch (Throwable) {
                }

                if ($currentTermId) {
                    try {
                        $te = $pdo->prepare('
                          SELECT COUNT(*) FROM enrollments e
                          JOIN sections s ON s.section_id = e.section_id
                          WHERE e.student_id = ? AND s.term_id = ? AND e.status = "enrolled"
                        ');
                        $te->execute([$peopleId, $currentTermId]);
                        $stuTermEnrolled = (int)$te->fetchColumn();
                    } catch (Throwable) {
                    }
                }

                try {
                    $dd = $pdo->prepare('
                      SELECT d.dept_id, d.dept_name, sd.date_of_declaration, sd.declaration_role
                      FROM student_departments sd
                      JOIN departments d ON d.dept_id = sd.dept_id
                      WHERE sd.student_id = ?
                      ORDER BY d.dept_name
                    ');
                    $dd->execute([$peopleId]);
                    $stuDepts = $dd->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                    $stuDepts = [];
                }

                $allDeptRows = [];
                $deptsAvailableToAdd = [];
                try {
                    $allDeptRows = $pdo->query('SELECT dept_id, dept_name FROM departments ORDER BY dept_name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }
                $haveDeptIds = [];
                foreach ($stuDepts ?? [] as $sdRow) {
                    $did = trim((string)($sdRow['dept_id'] ?? ''));
                    if ($did !== '') {
                        $haveDeptIds[$did] = true;
                    }
                }
                foreach ($allDeptRows as $dr) {
                    $id = trim((string)($dr['dept_id'] ?? ''));
                    if ($id !== '' && empty($haveDeptIds[$id])) {
                        $deptsAvailableToAdd[] = $dr;
                    }
                }

                try {
                    $gr = $pdo->prepare('
                      SELECT scr.letter_grade, scr.grade_points, scr.credits_earned, c.course_id, c.course_name, t.code AS term_code, t.name AS term_name
                      FROM student_course_results scr
                      JOIN courses c ON c.course_id = scr.course_id
                      JOIN terms t ON t.term_id = scr.term_id
                      WHERE scr.student_id = ?
                      ORDER BY t.start_date DESC, c.course_id
                      LIMIT 40
                    ');
                    $gr->execute([$peopleId]);
                    $gradeRows = $gr->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                try {
                    $ug = $pdo->prepare('SELECT student_type, academic_year_level, enrollment_intensity FROM undergrad_students WHERE student_id = ?');
                    $ug->execute([$peopleId]);
                    $stuUgDetail = $ug->fetch(PDO::FETCH_ASSOC) ?: null;
                    if ($stuUgDetail) {
                        $stuUgType = (string)($stuUgDetail['student_type'] ?? '');
                    }
                } catch (Throwable) {
                }

                $stuEnrollmentDisplay = '';
                $stuClassYearDisplay = '';
                if ($stuUgDetail) {
                    $stuClassYearDisplay = trim((string)($stuUgDetail['academic_year_level'] ?? ''));
                    $stuEnrollmentDisplay = admin_ug_display_enrollment($stuUgDetail);
                }

                try {
                    $ucl = $pdo->prepare('SELECT total_credit_earned, year, max_credit, min_credit FROM ug_credit_limits WHERE student_id = ? LIMIT 1');
                    $ucl->execute([$peopleId]);
                    $stuUgLimitRow = $ucl->fetch(PDO::FETCH_ASSOC) ?: null;
                } catch (Throwable) {
                }

                try {
                    $dg = $pdo->prepare('
                      SELECT DISTINCT c.course_id, c.course_name, c.credits, d.dept_name, sd.declaration_role, drc.requirement_kind
                      FROM student_departments sd
                      JOIN departments d ON d.dept_id = sd.dept_id
                      JOIN degree_requirement_courses drc ON drc.dept_id = sd.dept_id
                        AND (
                          drc.requirement_kind = "both"
                          OR (drc.requirement_kind = "major" AND sd.declaration_role = "major")
                          OR (drc.requirement_kind = "minor" AND sd.declaration_role = "minor")
                        )
                      JOIN courses c ON c.course_id = drc.course_id
                      WHERE sd.student_id = ?
                      AND NOT EXISTS (
                        SELECT 1 FROM student_course_results scr
                        WHERE scr.student_id = sd.student_id AND scr.course_id = c.course_id AND scr.grade_points > 0
                      )
                      ORDER BY d.dept_name, c.course_id
                    ');
                    $dg->execute([$peopleId]);
                    $stuDegreeGaps = $dg->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                try {
                    $pg = $pdo->prepare('
                      SELECT DISTINCT cp.prereq_course_id, pc.course_name AS prereq_name,
                        fc.course_id AS for_course_id, fc.course_name AS for_course_name
                      FROM enrollments e
                      JOIN sections sec ON sec.section_id = e.section_id
                      JOIN courses fc ON fc.course_id = sec.course_id
                      JOIN course_prereqs cp ON cp.course_id = sec.course_id
                      JOIN courses pc ON pc.course_id = cp.prereq_course_id
                      WHERE e.student_id = ? AND e.status = "enrolled"
                      AND NOT EXISTS (
                        SELECT 1 FROM student_course_results scr
                        WHERE scr.student_id = e.student_id AND scr.course_id = cp.prereq_course_id AND scr.grade_points > 0
                      )
                      ORDER BY fc.course_id, cp.prereq_course_id
                    ');
                    $pg->execute([$peopleId]);
                    $stuPrereqGaps = $pg->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                try {
                    $peopleTermsList = $pdo->query('SELECT term_id, code, name FROM terms ORDER BY start_date DESC LIMIT 36')->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                foreach ($rows as $r) {
                    $tc = trim((string)($r['term_code'] ?? ''));
                    if ($tc === '') {
                        $tc = 'Other';
                    }
                    if (!isset($enrollmentByTerm[$tc])) {
                        $enrollmentByTerm[$tc] = [];
                        $enrollmentTermOrder[] = $tc;
                    }
                    $enrollmentByTerm[$tc][] = $r;
                }
            }

            if ($ur && $isFac) {
                try {
                    $sec = $pdo->prepare('
                      SELECT t.code AS term_code, t.name AS term_name, t.start_date, s.section_id, c.course_id, c.course_name,
                        s.meeting_days, s.meeting_time, s.room, s.term_id
                      FROM sections s
                      JOIN courses c ON c.course_id = s.course_id
                      JOIN terms t ON t.term_id = s.term_id
                      WHERE s.faculty_id = ?
                      ORDER BY t.start_date DESC, c.course_id
                      LIMIT 80
                    ');
                    $sec->execute([$peopleId]);
                    $srows = $sec->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }
                $facTotalSections = count($srows);
                if ($currentTermId) {
                    foreach ($srows as $sr) {
                        if ((int)($sr['term_id'] ?? 0) === $currentTermId) {
                            ++$facSectionsThisTerm;
                        }
                    }
                }
                try {
                    $fm = $pdo->prepare('SELECT office_number, `rank`, faculty_type, email, phone_number, hire_date FROM faculty WHERE faculty_id = ?');
                    $fm->execute([$peopleId]);
                    $facultyMeta = $fm->fetch(PDO::FETCH_ASSOC) ?: null;
                } catch (Throwable) {
                }
                try {
                    $fd = $pdo->prepare('
                      SELECT d.dept_id, d.dept_name, fd.percent_time, fd.date_of_appointment
                      FROM faculty_departments fd
                      JOIN departments d ON d.dept_id = fd.dept_id
                      WHERE fd.faculty_id = ?
                      ORDER BY d.dept_name
                    ');
                    $fd->execute([$peopleId]);
                    $facDepts = $fd->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (Throwable) {
                }

                foreach ($srows as $r) {
                    $tc = trim((string)($r['term_code'] ?? ''));
                    if ($tc === '') {
                        $tc = 'Other';
                    }
                    if (!isset($facSectionsByTerm[$tc])) {
                        $facSectionsByTerm[$tc] = [];
                        $facSectionTermOrder[] = $tc;
                    }
                    $facSectionsByTerm[$tc][] = $r;
                }
            }
            ?>
            <div class="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
              <?php if (!$ur): ?>
                <div class="border-b border-slate-200 bg-slate-100 px-5 py-4">
                  <div class="text-sm font-semibold text-slate-800">No match</div>
                  <p class="mt-1 text-sm text-slate-600">There is no user with ID <span class="font-mono font-semibold"><?= (int)$peopleId ?></span>.</p>
                </div>
                <div class="px-5 py-4">
                  <p class="text-sm text-slate-500">Try another ID, or check the number in Master schedule.</p>
                </div>
              <?php else: ?>
                <?php
                $peopleHeroBorder = 'border-indigo-100';
                $peopleHeroBg = 'bg-gradient-to-r from-indigo-600 to-sky-600';
                $peopleHeroSub = 'text-indigo-100';
                $peopleHeroDot = 'text-indigo-200';
                if ($isFac && !$isStu) {
                    $peopleHeroBorder = 'border-violet-200';
                    $peopleHeroBg = 'bg-gradient-to-r from-violet-600 to-fuchsia-600';
                    $peopleHeroSub = 'text-violet-100';
                    $peopleHeroDot = 'text-fuchsia-200';
                } elseif (!$isStu && !$isFac) {
                    $peopleHeroBorder = 'border-slate-200';
                    $peopleHeroBg = 'bg-gradient-to-r from-slate-600 to-slate-800';
                    $peopleHeroSub = 'text-slate-200';
                    $peopleHeroDot = 'text-slate-400';
                }
                ?>
                <div class="border-b <?= htmlspecialchars($peopleHeroBorder, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($peopleHeroBg, ENT_QUOTES, 'UTF-8') ?> px-5 py-4 text-white">
                  <div class="text-xs font-semibold uppercase tracking-wide opacity-90">Record</div>
                  <div class="mt-1 text-xl font-bold tracking-tight"><?= htmlspecialchars($displayName) ?></div>
                  <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm <?= htmlspecialchars($peopleHeroSub, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="font-mono font-semibold text-white">ID <?= (int)$peopleId ?></span>
                    <span class="<?= htmlspecialchars($peopleHeroDot, ENT_QUOTES, 'UTF-8') ?>">·</span>
                    <span><?= htmlspecialchars((string)$ur['user_type']) ?></span>
                    <?php if ($isStu): ?>
                      <span class="rounded-full bg-sky-400/25 px-2 py-0.5 text-xs font-semibold text-white ring-1 ring-white/20">Student</span>
                    <?php elseif ($isFac): ?>
                      <span class="rounded-full bg-fuchsia-400/25 px-2 py-0.5 text-xs font-semibold text-white ring-1 ring-white/20">Faculty</span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="p-5">
                <?php
                $__empty = static function (?string $v): bool {
                    return $v === null || trim($v) === '';
                };
                $__fmtDate = static function ($d): string {
                    if ($d === null || $d === '') {
                        return '—';
                    }
                    return htmlspecialchars((string)$d);
                };
                ?>
                <?php if ($isStu): ?>
                  <?php
                  $peopleHoldPresets = admin_people_hold_presets();
                  $peopleGenderOpts = admin_people_genders();
                  $peopleStateCodes = admin_people_us_state_codes();
                  $peopleYearOpts = admin_people_academic_year_levels();
                  $peopleIntOpts = admin_people_enrollment_intensity_options();
                  $peopleCurGender = trim((string)($ur['gender'] ?? ''));
                  $peopleCurState = strtoupper(trim((string)($ur['state'] ?? '')));
                  ?>
                  <div class="mt-1 flex flex-wrap items-center gap-3" id="people-student-actions" data-initial-panel="<?= htmlspecialchars($peoplePanel, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" id="people-btn-open-info" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-950 shadow-sm hover:bg-sky-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">Update info</button>
                    <button type="button" id="people-btn-open-hold" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-950 shadow-sm hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Holds</button>
                    <span class="text-xs text-slate-500">Opens a full-screen panel over this page.</span>
                  </div>
                  <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">Student snapshot</p>
                  <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Cumulative GPA</div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= $stuGpa !== null ? htmlspecialchars((string)$stuGpa) : '—' ?></div>
                      <div class="mt-1 text-xs text-slate-500"><?= $stuGradedCredits > 0 ? htmlspecialchars((string)$stuGradedCredits) . ' graded credits recorded' : 'No grades in transcript yet' ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500"><?= $currentTermCode !== null ? 'This term · ' . htmlspecialchars($currentTermCode) : 'This term' ?></div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int)$stuTermEnrolled ?></div>
                      <div class="mt-1 text-xs text-slate-500"><?= $currentTermCode !== null ? 'Sections enrolled (enrolled status)' : 'Set a current term on the timeline to populate' ?></div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Enrolled registrations</div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int)$stuEnrolledAny ?></div>
                      <div class="mt-1 text-xs text-slate-500">All terms · status = enrolled</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Active holds</div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int)$stuActiveHoldsCnt ?></div>
                      <div class="mt-1 text-xs text-slate-500"><?= $stuActiveHoldsCnt > 0 ? 'Registration may be blocked' : 'None blocking' ?></div>
                    </div>
                  </div>

                  <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/40 px-4 py-3 text-sm text-slate-800">
                    <span class="font-semibold text-slate-900">Credits &amp; GPA — </span>
                    <span class="text-slate-700">Transcript graded credits: <strong class="tabular-nums"><?= (int)$stuGradedCredits ?></strong>.</span>
                    <?php if ($stuUgLimitRow): ?>
                      <span class="text-slate-700"> Registrar cumulative (<code class="rounded bg-white px-1 text-xs">ug_credit_limits</code>): <strong class="tabular-nums"><?= (int)($stuUgLimitRow['total_credit_earned'] ?? 0) ?></strong><?php if (isset($stuUgLimitRow['max_credit'], $stuUgLimitRow['min_credit'])): ?> <span class="text-slate-600">(load band <?= (int)$stuUgLimitRow['min_credit'] ?>–<?= (int)$stuUgLimitRow['max_credit'] ?> cr.)</span><?php endif; ?>.</span>
                    <?php else: ?>
                      <span class="text-slate-600"> No <code class="rounded bg-slate-100 px-1 text-xs">ug_credit_limits</code> row yet — usually loaded from <span class="font-medium">UG_fulltime.csv</span> / <span class="font-medium">UG_parttime.csv</span> import; admins can seed totals under Update info.</span>
                    <?php endif; ?>
                    <span class="block mt-1 text-xs text-slate-600">GPA above is computed from transcript grades only. Use <strong class="font-semibold text-slate-700">Update info</strong> to edit grades — GPA updates after save.</span>
                  </div>

                  <div class="mt-8 grid gap-6 lg:grid-cols-12">
                    <div class="space-y-4 lg:col-span-5">
                      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Personal &amp; contact</h3>
                        <dl class="mt-3 space-y-2 text-sm text-slate-700">
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">DOB</dt><dd class="font-medium"><?= $__fmtDate($ur['dob'] ?? null) ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Gender</dt><dd class="font-medium"><?= $__empty(($ur['gender'] ?? null)) ? '—' : htmlspecialchars((string)$ur['gender']) ?></dd></div>
                          <?php if ($stuUgDetail): ?>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Class year</dt><dd class="font-medium"><?= $stuClassYearDisplay !== '' ? htmlspecialchars($stuClassYearDisplay) : '—' ?><?php if ($stuClassYearDisplay === ''): ?><span class="block text-xs font-normal text-slate-500">Not on file — set under Update info.</span><?php endif; ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Enrollment</dt><dd class="font-medium"><?= $stuEnrollmentDisplay !== '' ? htmlspecialchars($stuEnrollmentDisplay) : '—' ?></dd></div>
                          <?php endif; ?>
                          <?php if ($stuUgType): ?>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Import category</dt><dd class="font-medium text-slate-600"><?= htmlspecialchars((string)$stuUgType) ?> <span class="text-xs">(legacy CSV)</span></dd></div>
                          <?php endif; ?>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="break-all font-medium"><?= $__empty(($ur['email'] ?? null)) ? '—' : htmlspecialchars((string)$ur['email']) ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium"><?= $__empty(($ur['phone_number'] ?? null)) ? '—' : htmlspecialchars((string)$ur['phone_number']) ?></dd></div>
                          <div class="border-t border-slate-100 pt-2"><span class="text-slate-500">Address</span>
                            <?php if ($__empty($ur['street'] ?? null) && $__empty($ur['city'] ?? null)): ?>
                              <p class="mt-1 text-slate-600">—</p>
                            <?php else: ?>
                              <p class="mt-1 leading-relaxed text-slate-800"><?= htmlspecialchars(trim(((string)($ur['apt_no'] ?? '') . ' ' . (string)($ur['street'] ?? '')))) ?><br /><?= htmlspecialchars(trim(((string)($ur['city'] ?? '') . ', ' . (string)($ur['state'] ?? '') . ' ' . (string)($ur['zip_code'] ?? '')))) ?></p>
                            <?php endif; ?>
                          </div>
                        </dl>
                      </div>
                      <?php if ($stuDepts): ?>
                      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Departments</h3>
                        <ul class="mt-3 space-y-2 text-sm">
                          <?php foreach ($stuDepts as $sd): ?>
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                              <span class="font-medium text-slate-900"><?= htmlspecialchars((string)($sd['dept_name'] ?? '')) ?></span>
                              <span class="text-slate-500"> (<?= htmlspecialchars((string)($sd['dept_id'] ?? '')) ?>)</span>
                              <?php $sdRole = (string)($sd['declaration_role'] ?? 'major'); ?>
                              <span class="ml-2 inline-flex rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700"><?= $sdRole === 'minor' ? 'Minor' : 'Major' ?></span>
                              <?php if (!empty($sd['date_of_declaration'])): ?>
                                <div class="mt-0.5 text-xs text-slate-500">Declared <?= htmlspecialchars((string)$sd['date_of_declaration']) ?></div>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                      <?php endif; ?>
                    </div>

                    <div class="space-y-6 lg:col-span-7">
                      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                          <h3 class="text-sm font-semibold text-slate-900">Course enrollments</h3>
                          <p class="mt-0.5 text-xs text-slate-500">Pick a term tab (e.g. FA27), then review courses and status.</p>
                        </div>
                        <?php if (!$enrollmentTermOrder): ?>
                          <div class="px-4 py-8 text-center text-sm text-slate-500">No enrollment rows.</div>
                        <?php else: ?>
                          <?php if (count($enrollmentTermOrder) > 1): ?>
                            <div class="flex flex-wrap gap-1 border-b border-slate-100 bg-slate-50/60 px-2 pt-2" role="tablist" aria-label="Enrollment by term">
                              <?php foreach ($enrollmentTermOrder as $tc): ?>
                                <button type="button" role="tab" class="people-enroll-tab -mb-px rounded-t-lg border border-slate-200 border-b-0 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" data-term="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tc) ?></button>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                          <?php foreach ($enrollmentTermOrder as $ti => $tc): ?>
                            <?php $termRows = $enrollmentByTerm[$tc] ?? []; ?>
                            <div class="people-enroll-panel overflow-x-auto" data-term="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>" <?= $ti > 0 ? 'hidden' : '' ?>>
                              <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                  <tr>
                                    <th class="px-3 py-2">Course</th>
                                    <th class="px-3 py-2">Cr</th>
                                    <th class="px-3 py-2">When / where</th>
                                    <th class="px-3 py-2">Status</th>
                                  </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                  <?php foreach ($termRows as $r): ?>
                                    <tr class="hover:bg-slate-50/60">
                                      <td class="px-3 py-2 align-top">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($r['course_id'] ?? '')) ?></div>
                                        <div class="text-xs text-slate-600"><?= htmlspecialchars((string)($r['course_name'] ?? '')) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars((string)($r['term_name'] ?? '')) ?></div>
                                      </td>
                                      <td class="px-3 py-2 align-top tabular-nums text-slate-700"><?= (int)($r['course_credits'] ?? 0) ?></td>
                                      <td class="px-3 py-2 align-top text-xs text-slate-600">
                                        <?php if (!empty($r['meeting_days']) || !empty($r['meeting_time']) || !empty($r['room'])): ?>
                                          <?= htmlspecialchars(trim(((string)($r['meeting_days'] ?? '') . ' ' . (string)($r['meeting_time'] ?? '')))) ?>
                                          <?php if (!empty($r['room'])): ?><span class="text-slate-500"> · <?= htmlspecialchars((string)$r['room']) ?></span><?php endif; ?>
                                        <?php else: ?>
                                          <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                      </td>
                                      <td class="px-3 py-2 align-top">
                                        <span class="inline-flex rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs font-semibold text-slate-800"><?= htmlspecialchars((string)($r['status'] ?? '')) ?></span>
                                      </td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>

                      <?php if ($gradeRows): ?>
                      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                          <h3 class="text-sm font-semibold text-slate-900">Transcript (graded courses)</h3>
                          <p class="mt-0.5 text-xs text-slate-500">From registrar grade records.</p>
                        </div>
                        <div class="overflow-x-auto">
                          <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                              <tr>
                                <th class="px-3 py-2">Term</th>
                                <th class="px-3 py-2">Course</th>
                                <th class="px-3 py-2">Grade</th>
                                <th class="px-3 py-2">Pts</th>
                                <th class="px-3 py-2">Cr</th>
                              </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                              <?php foreach ($gradeRows as $g): ?>
                                <tr class="hover:bg-slate-50/60">
                                  <td class="px-3 py-2 align-top">
                                    <div class="font-medium text-slate-900"><?= htmlspecialchars((string)($g['term_code'] ?? '')) ?></div>
                                    <div class="text-xs text-slate-500"><?= htmlspecialchars((string)($g['term_name'] ?? '')) ?></div>
                                  </td>
                                  <td class="px-3 py-2 align-top">
                                    <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($g['course_id'] ?? '')) ?></div>
                                    <div class="text-xs text-slate-600"><?= htmlspecialchars((string)($g['course_name'] ?? '')) ?></div>
                                  </td>
                                  <td class="px-3 py-2 align-top font-semibold"><?= htmlspecialchars((string)($g['letter_grade'] ?? '')) ?></td>
                                  <td class="px-3 py-2 align-top tabular-nums"><?= htmlspecialchars((string)$g['grade_points']) ?></td>
                                  <td class="px-3 py-2 align-top tabular-nums"><?= (int)($g['credits_earned'] ?? 0) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div id="people-overlay-info" class="people-stu-overlay fixed inset-0 z-[70] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="people-overlay-info-title">
                    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" data-close-stu-overlay="info"></div>
                    <div class="relative z-10 flex max-h-[min(90vh,880px)] w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                      <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                        <h2 id="people-overlay-info-title" class="text-lg font-semibold text-slate-900">Update information</h2>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-200/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" data-close-stu-overlay="info">Close</button>
                      </div>
                      <div class="min-h-0 overflow-y-auto">
                    <div id="people-panel-info" class="space-y-6 p-4 sm:p-5">
                      <div class="rounded-2xl border border-sky-200/80 bg-sky-50/50 p-4 sm:p-5 shadow-sm">
                        <h3 class="text-base font-semibold text-slate-900">Update information</h3>
                        <p class="mt-1 text-xs text-slate-600">Use this section for contact, address, class year, enrollment, <strong class="font-semibold text-slate-800">majors and minors</strong> (switch roles or add a declaration below), and registrar credits. Leave optional fields blank to keep current values.</p>
                        <?php if ($canManageHolds): ?>
                        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId . '&people_panel=info')) ?>">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                          <input type="hidden" name="action" value="people_update_student" />
                          <input type="hidden" name="student_id" value="<?= (int)$peopleId ?>" />
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-gender">Gender</label>
                            <select id="people-up-gender" name="gender" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                              <option value="__keep__">Keep current<?= $peopleCurGender !== '' ? ' (' . htmlspecialchars($peopleCurGender) . ')' : '' ?></option>
                              <?php foreach ($peopleGenderOpts as $g): ?>
                                <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-state">State (US)</label>
                            <select id="people-up-state" name="state" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-mono">
                              <option value="__keep__">Keep current<?= $peopleCurState !== '' ? ' (' . htmlspecialchars($peopleCurState) . ')' : '' ?></option>
                              <?php foreach ($peopleStateCodes as $sc): ?>
                                <option value="<?= htmlspecialchars($sc) ?>"><?= htmlspecialchars($sc) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                            <div>
                              <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-email">Email</label>
                              <input id="people-up-email" name="email" type="email" autocomplete="off" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                            </div>
                            <div>
                              <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-phone">Phone</label>
                              <input id="people-up-phone" name="phone" type="text" inputmode="tel" autocomplete="off" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-apt">Apt / unit</label>
                            <input id="people-up-apt" name="apt_no" type="text" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-street">Street</label>
                            <input id="people-up-street" name="street" type="text" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-city">City</label>
                            <input id="people-up-city" name="city" type="text" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-zip">ZIP</label>
                            <input id="people-up-zip" name="zip_code" type="text" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-year">Class year</label>
                            <select id="people-up-year" name="academic_year_level" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                              <option value="__keep__">Keep current<?php $cy = trim((string)(($stuUgDetail ?? [])['academic_year_level'] ?? '')); echo $cy !== '' ? ' (' . htmlspecialchars($cy) . ')' : ''; ?></option>
                              <?php foreach ($peopleYearOpts as $y): ?>
                                <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-int">Full-time / part-time</label>
                            <select id="people-up-int" name="enrollment_intensity" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                              <option value="__keep__">Keep current<?php
                              $ciRaw = trim((string)(($stuUgDetail ?? [])['enrollment_intensity'] ?? ''));
                              $ciShow = $ciRaw !== '' ? $ciRaw : (($stuUgDetail ?? null) ? admin_ug_display_enrollment($stuUgDetail) : '');
                              echo $ciShow !== '' ? ' (' . htmlspecialchars($ciShow) . ')' : '';
                              ?></option>
                              <?php foreach ($peopleIntOpts as $io): ?>
                                <option value="<?= htmlspecialchars($io) ?>"><?= htmlspecialchars($io) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <?php if ($stuDepts): ?>
                          <div class="sm:col-span-2 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Major / minor per declaration</div>
                            <div class="mt-3 space-y-3">
                              <?php foreach ($stuDepts as $sd): ?>
                                <?php $did = htmlspecialchars((string)($sd['dept_id'] ?? '')); ?>
                                <div class="flex flex-wrap items-center gap-3">
                                  <span class="text-sm font-medium text-slate-800"><?= htmlspecialchars((string)($sd['dept_name'] ?? '')) ?> <span class="text-slate-500">(<?= $did ?>)</span></span>
                                  <select name="declaration_role[<?= $did ?>]" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm">
                                    <?php $dr = (string)($sd['declaration_role'] ?? 'major'); ?>
                                    <option value="major" <?= $dr === 'major' ? 'selected' : '' ?>>Major</option>
                                    <option value="minor" <?= $dr === 'minor' ? 'selected' : '' ?>>Minor</option>
                                  </select>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                          <?php endif; ?>
                          <?php if ($deptsAvailableToAdd): ?>
                          <div class="sm:col-span-2 rounded-xl border border-sky-100 bg-sky-50/50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-sky-900">Add major or minor</div>
                            <p class="mt-1 text-xs text-slate-600">Pick a department that is not already listed above and choose whether it is a major or minor, then click <span class="font-semibold">Save updates</span>.</p>
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                              <div class="min-w-0 flex-1 sm:max-w-xs">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-add-dept">Department</label>
                                <select id="people-add-dept" name="add_declaration_dept" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                  <option value="">Select department…</option>
                                  <?php foreach ($deptsAvailableToAdd as $dr): ?>
                                    <?php
                                    $optId = (string)($dr['dept_id'] ?? '');
                                    $optName = (string)($dr['dept_name'] ?? '');
                                    $optLabel = $optName !== '' ? $optName . ' (' . $optId . ')' : $optId;
                                    ?>
                                    <option value="<?= htmlspecialchars($optId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($optLabel) ?></option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                              <div class="sm:w-40">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-add-role">Role</label>
                                <select id="people-add-role" name="add_declaration_role" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                  <option value="major">Major</option>
                                  <option value="minor">Minor</option>
                                </select>
                              </div>
                            </div>
                          </div>
                          <?php elseif ($canManageHolds && $deptsAvailableToAdd === [] && $allDeptRows !== []): ?>
                          <div class="sm:col-span-2 rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-3 text-xs text-slate-600">
                            No departments left to add — this student already has a declaration for every department in the catalog.
                          </div>
                          <?php elseif ($canManageHolds && $allDeptRows === []): ?>
                          <div class="sm:col-span-2 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 text-xs text-amber-950">
                            Cannot add a major or minor until there are rows in <code class="rounded bg-white px-1">departments</code> (run registrar import or insert departments first).
                          </div>
                          <?php endif; ?>
                          <?php if ($isAdmin): ?>
                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-up-tce">Registrar total credits earned (<code class="text-[11px]">ug_credit_limits.total_credit_earned</code>)</label>
                            <input id="people-up-tce" name="total_credit_earned" type="number" min="0" step="1" placeholder="<?= $stuUgLimitRow ? (string)(int)($stuUgLimitRow['total_credit_earned'] ?? 0) : 'e.g. 90' ?>" class="mt-1 max-w-xs rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono" />
                            <p class="mt-1 text-xs text-slate-500">Admin only. Updates or creates that row. If the row did not exist, min/max load bands use part-time vs full-time defaults from legacy <code class="rounded bg-slate-100 px-1">student_type</code> until CSV import fills real limits.</p>
                          </div>
                          <?php endif; ?>
                          <div class="sm:col-span-2">
                            <button type="submit" class="rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-600">Save updates</button>
                          </div>
                        </form>
                        <?php else: ?>
                        <p class="mt-3 text-sm text-slate-600">Your role cannot edit these fields.</p>
                        <?php endif; ?>
                      </div>

                      <div>
                        <h3 class="text-sm font-semibold text-slate-900">Degree &amp; prerequisite gaps</h3>
                        <p class="mt-1 text-xs text-slate-500">Missing courses use the <code class="rounded bg-slate-100 px-1">degree_requirement_courses</code> catalog (by declared major/minor). Populate it for your programs if this list is empty.</p>
                        <?php if ($stuDegreeGaps): ?>
                          <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full text-left text-sm">
                              <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500"><tr><th class="px-3 py-2">Dept</th><th class="px-3 py-2">Course</th><th class="px-3 py-2">Cr</th><th class="px-3 py-2">Role</th></tr></thead>
                              <tbody class="divide-y divide-slate-100">
                                <?php foreach ($stuDegreeGaps as $dg): ?>
                                  <tr>
                                    <td class="px-3 py-2"><?= htmlspecialchars((string)($dg['dept_name'] ?? '')) ?></td>
                                    <td class="px-3 py-2"><span class="font-semibold"><?= htmlspecialchars((string)($dg['course_id'] ?? '')) ?></span> · <?= htmlspecialchars((string)($dg['course_name'] ?? '')) ?></td>
                                    <td class="px-3 py-2 tabular-nums"><?= (int)($dg['credits'] ?? 0) ?></td>
                                    <td class="px-3 py-2 text-xs"><?= htmlspecialchars((string)($dg['declaration_role'] ?? '')) ?> / <?= htmlspecialchars((string)($dg['requirement_kind'] ?? '')) ?></td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                        <?php else: ?>
                          <p class="mt-2 text-sm text-slate-600">No catalog gaps found (or no requirements loaded for this student’s departments).</p>
                        <?php endif; ?>

                        <h4 class="mt-6 text-xs font-semibold uppercase tracking-wide text-slate-500">Prerequisite gaps (current enrollments)</h4>
                        <?php if ($stuPrereqGaps): ?>
                          <ul class="mt-2 space-y-1 text-sm text-slate-800">
                            <?php foreach ($stuPrereqGaps as $pg): ?>
                              <li class="rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2">
                                Needs <strong><?= htmlspecialchars((string)($pg['prereq_course_id'] ?? '')) ?></strong> <?= htmlspecialchars((string)($pg['prereq_name'] ?? '')) ?>
                                <span class="text-slate-600"> — before <?= htmlspecialchars((string)($pg['for_course_id'] ?? '')) ?> <?= htmlspecialchars((string)($pg['for_course_name'] ?? '')) ?></span>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <p class="mt-2 text-sm text-slate-600">None detected for active enrollments (or no prereqs in <code class="rounded bg-slate-100 px-1">course_prereqs</code>).</p>
                        <?php endif; ?>
                      </div>

                      <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm">
                        <span class="font-semibold text-slate-900">Roster &amp; registration — </span>
                        <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=registration&student_id=' . (int)$peopleId . ($currentTermCode !== null ? '&term=' . rawurlencode($currentTermCode) : ''))) ?>">Open registration / add-drop for this student</a>
                      </div>

                      <?php if ($isAdmin): ?>
                      <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-900">Registrar: add or replace transcript grade</h3>
                        <p class="mt-1 text-xs text-slate-500">Creates or updates one row in <code class="rounded bg-slate-100 px-1">student_course_results</code>. Letter grades map to standard quality points; override points only if needed.</p>
                        <form class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId . '&people_panel=info')) ?>">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                          <input type="hidden" name="action" value="people_scr_upsert" />
                          <input type="hidden" name="student_id" value="<?= (int)$peopleId ?>" />
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="scr-course">Course ID</label>
                            <input id="scr-course" name="course_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-sm uppercase" placeholder="CS101" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="scr-term">Term</label>
                            <select id="scr-term" name="term_id" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                              <option value="">Select term…</option>
                              <?php foreach ($peopleTermsList as $tm): ?>
                                <option value="<?= (int)$tm['term_id'] ?>"><?= htmlspecialchars((string)($tm['code'] ?? '')) ?> — <?= htmlspecialchars((string)($tm['name'] ?? '')) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="scr-letter">Letter grade</label>
                            <input id="scr-letter" name="letter_grade" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm uppercase" placeholder="A-, B+, F" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="scr-gp">Quality points (override)</label>
                            <input id="scr-gp" name="grade_points" type="text" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-sm" placeholder="Optional if letter maps" />
                          </div>
                          <div class="lg:col-span-4">
                            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Save grade row</button>
                          </div>
                        </form>
                      </div>
                      <?php endif; ?>
                    </div>
                      </div>
                    </div>
                  </div>

                  <div id="people-overlay-hold" class="people-stu-overlay fixed inset-0 z-[70] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="people-overlay-hold-title">
                    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" data-close-stu-overlay="hold"></div>
                    <div class="relative z-10 flex max-h-[min(90vh,760px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                      <div class="flex shrink-0 items-center justify-between gap-3 border-b border-amber-100 bg-amber-50/90 px-4 py-3">
                        <h2 id="people-overlay-hold-title" class="text-lg font-semibold text-slate-900">Holds</h2>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-amber-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500" data-close-stu-overlay="hold">Close</button>
                      </div>
                      <div class="min-h-0 overflow-y-auto p-4 sm:p-5">
                      <h3 class="text-sm font-semibold text-slate-900">Registration holds</h3>
                      <?php if ($canManageHolds): ?>
                        <form class="mt-4 grid gap-4 sm:max-w-xl" method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId . '&people_panel=hold')) ?>" id="people-hold-form">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                          <input type="hidden" name="action" value="hold_add_people" />
                          <input type="hidden" name="student_id" value="<?= (int)$peopleId ?>" />
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-hold-type">Hold type</label>
                            <select id="people-hold-type" name="hold_type_select" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm" required>
                              <option value="" disabled selected>Select a hold…</option>
                              <?php foreach ($peopleHoldPresets as $hp): ?>
                                <option value="<?= htmlspecialchars($hp) ?>"><?= htmlspecialchars($hp) ?></option>
                              <?php endforeach; ?>
                              <option value="__custom__">Custom (type below)</option>
                            </select>
                          </div>
                          <div id="people-hold-custom-wrap" class="hidden">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-hold-custom">Custom hold label</label>
                            <input id="people-hold-custom" name="hold_type_custom" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="e.g. Immunization proof pending" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-hold-note-preset">Note</label>
                            <select id="people-hold-note-preset" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                              <option value="">No preset</option>
                              <option value="Contact bursar office.">Bursar — contact office</option>
                              <option value="Advisor clearance required.">Academic — advisor clearance</option>
                              <option value="Complete immunization documentation.">Immunization documentation</option>
                              <option value="Financial aid paperwork incomplete.">Financial aid paperwork</option>
                              <option value="Registrar review pending.">Registrar review</option>
                            </select>
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="people-hold-note">Note detail <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                            <textarea id="people-hold-note" name="note" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Additional context"></textarea>
                          </div>
                          <div>
                            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Add hold</button>
                          </div>
                        </form>
                      <?php else: ?>
                        <p class="mt-3 text-sm text-slate-600">Your role cannot add or clear holds.</p>
                      <?php endif; ?>
                      <ul class="mt-6 space-y-2 text-sm">
                        <?php foreach ($holdRows as $h): ?>
                          <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                            <span><?= htmlspecialchars((string)$h['hold_type']) ?><?= (int)$h['is_active'] === 1 ? '' : ' (cleared)' ?></span>
                            <?php if ((int)$h['is_active'] === 1 && $canManageHolds): ?>
                              <form method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId . '&people_panel=hold')) ?>">
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
                      </div>
                    </div>
                  </div>
                  <script>
                  (function () {
                    function wireTermTabs(tabSel, panelSel) {
                      var tabs = document.querySelectorAll(tabSel);
                      var panels = document.querySelectorAll(panelSel);
                      if (!tabs.length || !panels.length) return;
                      function show(term) {
                        panels.forEach(function (p) {
                          p.hidden = p.getAttribute('data-term') !== term;
                        });
                        tabs.forEach(function (b) {
                          var on = b.getAttribute('data-term') === term;
                          b.setAttribute('aria-selected', on ? 'true' : 'false');
                          b.classList.toggle('border-indigo-600', on);
                          b.classList.toggle('bg-white', on);
                          b.classList.toggle('text-indigo-900', on);
                          b.classList.toggle('border-transparent', !on);
                          b.classList.toggle('text-slate-600', !on);
                          b.classList.toggle('bg-transparent', !on);
                        });
                      }
                      tabs.forEach(function (b) {
                        b.addEventListener('click', function () { show(b.getAttribute('data-term')); });
                      });
                      show(tabs[0].getAttribute('data-term'));
                    }
                    wireTermTabs('.people-enroll-tab', '.people-enroll-panel');
                    wireTermTabs('.people-facsec-tab', '.people-facsec-panel');

                    var ovInfo = document.getElementById('people-overlay-info');
                    var ovHold = document.getElementById('people-overlay-hold');
                    var btnInfo = document.getElementById('people-btn-open-info');
                    var btnHold = document.getElementById('people-btn-open-hold');
                    var actWrap = document.getElementById('people-student-actions');
                    function openStuOverlay(which) {
                      if (which === 'info' && ovInfo) {
                        if (ovHold) { ovHold.classList.add('hidden'); ovHold.classList.remove('flex'); }
                        ovInfo.classList.remove('hidden');
                        ovInfo.classList.add('flex');
                        document.documentElement.classList.add('overflow-hidden');
                      } else if (which === 'hold' && ovHold) {
                        if (ovInfo) { ovInfo.classList.add('hidden'); ovInfo.classList.remove('flex'); }
                        ovHold.classList.remove('hidden');
                        ovHold.classList.add('flex');
                        document.documentElement.classList.add('overflow-hidden');
                      }
                    }
                    function closeStuOverlays() {
                      [ovInfo, ovHold].forEach(function (el) {
                        if (!el) return;
                        el.classList.add('hidden');
                        el.classList.remove('flex');
                      });
                      document.documentElement.classList.remove('overflow-hidden');
                    }
                    if (btnInfo) btnInfo.addEventListener('click', function () { openStuOverlay('info'); });
                    if (btnHold) btnHold.addEventListener('click', function () { openStuOverlay('hold'); });
                    document.querySelectorAll('[data-close-stu-overlay]').forEach(function (el) {
                      el.addEventListener('click', function (e) {
                        e.preventDefault();
                        closeStuOverlays();
                      });
                    });
                    document.addEventListener('keydown', function (e) {
                      if (e.key !== 'Escape') return;
                      var open = (ovInfo && ovInfo.classList.contains('flex')) || (ovHold && ovHold.classList.contains('flex'));
                      if (open) closeStuOverlays();
                    });
                    if (actWrap) {
                      var init = actWrap.getAttribute('data-initial-panel') || '';
                      if (init === 'info' || init === 'hold') openStuOverlay(init);
                    }

                    var ht = document.getElementById('people-hold-type');
                    var hw = document.getElementById('people-hold-custom-wrap');
                    var hci = document.getElementById('people-hold-custom');
                    if (ht && hw) {
                      function syncHoldCustom() {
                        var custom = ht.value === '__custom__';
                        hw.classList.toggle('hidden', !custom);
                        if (hci) hci.required = custom;
                      }
                      ht.addEventListener('change', syncHoldCustom);
                      syncHoldCustom();
                    }
                    var np = document.getElementById('people-hold-note-preset');
                    var nt = document.getElementById('people-hold-note');
                    if (np && nt) {
                      np.addEventListener('change', function () {
                        if (np.value) nt.value = np.value;
                      });
                    }
                  })();
                  </script>
                <?php elseif ($isFac): ?>
                  <div class="mt-1 flex flex-wrap items-center gap-3" id="people-faculty-actions" data-initial-panel="<?= htmlspecialchars($peoplePanel, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" id="people-btn-fac-open-info" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-950 shadow-sm hover:bg-violet-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500">Update info</button>
                    <button type="button" id="people-btn-fac-open-hold" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-950 shadow-sm hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Holds</button>
                    <span class="text-xs text-slate-500">Opens a full-screen panel over this page.</span>
                  </div>

                  <div id="people-overlay-fac-info" class="people-fac-overlay fixed inset-0 z-[70] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="people-overlay-fac-info-title">
                    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" data-close-fac-overlay="info"></div>
                    <div class="relative z-10 flex max-h-[min(90vh,720px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                      <div class="flex shrink-0 items-center justify-between gap-3 border-b border-violet-100 bg-violet-50/90 px-4 py-3">
                        <h2 id="people-overlay-fac-info-title" class="text-lg font-semibold text-slate-900">Update info</h2>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-violet-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500" data-close-fac-overlay="info">Close</button>
                      </div>
                      <div class="min-h-0 overflow-y-auto p-4 sm:p-5">
                        <p class="text-sm text-slate-600">Edit directory contact and office. Leave a field blank to keep its current value.</p>
                        <?php if ($canManageHolds): ?>
                        <form class="mt-4 grid gap-4" method="post" action="<?= htmlspecialchars(url('/admin.php?view=people&id=' . (int)$peopleId . '&people_panel=info')) ?>">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>" />
                          <input type="hidden" name="action" value="people_update_faculty" />
                          <input type="hidden" name="faculty_id" value="<?= (int)$peopleId ?>" />
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="fac-up-email">Email</label>
                            <input id="fac-up-email" name="email" type="email" autocomplete="off" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="fac-up-phone">Phone</label>
                            <input id="fac-up-phone" name="phone" type="text" inputmode="tel" autocomplete="off" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="fac-up-office">Office</label>
                            <input id="fac-up-office" name="office_number" type="text" placeholder="Leave blank to keep" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" />
                          </div>
                          <div>
                            <button type="submit" class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Save faculty profile</button>
                          </div>
                        </form>
                        <?php else: ?>
                        <p class="mt-3 text-sm text-slate-600">Your role cannot edit these fields.</p>
                        <?php endif; ?>
                        <p class="mt-6 text-xs text-slate-500">
                          <a class="font-semibold text-violet-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=schedule')) ?>">Open Master schedule</a>
                          <span class="text-slate-400"> · </span>
                          Sections for this instructor appear under “Sections teaching” below.
                        </p>
                      </div>
                    </div>
                  </div>

                  <div id="people-overlay-fac-hold" class="people-fac-overlay fixed inset-0 z-[70] hidden items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="people-overlay-fac-hold-title">
                    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" data-close-fac-overlay="hold"></div>
                    <div class="relative z-10 flex max-h-[min(90vh,520px)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                      <div class="flex shrink-0 items-center justify-between gap-3 border-b border-amber-100 bg-amber-50/90 px-4 py-3">
                        <h2 id="people-overlay-fac-hold-title" class="text-lg font-semibold text-slate-900">Holds</h2>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-amber-100/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500" data-close-fac-overlay="hold">Close</button>
                      </div>
                      <div class="min-h-0 overflow-y-auto p-4 sm:p-5 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Registration holds apply to students</p>
                        <p class="mt-2 leading-relaxed">There are no faculty-specific holds in this system. To add or clear a registration hold, open a <span class="rounded-md bg-sky-100 px-1.5 py-0.5 font-mono font-semibold text-sky-950">student</span> record and use Holds there.</p>
                        <p class="mt-4">
                          <a class="font-semibold text-indigo-700 hover:underline" href="<?= htmlspecialchars(url('/admin.php?view=people')) ?>">ID lookup — search by student ID</a>
                        </p>
                      </div>
                    </div>
                  </div>

                  <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">Faculty snapshot</p>
                  <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500"><?= $currentTermCode !== null ? 'This term · ' . htmlspecialchars($currentTermCode) : 'This term' ?></div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int)$facSectionsThisTerm ?></div>
                      <div class="mt-1 text-xs text-slate-500">Sections on file</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Teaching load (list)</div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= (int)$facTotalSections ?></div>
                      <div class="mt-1 text-xs text-slate-500">Most recent terms first</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Departments</div>
                      <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900"><?= count($facDepts) ?></div>
                      <div class="mt-1 text-xs text-slate-500">Affiliations</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-4">
                      <div class="text-xs font-semibold uppercase text-slate-500">Contact on file</div>
                      <div class="mt-1 text-sm font-semibold leading-snug text-slate-900"><?php $___fe = (($facultyMeta ?? [])['email'] ?? null) ?: ($ur['email'] ?? null); echo (($___fe !== null && (string)$___fe !== '') ? htmlspecialchars(trim((string)$___fe)) : '—'); ?></div>
                      <div class="mt-1 text-xs text-slate-500">Faculty record, else user</div>
                    </div>
                  </div>

                  <div class="mt-8 grid gap-6 lg:grid-cols-12">
                    <div class="space-y-4 lg:col-span-5">
                      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Profile &amp; office</h3>
                        <dl class="mt-3 space-y-2 text-sm text-slate-700">
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Rank / type</dt><dd class="text-right font-medium"><?php
                            $__fr = trim((string)(($facultyMeta ?? [])['rank'] ?? ''));
                            $__ft = trim((string)(($facultyMeta ?? [])['faculty_type'] ?? ''));
                            $__fcb = $__fr !== '' && $__ft !== '' ? $__fr . ' · ' . $__ft : ($__fr !== '' ? $__fr : $__ft);
                            echo $__fcb !== '' ? htmlspecialchars($__fcb) : '—';
                          ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Office</dt><dd class="font-medium"><?= $facultyMeta && ($facultyMeta['office_number'] ?? '') !== '' ? htmlspecialchars((string)$facultyMeta['office_number']) : '—' ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Hire date</dt><dd class="font-medium"><?= $facultyMeta && ($facultyMeta['hire_date'] ?? null) ? htmlspecialchars((string)$facultyMeta['hire_date']) : '—' ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="break-all font-medium"><?php $___fe = (($facultyMeta ?? [])['email'] ?? null) ?: ($ur['email'] ?? null); echo ($___fe !== null && (string)$___fe !== '') ? htmlspecialchars(trim((string)$___fe)) : '—'; ?></dd></div>
                          <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium"><?php $___fp = (($facultyMeta ?? [])['phone_number'] ?? null) ?: ($ur['phone_number'] ?? null); echo ($___fp !== null && (string)$___fp !== '') ? htmlspecialchars(trim((string)$___fp)) : '—'; ?></dd></div>
                        </dl>
                      </div>
                      <?php if ($facDepts): ?>
                      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-900">Departments</h3>
                        <ul class="mt-3 space-y-2 text-sm">
                          <?php foreach ($facDepts as $fd): ?>
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                              <span class="font-medium text-slate-900"><?= htmlspecialchars((string)($fd['dept_name'] ?? '')) ?></span>
                              <span class="text-slate-500"> (<?= htmlspecialchars((string)($fd['dept_id'] ?? '')) ?>)</span>
                              <?php if (($fd['percent_time'] ?? null) !== null && (string)$fd['percent_time'] !== ''): ?>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars((string)$fd['percent_time']) ?>% time</div>
                              <?php endif; ?>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="lg:col-span-7">
                      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-4 py-3">
                          <h3 class="text-sm font-semibold text-slate-900">Sections teaching</h3>
                          <p class="mt-0.5 text-xs text-slate-500">Switch term tabs (e.g. FA27) to see each roster slice.</p>
                        </div>
                        <?php if (!$facSectionTermOrder): ?>
                          <div class="px-4 py-8 text-center text-sm text-slate-500">No sections assigned.</div>
                        <?php else: ?>
                          <?php if (count($facSectionTermOrder) > 1): ?>
                            <div class="flex flex-wrap gap-1 border-b border-slate-100 bg-slate-50/60 px-2 pt-2" role="tablist" aria-label="Sections by term">
                              <?php foreach ($facSectionTermOrder as $tc): ?>
                                <button type="button" role="tab" class="people-facsec-tab -mb-px rounded-t-lg border border-slate-200 border-b-0 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-white/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" data-term="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tc) ?></button>
                              <?php endforeach; ?>
                            </div>
                          <?php endif; ?>
                          <?php foreach ($facSectionTermOrder as $ti => $tc): ?>
                            <?php $facTermRows = $facSectionsByTerm[$tc] ?? []; ?>
                            <div class="people-facsec-panel overflow-x-auto" data-term="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>" <?= $ti > 0 ? 'hidden' : '' ?>>
                              <table class="min-w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                                  <tr>
                                    <th class="px-3 py-2">Course</th>
                                    <th class="px-3 py-2">Section</th>
                                    <th class="px-3 py-2">Schedule</th>
                                  </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                  <?php foreach ($facTermRows as $r): ?>
                                    <tr class="hover:bg-slate-50/60">
                                      <td class="px-3 py-2 align-top">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars((string)($r['course_id'] ?? '')) ?></div>
                                        <div class="text-xs text-slate-600"><?= htmlspecialchars((string)($r['course_name'] ?? '')) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars((string)($r['term_name'] ?? '')) ?></div>
                                      </td>
                                      <td class="px-3 py-2 align-top font-mono text-slate-800">#<?= (int)($r['section_id'] ?? 0) ?></td>
                                      <td class="px-3 py-2 align-top text-xs text-slate-600">
                                        <?php if (!empty($r['meeting_days']) || !empty($r['meeting_time']) || !empty($r['room'])): ?>
                                          <?= htmlspecialchars(trim(((string)($r['meeting_days'] ?? '') . ' ' . (string)($r['meeting_time'] ?? '')))) ?>
                                          <?php if (!empty($r['room'])): ?><span class="text-slate-500"> · <?= htmlspecialchars((string)$r['room']) ?></span><?php endif; ?>
                                        <?php else: ?>
                                          <span class="text-slate-400">—</span>
                                        <?php endif; ?>
                                      </td>
                                    </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <script>
                  (function () {
                    function wireTermTabs(tabSel, panelSel) {
                      var tabs = document.querySelectorAll(tabSel);
                      var panels = document.querySelectorAll(panelSel);
                      if (!tabs.length || !panels.length) return;
                      function show(term) {
                        panels.forEach(function (p) {
                          p.hidden = p.getAttribute('data-term') !== term;
                        });
                        tabs.forEach(function (b) {
                          var on = b.getAttribute('data-term') === term;
                          b.setAttribute('aria-selected', on ? 'true' : 'false');
                          b.classList.toggle('border-indigo-600', on);
                          b.classList.toggle('bg-white', on);
                          b.classList.toggle('text-indigo-900', on);
                          b.classList.toggle('border-transparent', !on);
                          b.classList.toggle('text-slate-600', !on);
                          b.classList.toggle('bg-transparent', !on);
                        });
                      }
                      tabs.forEach(function (b) {
                        b.addEventListener('click', function () { show(b.getAttribute('data-term')); });
                      });
                      show(tabs[0].getAttribute('data-term'));
                    }
                    wireTermTabs('.people-facsec-tab', '.people-facsec-panel');

                    var ovFacInfo = document.getElementById('people-overlay-fac-info');
                    var ovFacHold = document.getElementById('people-overlay-fac-hold');
                    var btnFacInfo = document.getElementById('people-btn-fac-open-info');
                    var btnFacHold = document.getElementById('people-btn-fac-open-hold');
                    var actFacWrap = document.getElementById('people-faculty-actions');
                    function openFacOverlay(which) {
                      if (which === 'info' && ovFacInfo) {
                        if (ovFacHold) { ovFacHold.classList.add('hidden'); ovFacHold.classList.remove('flex'); }
                        ovFacInfo.classList.remove('hidden');
                        ovFacInfo.classList.add('flex');
                        document.documentElement.classList.add('overflow-hidden');
                      } else if (which === 'hold' && ovFacHold) {
                        if (ovFacInfo) { ovFacInfo.classList.add('hidden'); ovFacInfo.classList.remove('flex'); }
                        ovFacHold.classList.remove('hidden');
                        ovFacHold.classList.add('flex');
                        document.documentElement.classList.add('overflow-hidden');
                      }
                    }
                    function closeFacOverlays() {
                      [ovFacInfo, ovFacHold].forEach(function (el) {
                        if (!el) return;
                        el.classList.add('hidden');
                        el.classList.remove('flex');
                      });
                      document.documentElement.classList.remove('overflow-hidden');
                    }
                    if (btnFacInfo) btnFacInfo.addEventListener('click', function () { openFacOverlay('info'); });
                    if (btnFacHold) btnFacHold.addEventListener('click', function () { openFacOverlay('hold'); });
                    document.querySelectorAll('[data-close-fac-overlay]').forEach(function (el) {
                      el.addEventListener('click', function (e) {
                        e.preventDefault();
                        closeFacOverlays();
                      });
                    });
                    document.addEventListener('keydown', function (e) {
                      if (e.key !== 'Escape') return;
                      var facOpen = (ovFacInfo && ovFacInfo.classList.contains('flex')) || (ovFacHold && ovFacHold.classList.contains('flex'));
                      if (facOpen) closeFacOverlays();
                    });
                    if (actFacWrap) {
                      var initFac = actFacWrap.getAttribute('data-initial-panel') || '';
                      if (initFac === 'info' || initFac === 'hold') openFacOverlay(initFac);
                    }
                  })();
                  </script>
                <?php else: ?>
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Directory profile</p>
                  <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Personal &amp; contact</h3>
                    <p class="mt-2 text-sm text-slate-600">This ID is not linked to a student or faculty row—only the user record below applies (e.g. staff account).</p>
                    <dl class="mt-4 space-y-2 text-sm text-slate-700">
                      <div class="flex justify-between gap-3"><dt class="text-slate-500">DOB</dt><dd class="font-medium"><?= $__fmtDate($ur['dob'] ?? null) ?></dd></div>
                      <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="break-all font-medium"><?= $__empty(($ur['email'] ?? null)) ? '—' : htmlspecialchars((string)$ur['email']) ?></dd></div>
                      <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium"><?= $__empty(($ur['phone_number'] ?? null)) ? '—' : htmlspecialchars((string)$ur['phone_number']) ?></dd></div>
                    </dl>
                  </div>
                <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <?php endif; ?>

          </div>

        <?php elseif ($view === 'schedule'): ?>
          <?php
          require_once __DIR__ . '/../app/lib/admin_schedule.php';
          $scheduleState = admin_schedule_state($pdo, $_GET);
          $schedule_form_action = url('/admin.php');
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
              <thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Student ID</th><th class="px-4 py-3">Name</th></tr></thead>
              <tbody class="divide-y divide-slate-200">
                <?php foreach ($dir as $r): ?>
                  <?php $sid = (int)$r['user_id']; ?>
                  <tr>
                    <td class="px-4 py-3">
                      <a class="inline-flex rounded-md bg-sky-100 px-2 py-0.5 font-mono text-sm font-semibold tabular-nums text-sky-950 ring-1 ring-inset ring-sky-200/90 hover:bg-sky-200/90" href="<?= htmlspecialchars(url('/admin.php?view=people&id=' . $sid)) ?>"><?= $sid ?></a>
                    </td>
                    <td class="px-4 py-3"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></td>
                  </tr>
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
