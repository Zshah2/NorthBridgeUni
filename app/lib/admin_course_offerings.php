<?php

declare(strict_types=1);

/**
 * Course offerings list (sections joined to courses + instructors) for admin browse.
 *
 * @param array<string, scalar|array|null> $get
 *
 * @return array{
 *   terms: list<array<string, mixed>>,
 *   term_id: int|null,
 *   dept_rows: list<array<string, mixed>>,
 *   dept_id: string,
 *   q: string,
 *   course_sections: list<array<string, mixed>>,
 *   course_sections_total: int,
 *   page: int,
 *   per_page: int,
 *   total_pages: int
 * }
 */
function admin_course_offerings_state(PDO $pdo, array $get): array
{
    $terms = [];
    try {
        $terms = $pdo->query('
          SELECT term_id, code, name, start_date, end_date
          FROM terms
          ORDER BY COALESCE(start_date, "1970-01-01") DESC, term_id DESC
        ')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        $terms = [];
    }

    $deptRows = [];
    try {
        $deptRows = $pdo->query('SELECT dept_id, dept_name FROM departments ORDER BY dept_id')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable) {
        $deptRows = [];
    }

    $validDeptIds = array_map(static fn ($r) => (string)($r['dept_id'] ?? ''), $deptRows);

    $termId = null;
    if ($terms !== []) {
        /** @var list<int> $validTermIds */
        $validTermIds = array_map(static fn ($t) => (int)$t['term_id'], $terms);
        $tidRaw = $get['term_id'] ?? null;
        if (isset($tidRaw) && ctype_digit((string)$tidRaw) && in_array((int)$tidRaw, $validTermIds, true)) {
            $termId = (int)$tidRaw;
        } else {
            $termId = (int)$terms[0]['term_id'];
        }
    }

    $deptFilter = trim((string)($get['dept_id'] ?? ''));
    if ($deptFilter !== '' && !in_array($deptFilter, $validDeptIds, true)) {
        $deptFilter = '';
    }

    $q = trim((string)($get['q'] ?? ''));

    $perPage = (int)($get['per_page'] ?? 50);
    if (!in_array($perPage, [25, 50, 100, 200], true)) {
        $perPage = 50;
    }
    $page = max(1, (int)($get['page'] ?? 1));

    $rows = [];
    $total = 0;

    if ($termId !== null) {
        $where = ['s.term_id = ?'];
        $bind = [$termId];
        if ($deptFilter !== '') {
            $where[] = 'c.dept_id = ?';
            $bind[] = $deptFilter;
        }
        if ($q !== '') {
            $where[] = '(
              CAST(s.section_id AS CHAR) LIKE ?
              OR c.course_id LIKE ?
              OR LOWER(c.course_name) LIKE ?
              OR LOWER(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) LIKE ?
              OR LOWER(COALESCE(s.room, "")) LIKE ?
              OR LOWER(COALESCE(s.meeting_days, "")) LIKE ?
              OR LOWER(COALESCE(s.meeting_time, "")) LIKE ?
            )';
            $slike = '%' . strtolower($q) . '%';
            $idLike = '%' . $q . '%';
            array_push($bind, $idLike, $idLike, $slike, $slike, $slike, $slike, $slike);
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "
          SELECT COUNT(*)
          FROM sections s
          JOIN courses c ON c.course_id = s.course_id
          LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
          LEFT JOIN users u ON u.user_id = f.faculty_id
          WHERE {$whereSql}
        ";
        try {
            $cst = $pdo->prepare($countSql);
            $cst->execute($bind);
            $total = (int)$cst->fetchColumn();
        } catch (Throwable) {
            $total = 0;
        }

        $offset = ($page - 1) * $perPage;
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = "
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
            s.capacity,
            (SELECT COUNT(*) FROM enrollments e
             WHERE e.section_id = s.section_id AND e.status = 'enrolled') AS enrolled_count
          FROM sections s
          JOIN courses c ON c.course_id = s.course_id
          JOIN terms t ON t.term_id = s.term_id
          LEFT JOIN faculty f ON f.faculty_id = s.faculty_id
          LEFT JOIN users u ON u.user_id = f.faculty_id
          WHERE {$whereSql}
          ORDER BY c.course_id, s.section_id
          LIMIT ? OFFSET ?
        ";
        try {
            $st = $pdo->prepare($sql);
            $st->execute(array_merge($bind, [$perPage, $offset]));
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            $rows = [];
        }
    } else {
        $totalPages = 1;
    }

    return [
        'terms' => $terms,
        'term_id' => $termId,
        'dept_rows' => $deptRows,
        'dept_id' => $deptFilter,
        'q' => $q,
        'course_sections' => $rows,
        'course_sections_total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => max(1, $totalPages),
    ];
}
