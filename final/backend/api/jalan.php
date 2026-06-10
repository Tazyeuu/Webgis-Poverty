<?php
/**
 * jalan.php
 * Tanggung Jawab: Menangani operasi CRUD untuk entitas Jalan (LineString).
 */

require_once '../config/db.php';
require_once '../utils/response_helper.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT id, nama, jenis_jalan, created_at, ST_AsGeoJSON(geom) as geojson FROM jalan");
            $features = [];
            while ($row = $stmt->fetch()) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => json_decode($row['geojson']),
                    'properties' => [
                        'id' => $row['id'],
                        'nama' => $row['nama'],
                        'jenis_jalan' => $row['jenis_jalan'],
                        'created_at' => $row['created_at']
                    ]
                ];
            }
            sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data Jalan berhasil diambil');
        } catch (PDOException $e) {
            sendError('Gagal mengambil data Jalan: ' . $e->getMessage(), 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nama']) || !isset($input['geometry'])) {
            sendError('Nama dan geometri wajib diisi');
        }

        try {
            $sql = "INSERT INTO jalan (nama, jenis_jalan, geom) VALUES (:nama, :jenis_jalan, ST_GeomFromGeoJSON(:geometry))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama' => $input['nama'],
                ':jenis_jalan' => $input['jenis_jalan'] ?? 'Lokal',
                ':geometry' => json_encode($input['geometry'])
            ]);
            sendSuccess(['id' => $pdo->lastInsertId()], 'Data Jalan berhasil disimpan', 201);
        } catch (PDOException $e) {
            sendError('Gagal menyimpan Jalan: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendError('ID Jalan wajib disertakan');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM jalan WHERE id = :id");
            $stmt->execute([':id' => $id]);
            sendSuccess(null, 'Data Jalan berhasil dihapus');
        } catch (PDOException $e) {
            sendError('Gagal menghapus Jalan: ' . $e->getMessage(), 500);
        }
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}
?>
