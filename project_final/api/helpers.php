<?php
require_once __DIR__ . '/../config/session.php';

/**
 * Shared response helper for all API endpoints
 */
header('Content-Type: application/json');
$allowedOrigin = getenv('CORS_ALLOWED_ORIGIN');
if ($allowedOrigin !== false && $allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function sendSuccess($data, string $message = 'OK', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit;
}

function sendError(string $message = 'Bad Request', int $code = 400): void {
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message, 'data' => null]);
    exit;
}

function getInput(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function requireApiRole(string $role): void {
    if (session_status() === PHP_SESSION_NONE) {
        startAppSession();
    }

    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        sendError('Unauthorized', 401);
    }

    if ($_SESSION['role'] !== $role) {
        sendError('Forbidden', 403);
    }
}

function requireAdminForMutation(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        requireApiRole('admin');
    }
}
