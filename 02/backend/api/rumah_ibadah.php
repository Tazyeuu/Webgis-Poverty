<?php
/**
 * rumah_ibadah.php
 * Tanggung Jawab: Operasi CRUD Rumah Ibadah + Endpoint analisis jangkauan Haversine.
 */

require_once '../config/db.php';
require_once '../utils/response_helper.php';
require_once '../utils/geo_helper.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'jangkauan' && isset($_GET['id'])) {
            // Analisis Jangkauan Haversine
            $id = $_GET['id'];
            $radius = isset($_GET['radius']) ? (float) $_GET['radius'] : 1.0; // default 1 km
            
            // Dapatkan koordinat rumah ibadah ini
            $stmt = $pdo->prepare("SELECT ST_AsGeoJSON(geom) as geojson FROM rumah_ibadah WHERE id = ?");
            $stmt->execute([$id]);
            $rumah_ibadah = $stmt->fetch();
            
            if (!$rumah_ibadah) sendError('Rumah ibadah tidak ditemukan', 404);
            
            $geom = json_decode($rumah_ibadah['geojson'], true);
            $pusatLon = $geom['coordinates'][0];
            $pusatLat = $geom['coordinates'][1];

            // Panggil geo_helper untuk mendapatkan warga miskin
            $warga = GeoHelper::getWargaDalamRadius($pdo, $pusatLat, $pusatLon, $radius);
            sendSuccess($warga, 'Data jangkauan berhasil dihitung');
            
        } else {
            // GET Semua Rumah Ibadah (GeoJSON)
            try {
                $stmt = $pdo->query("SELECT id, nama, agama, ST_AsGeoJSON(geom) as geojson FROM rumah_ibadah");
                $features = [];
                while ($row = $stmt->fetch()) {
                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => json_decode($row['geojson']),
                        'properties' => [
                            'id' => $row['id'],
                            'nama' => $row['nama'],
                            'agama' => $row['agama']
                        ]
                    ];
                }
                sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data Rumah Ibadah');
            } catch (PDOException $e) {
                sendError('Gagal: ' . $e->getMessage(), 500);
            }
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nama']) || !isset($input['geometry'])) sendError('Validasi gagal');

        try {
            $sql = "INSERT INTO rumah_ibadah (nama, agama, geom) VALUES (:nama, :agama, ST_GeomFromGeoJSON(:geometry))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama' => $input['nama'],
                ':agama' => $input['agama'] ?? 'Islam',
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
        $stmt = $pdo->prepare("DELETE FROM rumah_ibadah WHERE id = ?");
        $stmt->execute([$id]);
        sendSuccess(null, 'Dihapus');
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}
?>
