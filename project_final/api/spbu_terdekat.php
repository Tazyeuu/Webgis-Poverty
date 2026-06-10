<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);
$lat = (float)($_GET['lat'] ?? 0); $lng = (float)($_GET['lng'] ?? 0);
if (!$lat || !$lng) sendError('lat dan lng wajib diisi');
$wkt = sprintf('POINT(%F %F)', $lng, $lat);
$stmt = $pdo->prepare("SELECT id, nama, buka_24_jam, ST_AsGeoJSON(geom) as geojson,
    ST_Distance_Sphere(ST_SRID(geom, 4326), ST_SRID(ST_GeomFromText(?), 4326)) / 1000 as jarak_km
    FROM spbu ORDER BY jarak_km ASC LIMIT 1");
$stmt->execute([$wkt]);
$r = $stmt->fetch();
if (!$r) sendError('Tidak ada SPBU di database', 404);
sendSuccess(['type'=>'Feature','geometry'=>json_decode($r['geojson']),'properties'=>['id'=>$r['id'],'nama'=>$r['nama'],'buka_24_jam'=>(int)$r['buka_24_jam'],'jarak_km'=>round($r['jarak_km'],2)]], 'SPBU terdekat');
