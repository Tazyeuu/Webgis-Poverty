<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

// Warga miskin yang berada di luar semua radius bantuan rumah ibadah.
$sql = "
    SELECT
        w.id,
        w.nama_kk,
        w.penghasilan,
        ST_AsGeoJSON(w.geom) AS geojson,
        ri.nama AS rumah_ibadah_terdekat,
        ST_Distance_Sphere(ST_SRID(w.geom, 4326), ST_SRID(ri.geom, 4326)) AS jarak_meter,
        ri.radius_bantuan_meter,
        (ST_Distance_Sphere(ST_SRID(w.geom, 4326), ST_SRID(ri.geom, 4326)) - ri.radius_bantuan_meter) AS selisih_meter
    FROM warga_miskin w
    JOIN rumah_ibadah ri
    JOIN (
        SELECT
            nearest_distance.warga_id,
            MIN(nearest_distance.jarak_meter) AS min_jarak_meter
        FROM (
            SELECT
                w2.id AS warga_id,
                ST_Distance_Sphere(ST_SRID(w2.geom, 4326), ST_SRID(ri2.geom, 4326)) AS jarak_meter
            FROM warga_miskin w2
            CROSS JOIN rumah_ibadah ri2
        ) nearest_distance
        GROUP BY nearest_distance.warga_id
    ) nearest
        ON nearest.warga_id = w.id
        AND ABS(ST_Distance_Sphere(ST_SRID(w.geom, 4326), ST_SRID(ri.geom, 4326)) - nearest.min_jarak_meter) < 0.001
    WHERE NOT EXISTS (
        SELECT 1
        FROM rumah_ibadah ri_in_radius
        WHERE ST_Distance_Sphere(ST_SRID(w.geom, 4326), ST_SRID(ri_in_radius.geom, 4326)) <= ri_in_radius.radius_bantuan_meter
    )
    GROUP BY w.id
    ORDER BY selisih_meter DESC
";

$stmt = $pdo->query($sql);
$features = [];

while ($r = $stmt->fetch()) {
    $features[] = [
        'type' => 'Feature',
        'geometry' => json_decode($r['geojson']),
        'properties' => [
            'id' => (int)$r['id'],
            'nama_kk' => $r['nama_kk'],
            'jarak_km' => round(((float)$r['jarak_meter']) / 1000, 2),
            'radius_km' => round(((int)$r['radius_bantuan_meter']) / 1000, 2),
            'selisih_km' => round(((float)$r['selisih_meter']) / 1000, 2),
            'rumah_ibadah_terdekat' => $r['rumah_ibadah_terdekat']
        ]
    ];
}

sendSuccess(['type' => 'FeatureCollection', 'features' => $features], 'Blank Spot');
