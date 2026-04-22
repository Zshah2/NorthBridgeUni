<?php

declare(strict_types=1);

/**
 * Adds demo terms, sections, enrollments, and a sample hold so admin pages
 * have predictable data after migrate + import_all.
 *
 * Requires migration 002 (student_holds). Run:
 *   php scripts/migrate.php
 *   php scripts/import_all.php
 *   php scripts/seed_demo_registration.php
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

const DEMO_STUDENT_ID = 123123;
const DEMO_FACULTY_ID = 123152;

$pdo = db();

$st = $pdo->prepare('SELECT COUNT(*) FROM students WHERE student_id = ?');
$st->execute([DEMO_STUDENT_ID]);
if ((int)$st->fetchColumn() === 0) {
    fwrite(STDERR, 'Student ' . DEMO_STUDENT_ID . " not found. Run: php scripts/import_all.php\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $pdo->prepare('
      INSERT INTO terms (code, name, start_date, end_date)
      VALUES ("FA26", "Fall 2026", "2026-08-20", "2026-12-15")
      ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        start_date = VALUES(start_date),
        end_date = VALUES(end_date)
    ')->execute();

    $termId = (int)$pdo->query("SELECT term_id FROM terms WHERE code = 'FA26' LIMIT 1")->fetchColumn();
    if ($termId < 1) {
        throw new RuntimeException('Failed to resolve term FA26');
    }

    $upsertCourse = $pdo->prepare('
      INSERT INTO courses (course_id, course_name, credits, dept_id)
      VALUES (?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        course_name = VALUES(course_name),
        credits = VALUES(credits),
        dept_id = VALUES(dept_id)
    ');
    $upsertCourse->execute(['ENG101', 'English Composition I', 4, 'ENG']);
    $upsertCourse->execute(['HIS103', 'History of Ideas', 4, 'HIS']);

    $cntStmt = $pdo->prepare('
      SELECT COUNT(*) FROM sections s
      WHERE s.term_id = ? AND s.course_id IN ("ENG101","HIS103")
    ');
    $cntStmt->execute([$termId]);
    $sectionCount = (int)$cntStmt->fetchColumn();

    if ($sectionCount < 2) {
        $insSec = $pdo->prepare('
          INSERT INTO sections (course_id, term_id, faculty_id, meeting_days, meeting_time, room, capacity)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $insSec->execute(['ENG101', $termId, DEMO_FACULTY_ID, 'MWF', '09:00-09:50', 'ENG-201', 30]);
        $insSec->execute(['HIS103', $termId, DEMO_FACULTY_ID, 'TR', '13:00-14:15', 'LIB-1107', 35]);
    } else {
        fwrite(STDOUT, "Demo sections for FA26 already exist; syncing enrollments and hold.\n");
    }

    $secStmt = $pdo->prepare('
      SELECT section_id FROM sections WHERE term_id = ? AND course_id = ?
    ');
    $secStmt->execute([$termId, 'ENG101']);
    $engSection = (int)$secStmt->fetchColumn();
    $secStmt->execute([$termId, 'HIS103']);
    $hisSection = (int)$secStmt->fetchColumn();

    if ($engSection < 1 || $hisSection < 1) {
        throw new RuntimeException('Could not resolve demo section IDs');
    }

    $enroll = $pdo->prepare('
      INSERT IGNORE INTO enrollments (student_id, section_id, status)
      VALUES (?, ?, "enrolled")
    ');
    $enroll->execute([DEMO_STUDENT_ID, $engSection]);
    $enroll->execute([DEMO_STUDENT_ID, $hisSection]);

    $hc = $pdo->prepare('
      SELECT COUNT(*) FROM student_holds
      WHERE student_id = ? AND is_active = 1 AND hold_type = "Bursar"
    ');
    $hc->execute([DEMO_STUDENT_ID]);
    if ((int)$hc->fetchColumn() === 0) {
        $pdo->prepare('
          INSERT INTO student_holds (student_id, hold_type, note, is_active)
          VALUES (?, "Bursar", "Demo financial hold — use Admin → Holds to clear.", 1)
        ')->execute([DEMO_STUDENT_ID]);
    }

    $pdo->commit();
    fwrite(STDOUT, 'Demo registration seed OK (student ' . DEMO_STUDENT_ID . ", term FA26).\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
