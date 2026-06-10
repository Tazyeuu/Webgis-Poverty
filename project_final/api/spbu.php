<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT s.id, s.nama, s.deskripsi, s.buka_24_jam, s.created_at, ST_AsGeoJSON(s.geom) as geojson,
                                    IFNULL(AVG(u.rating), 0) as avg_rating, COUNT(u.id) as total_ulasan
                             FROM spbu s
                             LEFT JOIN ulasan_fasilitas u ON u.fasilitas_id = s.id AND u.fasilitas_tipe = 'spbu'
                             GROUP BY s.id
                             ORDER BY s.id DESC");
        $features = [];
        while ($r = $stmt->fetch()) { 
            $features[] = [
                'type'=>'Feature',
                'geometry'=>json_decode($r['geojson']),
                'properties'=>[
                    'id'=>$r['id'],
                    'nama'=>$r['nama'],
                    'deskripsi'=>$r['deskripsi'],
                    'buka_24_jam'=>(int)$r['buka_24_jam'],
                    'created_at'=>$r['created_at'],
                    'avg_rating'=>round($r['avg_rating'], 1),
                    'total_ulasan'=>$r['total_ulasan']
                ]
            ]; 
        }
        sendSuccess(['type'=>'FeatureCollection','features'=>$features], 'Data SPBU');
        break;
    case 'POST':
        $d = getInput();
        if (empty($d['nama']) || empty($d['geometry'])) sendError('Nama dan geometri wajib diisi');
        $stmt = $pdo->prepare("INSERT INTO spbu (nama, deskripsi, buka_24_jam, geom) VALUES (?,?,?,ST_GeomFromGeoJSON(?))");
        $stmt->execute([$d['nama'], $d['deskripsi']??'', (int)($d['buka_24_jam']??0), json_encode($d['geometry'])]);
        sendSuccess(['id'=>$pdo->lastInsertId()], 'SPBU disimpan', 201);
        break;
    case 'PUT':
        $d = getInput(); $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID wajib disertakan');
        if (!empty($d['geometry'])) {
            $stmt = $pdo->prepare("UPDATE spbu SET geom=ST_GeomFromGeoJSON(?) WHERE id=?");
            $stmt->execute([json_encode($d['geometry']), $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE spbu SET nama=?, deskripsi=?, buka_24_jam=? WHERE id=?");
            $stmt->execute([$d['nama'], $d['deskripsi']??'', (int)($d['buka_24_jam']??0), $id]);
        }
        sendSuccess(null, 'SPBU diperbarui');
        break;
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) sendError('ID wajib disertakan');
        $pdo->prepare("DELETE FROM spbu WHERE id=?")->execute([$id]);
        sendSuccess(null, 'SPBU dihapus');
        break;
    default: sendError('Method not allowed', 405);
}
