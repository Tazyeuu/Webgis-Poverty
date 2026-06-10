<?php
/**
 * Application path helpers.
 *
 * Set APP_BASE_PATH to the subdirectory that serves project_final.
 * In this repository's Coolify image the final app runs at /project_final.
 */
function app_base_path(): string {
    $basePath = getenv('APP_BASE_PATH');
    if ($basePath === false) {
        $basePath = '/project/project_final';
    }

    $basePath = trim($basePath);
    if ($basePath === '' || $basePath === '/') {
        return '';
    }

    return '/' . trim($basePath, '/');
}

function app_url(string $path = ''): string {
    $path = '/' . ltrim($path, '/');
    return app_base_path() . $path;
}
