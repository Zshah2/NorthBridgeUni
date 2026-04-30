<?php

declare(strict_types=1);

/**
 * School email pattern: first letter of first name + sanitized last name @northbridge.edu
 * (same for students and faculty). When that local part is taken (e.g. same initial + last name),
 * append 2, 3, 4, … until unique across users.email and faculty.email.
 */
function northbridge_email_domain(): string
{
    return 'northbridge.edu';
}

function northbridge_email_sanitize_last(string $lastName): string
{
    $lastName = trim($lastName);
    if ($lastName === '') {
        return 'user';
    }
    if (function_exists('iconv')) {
        $conv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $lastName);
        if ($conv !== false && $conv !== '') {
            $lastName = $conv;
        }
    }
    $s = strtolower((string)preg_replace('/[^a-z0-9]/i', '', $lastName));

    return $s !== '' ? $s : 'user';
}

function northbridge_email_local_base(string $firstName, string $lastName): string
{
    $first = trim($firstName);
    $letter = 'u';
    if ($first !== '') {
        if (function_exists('mb_substr') && function_exists('mb_strtolower')) {
            $ch = mb_substr($first, 0, 1, 'UTF-8');
            $letter = mb_strtolower($ch, 'UTF-8');
        } else {
            $letter = strtolower($first[0]);
        }
        $letter = strtolower((string)preg_replace('/[^a-z]/i', '', $letter));
        if ($letter === '') {
            $letter = 'u';
        }
    }

    return $letter . northbridge_email_sanitize_last($lastName);
}

function northbridge_email_full(string $localPart): string
{
    return strtolower(trim($localPart)) . '@' . northbridge_email_domain();
}

/** True if this address is already used on another user_id (users or faculty). */
function northbridge_school_email_in_use(PDO $pdo, string $email, int $forUserId): bool
{
    $e = strtolower(trim($email));
    if ($e === '') {
        return false;
    }
    $stmt = $pdo->prepare('
      SELECT 1 FROM users
      WHERE email IS NOT NULL AND TRIM(email) <> "" AND LOWER(TRIM(email)) = ? AND user_id <> ?
      LIMIT 1
    ');
    $stmt->execute([$e, $forUserId]);
    if ($stmt->fetch()) {
        return true;
    }
    $stmt = $pdo->prepare('
      SELECT 1 FROM faculty
      WHERE email IS NOT NULL AND TRIM(email) <> "" AND LOWER(TRIM(email)) = ? AND faculty_id <> ?
      LIMIT 1
    ');
    $stmt->execute([$e, $forUserId]);

    return (bool) $stmt->fetch();
}

/**
 * @return non-empty string school email
 */
function northbridge_allocate_school_email(PDO $pdo, string $firstName, string $lastName, int $userId): string
{
    $base = northbridge_email_local_base($firstName, $lastName);
    $full = northbridge_email_full($base);
    if (!northbridge_school_email_in_use($pdo, $full, $userId)) {
        return $full;
    }
    for ($n = 2; $n <= 9999; $n++) {
        $local = $base . (string) $n;
        $full = northbridge_email_full($local);
        if (!northbridge_school_email_in_use($pdo, $full, $userId)) {
            return $full;
        }
    }

    return northbridge_email_full($base . 'x' . (string) $userId);
}
