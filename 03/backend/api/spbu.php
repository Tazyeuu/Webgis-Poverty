<?php
require_once '../config/db.php';
require_once '../utils/response_helper.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT id, nama, deskripsi, buka_24_jam, created_at, ST_AsGeoJSON(geom) as geojson FROM spbu");
            $features = [];
            while ($row = $stmt->fetch()) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => json_decode($row['geojson']),
                    'properties' => [
                        'id' => $row['id'],
                        'nama' => $row['nama'],
                        'deskripsi' => $row['deskripsi'],
                        'buka_24_jam' => (bool)$row['buka_24_jam'],
                        'created_at' => $row['created_at']
                    ]
                ];
            }
            sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data SPBU berhasil diambil');
        } catch (PDOException $e) { sendError('Gagal: ' . $e->getMessage(), 500); }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['nama']) || !isset($input['geometry'])) sendError('Validasi gagal');

        try {
            $sql = "INSERT INTO spbu (nama, deskripsi, buka_24_jam, geom) VALUES (:nama, :deskripsi, :buka_24_jam, ST_GeomFromGeoJSON(:geometry))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama' => $input['nama'],
                ':deskripsi' => $input['deskripsi'] ?? '',
                ':buka_24_jam' => isset($input['buka_24_jam']) && $input['buka_24_jam'] ? 1 : 0,
                ':geometry' => json_encode($input['geometry'])
            ]);
            sendSuccess(['id' => $pdo->lastInsertId()], 'Disimpan', 201);
        } catch (PDOException $e) { sendError('Gagal: ' . $e->getMessage(), 500); }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID dibutuhkan');
        $stmt = $pdo->prepare("DELETE FROM spbu WHERE id = ?");
        $stmt->execute([$id]);
        sendSuccess(null, 'Dihapus');
        break;

    default:
        sendError('Method not allowed', 405);
        break;
}
?>
