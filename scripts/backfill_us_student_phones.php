<?php

declare(strict_types=1);

/**
 * Fill missing users.phone_number for students with a unique US (NANP-style) number.
 *
 * Usage: php scripts/backfill_us_student_phones.php
 */

require __DIR__ . '/../app/lib/view.php';
require __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/us_student_phone.php';

$pdo = db();
$up = $pdo->prepare('UPDATE users SET phone_number = ? WHERE user_id = ?');
$rows = $pdo->query('
  SELECT u.user_id
  FROM users u
  INNER JOIN students s ON s.student_id = u.user_id
  WHERE u.phone_number IS NULL OR TRIM(u.phone_number) = ""
  ORDER BY u.user_id
')->fetchAll(PDO::FETCH_ASSOC);

$n = 0;
foreach ($rows as $row) {
    $uid = (int)$row['user_id'];
    $phone = northbridge_allocate_us_student_phone($pdo, $uid);
    $up->execute([$phone, $uid]);
    $n++;
}
fwrite(STDOUT, "Set phone_number for {$n} students.\nDone.\n");
