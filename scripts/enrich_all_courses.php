<?php

declare(strict_types=1);

/**
 * Fills empty catalog descriptions for every course and adds inferable prerequisites.
 * Usually you do not need to run this — import_all.php runs the same logic automatically.
 *
 *   php scripts/enrich_all_courses.php
 *   php scripts/enrich_all_courses.php --force   # overwrite all descriptions with templates
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/enrich_catalog.php';

$force = in_array('--force', $argv, true) || in_array('-f', $argv, true);

try {
    $pdo = db();
    $stats = enrich_catalog_run($pdo, $force);
    fwrite(STDOUT, "enrich_all_courses: updated {$stats['descriptions']} description(s)" . ($force ? ' (force).' : ' (empty only).') . "\n");
    fwrite(STDOUT, "enrich_all_courses: added {$stats['prereqs']} new prerequisite link(s).\n");
    fwrite(STDOUT, "Done.\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'enrich_all_courses failed: ' . $e->getMessage() . "\n");
    exit(1);
}
