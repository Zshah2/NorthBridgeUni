<?php

declare(strict_types=1);

/**
 * Fix duplicate registrations: same student may not hold two enrolled/waitlisted rows for the
 * same catalog course_id in the same term (different sections). Normal registration blocks this,
 * but imports or legacy data can create duplicates — this reconciles them.
 *
 * Keeps one row per (student_id, term_id, course_id):
 * - Prefers status `enrolled` over `waitlisted`
 * - Then lowest enrollment_id (earliest registration)
 *
 * Others are set to `dropped`.
 *
 *   php scripts/fix_duplicate_course_enrollments.php          # apply
 *   php scripts/fix_duplicate_course_enrollments.php --dry-run  # preview only
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

$dryRun = in_array('--dry-run', $argv, true);

$pdo = db();

$groups = $pdo->query('
  SELECT e.student_id, s.term_id, s.course_id, COUNT(*) AS cnt
  FROM enrollments e
  INNER JOIN sections s ON s.section_id = e.section_id
  WHERE e.status IN (\'enrolled\', \'waitlisted\')
  GROUP BY e.student_id, s.term_id, s.course_id
  HAVING cnt > 1
')->fetchAll(PDO::FETCH_ASSOC) ?: [];

if ($groups === []) {
    fwrite(STDOUT, "No duplicate course enrollments (active) found.\n");
    exit(0);
}

$listStmt = $pdo->prepare('
  SELECT e.enrollment_id, e.status, e.section_id
  FROM enrollments e
  INNER JOIN sections s ON s.section_id = e.section_id
  WHERE e.student_id = ? AND s.term_id = ? AND s.course_id = ?
    AND e.status IN (\'enrolled\', \'waitlisted\')
  ORDER BY CASE e.status WHEN \'enrolled\' THEN 0 WHEN \'waitlisted\' THEN 1 ELSE 2 END,
           e.enrollment_id ASC
');

$dropStmt = $pdo->prepare('UPDATE enrollments SET status = \'dropped\' WHERE enrollment_id = ?');

$fixed = 0;
$pdo->beginTransaction();
try {
    foreach ($groups as $g) {
        $sid = (int)$g['student_id'];
        $tid = (int)$g['term_id'];
        $cid = (string)$g['course_id'];
        $listStmt->execute([$sid, $tid, $cid]);
        $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (count($rows) < 2) {
            continue;
        }
        $keep = array_shift($rows);
        $keepId = (int)($keep['enrollment_id'] ?? 0);
        foreach ($rows as $r) {
            $eid = (int)($r['enrollment_id'] ?? 0);
            if ($eid < 1) {
                continue;
            }
            fwrite(
                STDOUT,
                ($dryRun ? '[dry-run] would drop' : 'dropping')
                . " enrollment_id={$eid} (student={$sid} term_id={$tid} course={$cid}); keeping enrollment_id={$keepId}\n"
            );
            if (!$dryRun) {
                $dropStmt->execute([$eid]);
                $fixed++;
            } else {
                $fixed++;
            }
        }
    }
    if ($dryRun) {
        $pdo->rollBack();
        fwrite(STDOUT, "Dry run: {$fixed} enrollment row(s) would be set to dropped.\n");
    } else {
        $pdo->commit();
        fwrite(STDOUT, "Updated {$fixed} duplicate enrollment row(s) to dropped.\n");
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'fix_duplicate_course_enrollments failed: ' . $e->getMessage() . "\n");
    exit(1);
}
