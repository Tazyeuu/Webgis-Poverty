<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();

function normalizeRadius($value): int {
    $radius = (int)($value ?? 1000);
    if ($radius < 100) return 100;
    if ($radius > 10000) return 10000;
    return $radius;
}

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("
            SELECT
                r.id,
                r.nama,
                r.agama,
                r.radius_bantuan_meter,
                r.created_at,
                ST_AsGeoJSON(r.geom) AS geojson,
                IFNULL(AVG(u.rating), 0) AS avg_rating,
                COUNT(u.id) AS total_ulasan
            FROM rumah_ibadah r
            LEFT JOIN ulasan_fasilitas u
                ON u.fasilitas_id = r.id
                AND u.fasilitas_tipe = 'rumah_ibadah'
            GROUP BY r.id
            ORDER BY r.id DESC
        ");

        $features = [];
        while ($r = $stmt->fetch()) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($r['geojson']),
                'properties' => [
                    'id' => (int)$r['id'],
                    'nama' => $r['nama'],
                    'agama' => $r['agama'],
                    'radius_bantuan_meter' => (int)$r['radius_bantuan_meter'],
                    'radius_bantuan_km' => round(((int)$r['radius_bantuan_meter']) / 1000, 2),
                    'created_at' => $r['created_at'],
                    'avg_rating' => round((float)$r['avg_rating'], 1),
                    'total_ulasan' => (int)$r['total_ulasan']
                ]
            ];
        }

        sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Data Rumah Ibadah');
        break;

    case 'POST':
        $d = getInput();
        if (empty($d['nama']) || empty($d['agama']) || empty($d['geometry'])) {
            sendError('Data tidak lengkap');
        }

        $radius = normalizeRadius($d['radius_bantuan_meter'] ?? null);
        $pdo->prepare("
            INSERT INTO rumah_ibadah (nama, agama, radius_bantuan_meter, geom)
            VALUES (?, ?, ?, ST_GeomFromGeoJSON(?))
        ")->execute([$d['nama'], $d['agama'], $radius, json_encode($d['geometry'])]);

        sendSuccess(['id' => $pdo->lastInsertId()], 'Rumah Ibadah disimpan', 201);
        break;

    case 'PUT':
        $d = getInput();
        $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID wajib');

        if (!empty($d['geometry'])) {
            $pdo->prepare("UPDATE rumah_ibadah SET geom = ST_GeomFromGeoJSON(?) WHERE id = ?")
                ->execute([json_encode($d['geometry']), $id]);
        } else {
            $radius = normalizeRadius($d['radius_bantuan_meter'] ?? null);
            $pdo->prepare("
                UPDATE rumah_ibadah
                SET nama = ?, agama = ?, radius_bantuan_meter = ?
                WHERE id = ?
            ")->execute([$d['nama'], $d['agama'], $radius, $id]);
        }

        sendSuccess(null, 'Rumah Ibadah diperbarui');
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID wajib');

        $pdo->prepare("DELETE FROM rumah_ibadah WHERE id = ?")->execute([$id]);
        sendSuccess(null, 'Rumah Ibadah dihapus');
        break;

    default:
        sendError('Method not allowed', 405);
}
