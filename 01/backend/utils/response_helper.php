<?php
/**
 * response_helper.php
 * Tanggung Jawab: Standarisasi format response JSON untuk semua API endpoint.
 * Mencegah inkonsistensi struktur data yang dikembalikan ke frontend.
 */

header('Content-Type: application/json; charset=utf-8');
// Izinkan CORS (opsional, jika frontend dan backend di port yang beda)
$allowedOrigin = getenv('CORS_ALLOWED_ORIGIN');
if ($allowedOrigin !== false && $allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Mengirim response sukses
 * @param mixed $data Data yang ingin dikirim
 * @param string $message Pesan sukses (opsional)
 * @param int $statusCode HTTP Status Code (default: 200)
 */
function sendSuccess($data = null, $message = 'Success', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Mengirim response error
 * @param string $message Pesan error
 * @param int $statusCode HTTP Status Code (default: 400)
 */
function sendError($message = 'Error', $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit();
}
?>
