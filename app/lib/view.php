<?php

function view_path(string $relative): string
{
    return __DIR__ . '/../views/' . ltrim($relative, '/');
}

function config(string $relative)
{
    $path = __DIR__ . '/../config/' . ltrim($relative, '/');
    if (!str_ends_with($path, '.php')) {
        $path .= '.php';
    }
    return require $path;
}

/**
 * @param array<string,mixed> $data
 */
function render(string $template, array $data = [], ?string $layout = 'layouts/main.php'): void
{
    extract($data, EXTR_SKIP);

    ob_start();
    require view_path($template);
    $content = ob_get_clean();

    if ($layout === null) {
        echo $content;
        return;
    }

    require view_path($layout);
}

