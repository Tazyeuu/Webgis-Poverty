<?php
/**
 * kavling.php
 * Tanggung Jawab: Menangani operasi CRUD untuk entitas Kavling (Polygon).
 */

require_once '../config/db.php';
require_once '../utils/response_helper.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT id, nama_pemilik, luas, created_at, ST_AsGeoJSON(geom) as geojson FROM kavling");
            $features = [];
            while ($row = $stmt->fetch()) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => json_decode($row['geojson']),
                    'properties' => [
                        'id' => $row['id'],
                        'nama_pemilik' => $row['nama_pemilik'],
                        'luas' => $row['luas'],
                        'created_at' => $row['created_at']
                    ]
                ];
            }
            sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data Kavling berhasil diambil');
        } catch (PDOException $e) {
            sendError('Gagal mengambil data Kavling: ' . $e->getMessage(), 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nama_pemilik']) || !isset($input['geometry'])) {
            sendError('Nama pemilik dan geometri wajib diisi');
        }

        try {
            $sql = "INSERT INTO kavling (nama_pemilik, luas, geom) VALUES (:nama_pemilik, :luas, ST_GeomFromGeoJSON(:geometry))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama_pemilik' => $input['nama_pemilik'],
                ':luas' => $input['luas'] ?? 0,
                ':geometry' => json_encode($input['geometry'])
            ]);
            sendSuccess(['id' => $pdo->lastInsertId()], 'Data Kavling berhasil disimpan', 201);
        } catch (PDOException $e) {
            sendError('Gagal menyimpan Kavling: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendError('ID Kavling wajib disertakan');
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM kavling WHERE id = :id");
            $stmt->execute([':id' => $id]);
            sendSuccess(null, 'Data Kavling berhasil dihapus');
        } catch (PDOException $e) {
            sendError('Gagal menghapus Kavling: ' . $e->getMessage(), 500);
        }
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}
?>
