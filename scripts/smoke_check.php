<?php

declare(strict_types=1);

/**
 * Quick HTTP smoke test (run server first: php -S 127.0.0.1:8000 -t public).
 *
 *   php scripts/smoke_check.php http://127.0.0.1:8000
 */

/**
 * @return array{0:int,1:string} HTTP status code and body
 */
function http_get(string $url): array
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);

    $fp = @fopen($url, 'r', false, $ctx);
    if ($fp === false) {
        return [0, ''];
    }
    $meta = stream_get_meta_data($fp);
    $body = stream_get_contents($fp) ?: '';
    fclose($fp);

    $code = 0;
    if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
        foreach ($meta['wrapper_data'] as $line) {
            if (is_string($line) && preg_match('#HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $code = (int)$m[1];
            }
        }
    }

    return [$code, $body];
}

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$paths = ['/', '/health', '/login'];

$failed = false;
foreach ($paths as $path) {
    $url = $base . $path;
    [$code, $body] = http_get($url);

    $ok = $code >= 200 && $code < 400;
    if ($path === '/health') {
        $ok = $ok && $code === 200 && str_contains($body, '"ok"');
    }

    if ($ok) {
        fwrite(STDOUT, "OK  $path ($code)\n");
    } else {
        fwrite(STDERR, "FAIL $path ($code)\n");
        $failed = true;
    }
}

exit($failed ? 1 : 0);
