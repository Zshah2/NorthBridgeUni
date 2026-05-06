<?php

declare(strict_types=1);

/**
 * Remove duplicate faculty people (same first+last name) and cap faculty count to 250.
 *
 * - Duplicates are detected by normalized (first_name, last_name) from users joined to faculty.
 * - Keeps the lowest faculty_id for each duplicate group.
 * - For removed faculty IDs:
 *   - sections.faculty_id is set to NULL
 *   - departments.chair_id is set to NULL
 *   - faculty_departments rows are deleted
 *   - faculty row is deleted (cascades safe)
 *   - users row is deleted
 *
 * Usage:
 *   php scripts/trim_faculty_to_250.php
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function norm_name(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    $s = preg_replace('/[^a-z0-9 ]/i', '', $s) ?? $s;

    return trim($s);
}

function remove_faculty_ids(PDO $pdo, array $ids): void
{
    if ($ids === []) {
        return;
    }
    $place = implode(',', array_fill(0, count($ids), '?'));

    // Detach references
    $pdo->prepare('UPDATE sections SET faculty_id = NULL WHERE faculty_id IN (' . $place . ')')->execute($ids);
    $pdo->prepare('UPDATE departments SET chair_id = NULL WHERE chair_id IN (' . $place . ')')->execute($ids);

    // Remove adjunct tables first
    $pdo->prepare('DELETE FROM faculty_departments WHERE faculty_id IN (' . $place . ')')->execute($ids);

    // Remove faculty and corresponding user
    $pdo->prepare('DELETE FROM faculty WHERE faculty_id IN (' . $place . ')')->execute($ids);
    $pdo->prepare('DELETE FROM users WHERE user_id IN (' . $place . ')')->execute($ids);
}

$rows = $pdo->query('
  SELECT f.faculty_id, u.first_name, u.last_name
  FROM faculty f
  INNER JOIN users u ON u.user_id = f.faculty_id
  ORDER BY f.faculty_id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$byName = [];
$toRemoveDup = [];
foreach ($rows as $r) {
    $fid = (int)($r['faculty_id'] ?? 0);
    $first = norm_name((string)($r['first_name'] ?? ''));
    $last = norm_name((string)($r['last_name'] ?? ''));
    $key = $first . '|' . $last;

    // If we can't form a name key, don't dedupe it automatically.
    if ($first === '' || $last === '') {
        continue;
    }

    if (!isset($byName[$key])) {
        $byName[$key] = $fid;
        continue;
    }

    // Keep the earliest id; remove the rest.
    $keep = (int)$byName[$key];
    if ($fid === $keep) {
        continue;
    }
    $toRemoveDup[] = $fid;
}

$toRemoveDup = array_values(array_unique(array_filter($toRemoveDup, static fn ($v) => (int)$v > 0)));

$pdo->beginTransaction();
try {
    remove_faculty_ids($pdo, $toRemoveDup);

    $remaining = $pdo->query('
      SELECT f.faculty_id, u.last_name, u.first_name
      FROM faculty f
      INNER JOIN users u ON u.user_id = f.faculty_id
      ORDER BY u.last_name, u.first_name, f.faculty_id
    ')->fetchAll(PDO::FETCH_ASSOC);

    $remainingIds = array_map(static fn ($r) => (int)($r['faculty_id'] ?? 0), $remaining);
    $remainingIds = array_values(array_filter($remainingIds, static fn ($v) => (int)$v > 0));

    $toRemoveCap = [];
    if (count($remainingIds) > 250) {
        $toRemoveCap = array_slice($remainingIds, 250);
        remove_faculty_ids($pdo, $toRemoveCap);
    }

    $pdo->commit();

    $after = (int)$pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
    fwrite(STDOUT, "Removed duplicates: " . count($toRemoveDup) . "\n");
    fwrite(STDOUT, "Removed for cap>250: " . count($toRemoveCap) . "\n");
    fwrite(STDOUT, "Faculty remaining: {$after}\n");
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}

