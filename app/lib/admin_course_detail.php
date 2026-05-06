<?php

declare(strict_types=1);

/**
 * Single-course admin record: catalog fields, offerings by term, enrollment roster.
 *
 * @param array<string, scalar|array|null> $get
 *
 * @return array{
 *   course_id_param: string,
 *   course: array<string, mixed>|null,
 *   prereqs: list<array<string, mixed>>,
 *   terms_with_offerings: list<array<string, mixed>>,
 *   term_id: int|null,
 *   sections: list<array<string, mixed>>,
 *   roster: list<array<string, mixed>>,
 *   highlight_section: int|null
 * }
 */
function admin_course_detail_state(PDO $pdo, array $get): array
{
    $rawCourseId = trim((string)($get['course_id'] ?? ''));
    if ($rawCourseId === '' && isset($get['id'])) {
        $rawCourseId = trim((string)$get['id']);
    }
    $courseIdParam = strtoupper($rawCourseId);
    $highlightRaw = $get['highlight_section'] ?? null;
    $highlightSection = isset($highlightRaw) && ctype_digit((string)$highlightRaw)
        ? (int)$highlightRaw
        : null;

    $course = null;
    if ($courseIdParam !== '') {
        try {
            $st = $pdo->prepare('
              SELECT c.course_id, c.course_name, c.credits, c.dept_id, c.description,
                IFNULL(c.is_active, 1) AS is_active,
                d.dept_name
              FROM courses c
              LEFT JOIN departments d ON d.dept_id = c.dept_id
              WHERE c.course_id = ?
              LIMIT 1
            ');
            $st->execute([$courseIdParam]);
            $course = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable) {
            try {
                $st = $pdo->prepare('
                  SELECT c.course_id, c.course_name, c.credits, c.dept_id, c.description, 1 AS is_active, d.dept_name
                  FROM courses c
                  LEFT JOIN departments d ON d.dept_id = c.dept_id
                  WHERE c.course_id = ?
                  LIMIT 1
                ');
                $st->execute([$courseIdParam]);
                $course = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable) {
                $course = null;
            }
        }
    }

    $prereqs = [];
    $termsWithOfferings = [];
    $termId = null;
    $sections = [];
    $roster = [];

    if ($course !== null) {
        $cid = (string)$course['course_id'];
        try {
            $pre = $pdo->prepare('
              SELECT
                p.prereq_course_id AS course_id,
                c2.course_name,
                c2.credits,
                c2.description AS prereq_description,
                d2.dept_name AS prereq_dept_name,
                c2.dept_id AS prereq_dept_id
              FROM course_prereqs p
              INNER JOIN courses c2 ON c2.course_id = p.prereq_course_id
              LEFT JOIN departments d2 ON d2.dept_id = c2.dept_id
              WHERE p.course_id = ?
              ORDER BY p.prereq_course_id
            ');
            $pre->execute([$cid]);
            $prereqs = $pre->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            try {
                $pre = $pdo->prepare('
                  SELECT p.prereq_course_id AS course_id, c2.course_name, c2.credits,
                    NULL AS prereq_description, d2.dept_name AS prereq_dept_name, c2.dept_id AS prereq_dept_id
                  FROM course_prereqs p
                  INNER JOIN courses c2 ON c2.course_id = p.prereq_course_id
                  LEFT JOIN departments d2 ON d2.dept_id = c2.dept_id
                  WHERE p.course_id = ?
                  ORDER BY p.prereq_course_id
                ');
                $pre->execute([$cid]);
                $prereqs = $pre->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable) {
                $prereqs = [];
            }
        }

        try {
            $termsWithOfferings = $pdo->prepare('
              SELECT DISTINCT t.term_id, t.code, t.name, t.start_date
              FROM sections s
              INNER JOIN terms t ON t.term_id = s.term_id
              WHERE s.course_id = ?
              ORDER BY COALESCE(t.start_date, "1970-01-01") DESC, t.term_id DESC
            ');
            $termsWithOfferings->execute([$cid]);
            $termsWithOfferings = $termsWithOfferings->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            $termsWithOfferings = [];
        }

        $validTermIds = array_map(static fn ($t) => (int)$t['term_id'], $termsWithOfferings);
        $tidRaw = $get['term_id'] ?? null;
        if ($termsWithOfferings !== []) {
            if (isset($tidRaw) && ctype_digit((string)$tidRaw) && in_array((int)$tidRaw, $validTermIds, true)) {
                $termId = (int)$tidRaw;
            } else {
                $termId = (int)$termsWithOfferings[0]['term_id'];
            }
        }

        if ($termId !== null) {
            $secSql = '
              SELECT
                s.section_id,
                s.meeting_days,
                s.meeting_time,
                s.room,
                s.capacity,
                t.code AS term_code,
                u.first_name AS fac_first,
                u.last_name AS fac_last,
                (SELECT COUNT(*) FROM enrollments e
                 WHERE e.section_id = s.section_id AND e.status = \'enrolled\') AS enrolled_count,
                (SELECT COUNT(*) FROM enrollments e
                 WHERE e.section_id = s.section_id AND e.status = \'waitlisted\') AS waitlisted_count
              FROM sections s
              JOIN terms t ON t.term_id = s.term_id
              LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
              LEFT JOIN users u ON u.user_id = f.faculty_id
              WHERE s.course_id = ? AND s.term_id = ?
              ORDER BY s.section_id
            ';
            try {
                $sse = $pdo->prepare($secSql);
                $sse->execute([$cid, $termId]);
                $sections = $sse->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable) {
                $sections = [];
            }

            $rosSql = '
              SELECT
                e.student_id,
                e.status,
                e.section_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number
              FROM enrollments e
              INNER JOIN sections s ON s.section_id = e.section_id
              INNER JOIN users u ON u.user_id = e.student_id
              WHERE s.course_id = ? AND s.term_id = ? AND e.status IN (\'enrolled\', \'waitlisted\')
              ORDER BY
                CASE e.status WHEN \'enrolled\' THEN 0 WHEN \'waitlisted\' THEN 1 ELSE 2 END,
                u.last_name,
                u.first_name,
                e.student_id
              LIMIT 500
            ';
            try {
                $rse = $pdo->prepare($rosSql);
                $rse->execute([$cid, $termId]);
                $roster = $rse->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable) {
                $roster = [];
            }
        }
    }

    return [
        'course_id_param' => $courseIdParam,
        'course' => $course,
        'prereqs' => $prereqs,
        'terms_with_offerings' => $termsWithOfferings,
        'term_id' => $termId,
        'sections' => $sections,
        'roster' => $roster,
        'highlight_section' => $highlightSection,
    ];
}
