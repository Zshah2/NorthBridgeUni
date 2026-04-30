<?php

declare(strict_types=1);

/**
 * Assign a unique US-style NANP display number (area code + exchange + line) for students.
 * Uses real-looking area codes; avoids collisions with existing users.phone_number.
 */
function northbridge_allocate_us_student_phone(PDO $pdo, int $userId): string
{
    $areaCodes = ['201', '202', '212', '213', '214', '305', '310', '312', '404', '415', '503', '617', '702', '713', '718', '801', '817', '469', '972', '512', '206', '253', '425', '917', '646', '347', '929'];
    $stmt = $pdo->prepare('
      SELECT 1 FROM users
      WHERE user_id <> ? AND phone_number IS NOT NULL AND TRIM(phone_number) <> "" AND TRIM(phone_number) = ?
      LIMIT 1
    ');
    for ($i = 0; $i < 20000; $i++) {
        $ac = $areaCodes[($userId + $i) % count($areaCodes)];
        $prefix = 201 + (($userId * 17 + $i * 11) % 799);
        $line = ($userId * 13 + $i * 7) % 10000;
        $formatted = sprintf('(%s) %03d-%04d', $ac, $prefix, $line);
        $stmt->execute([$userId, $formatted]);
        if (!$stmt->fetch()) {
            return $formatted;
        }
    }

    return sprintf('(%s) %03d-%04d', $areaCodes[0], ($userId % 799) + 200, $userId % 10000);
}
