<?php
/**
 * warga_miskin.php
 * Tanggung Jawab: Operasi CRUD untuk Warga Miskin.
 */

require_once '../config/db.php';
require_once '../utils/response_helper.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT id, nama_kk, penghasilan, jumlah_tanggungan, ST_AsGeoJSON(geom) as geojson FROM warga_miskin");
            $features = [];
            while ($row = $stmt->fetch()) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => json_decode($row['geojson']),
                    'properties' => [
                        'id' => $row['id'],
                        'nama_kk' => $row['nama_kk'],
                        'penghasilan' => $row['penghasilan'],
                        'jumlah_tanggungan' => $row['jumlah_tanggungan']
                    ]
                ];
            }
            sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data Warga Miskin');
        } catch (PDOException $e) {
            sendError('Gagal: ' . $e->getMessage(), 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nama_kk']) || !isset($input['geometry'])) sendError('Validasi gagal');

        try {
            $sql = "INSERT INTO warga_miskin (nama_kk, penghasilan, jumlah_tanggungan, geom) VALUES (:nama_kk, :penghasilan, :jumlah_tanggungan, ST_GeomFromGeoJSON(:geometry))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama_kk' => $input['nama_kk'],
                ':penghasilan' => $input['penghasilan'] ?? 0,
                ':jumlah_tanggungan' => $input['jumlah_tanggungan'] ?? 0,
                ':geometry' => json_encode($input['geometry'])
            ]);
            sendSuccess(['id' => $pdo->lastInsertId()], 'Disimpan', 201);
        } catch (PDOException $e) {
            sendError('Gagal: ' . $e->getMessage(), 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID dibutuhkan');
        $stmt = $pdo->prepare("DELETE FROM warga_miskin WHERE id = ?");
        $stmt->execute([$id]);
        sendSuccess(null, 'Dihapus');
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}
?>
