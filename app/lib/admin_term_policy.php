<?php

declare(strict_types=1);

/**
 * Registration window policy for a term (migration 010 columns).
 * If columns are missing (older DB), enrollment is allowed.
 */
function admin_term_registration_allowed(PDO $pdo, int $termId): bool
{
    try {
        $st = $pdo->prepare('SELECT registration_open, registration_start, registration_end FROM terms WHERE term_id = ? LIMIT 1');
        $st->execute([$termId]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return false;
        }
        if (isset($r['registration_open']) && !(int)$r['registration_open']) {
            return false;
        }
        $today = date('Y-m-d');
        if (!empty($r['registration_start']) && $today < (string)$r['registration_start']) {
            return false;
        }
        if (!empty($r['registration_end']) && $today > (string)$r['registration_end']) {
            return false;
        }

        return true;
    } catch (Throwable) {
        return true;
    }
}
