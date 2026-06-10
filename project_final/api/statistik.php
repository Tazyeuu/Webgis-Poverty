<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

// Summary counts
$counts = $pdo->query("SELECT
    (SELECT COUNT(*) FROM spbu) as total_spbu,
    (SELECT COUNT(*) FROM jalan) as total_jalan,
    (SELECT COUNT(*) FROM kavling) as total_kavling,
    (SELECT COUNT(*) FROM rumah_ibadah) as total_rumah_ibadah,
    (SELECT COUNT(*) FROM warga_miskin) as total_warga_miskin,
    (SELECT COUNT(*) FROM kawasan_kumuh) as total_kawasan_kumuh,
    (SELECT COUNT(*) FROM spbu WHERE buka_24_jam=1) as spbu_24jam
")->fetch();

// Choropleth: kawasan kumuh + jumlah warga di dalamnya
$sql = "SELECT k.id, k.nama_kawasan, ST_AsGeoJSON(k.geom) as geojson,
               COUNT(w.id) as jumlah_warga,
               COALESCE(SUM(w.jumlah_tanggungan),0) as total_tanggungan
        FROM kawasan_kumuh k
        LEFT JOIN warga_miskin w ON ST_Contains(k.geom, w.geom)
        GROUP BY k.id";
$stmt = $pdo->query($sql);
$features = [];
$merahCount = 0;
while ($r = $stmt->fetch()) {
    $jumlah = (int)$r['jumlah_warga'];
    if ($jumlah > 3) $merahCount++;
    $features[] = [
        'type' => 'Feature',
        'geometry' => json_decode($r['geojson']),
        'properties' => [
            'id' => $r['id'],
            'nama_kawasan' => $r['nama_kawasan'],
            'jumlah_warga' => $jumlah,
            'total_tanggungan' => (int)$r['total_tanggungan'],
        ]
    ];
}

// Rumah ibadah by agama
$byAgama = $pdo->query("SELECT agama, COUNT(*) as total FROM rumah_ibadah GROUP BY agama")->fetchAll();

// Kavling by status
$byStatus = $pdo->query("SELECT status_kepemilikan, COUNT(*) as total FROM kavling GROUP BY status_kepemilikan")->fetchAll();

sendSuccess([
    'counts' => $counts,
    'kawasan_merah' => $merahCount,
    'choropleth' => ['type' => 'FeatureCollection', 'features' => $features],
    'rumah_by_agama' => $byAgama,
    'kavling_by_status' => $byStatus,
], 'Statistik OK');
