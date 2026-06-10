<?php
require_once __DIR__ . '/app.php';

function startAppSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secureEnv = strtolower((string)getenv('SESSION_SECURE'));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || $secureEnv === 'true'
        || $secureEnv === '1';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => app_base_path() ?: '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
