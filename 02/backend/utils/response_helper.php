<?php
/**
 * response_helper.php
 * Tanggung Jawab: Standarisasi format response JSON.
 */

header('Content-Type: application/json; charset=utf-8');
$allowedOrigin = getenv('CORS_ALLOWED_ORIGIN');
if ($allowedOrigin !== false && $allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function sendSuccess($data = null, $message = 'Success', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['status' => 'success', 'message' => $message, 'data' => $data]);
    exit();
}

function sendError($message = 'Error', $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}
?>
