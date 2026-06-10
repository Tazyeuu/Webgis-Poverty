<?php
/**
 * auth_check.php — Middleware: pastikan user sudah login dan role sesuai.
 * Usage: require_once __DIR__ . '/../config/auth_check.php';
 *        requireRole('admin'); // atau 'user'
 */
require_once __DIR__ . '/session.php';

if (session_status() === PHP_SESSION_NONE) {
    startAppSession();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireLogin(?string $redirect = null): void {
    if (!isLoggedIn()) {
        $redirect = $redirect ?? app_url('login.php');
        header("Location: $redirect");
        exit;
    }
}

function requireRole(string $role, ?string $redirect = null): void {
    requireLogin($redirect);
    if ($_SESSION['role'] !== $role) {
        // Redirect ke dashboard yang sesuai role-nya
        if ($_SESSION['role'] === 'admin') {
            header('Location: ' . app_url('admin/index.php'));
        } else {
            header('Location: ' . app_url('user/index.php'));
        }
        exit;
    }
}

function currentUser(): array {
    return [
        'id'          => $_SESSION['user_id']    ?? null,
        'username'    => $_SESSION['username']   ?? '',
        'role'        => $_SESSION['role']        ?? '',
        'nama_lengkap'=> $_SESSION['nama_lengkap']?? '',
    ];
}
