<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

$pdo = Database::getConnection();
date_default_timezone_set('Asia/Jakarta');

$row = $pdo->query("
    SELECT
        COUNT(*) AS total_spbu,
        SUM(CASE WHEN buka_24_jam = 1 THEN 1 ELSE 0 END) AS spbu_buka
    FROM spbu
")->fetch();

$total = (int)($row['total_spbu'] ?? 0);
$open = (int)($row['spbu_buka'] ?? 0);

sendSuccess([
    'total_spbu' => $total,
    'spbu_buka' => $open,
    'spbu_tutup' => max(0, $total - $open),
    'basis_perhitungan' => 'buka_24_jam',
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
], 'Status SPBU');
