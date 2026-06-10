<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection(); $method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id,nama_kawasan,created_at,ST_AsGeoJSON(geom) as geojson FROM kawasan_kumuh ORDER BY id DESC");
        $features=[];
        while($r=$stmt->fetch()) $features[]=['type'=>'Feature','geometry'=>json_decode($r['geojson']),'properties'=>['id'=>$r['id'],'nama_kawasan'=>$r['nama_kawasan'],'created_at'=>$r['created_at']]];
        sendSuccess(['type'=>'FeatureCollection','features'=>$features],'Data Kawasan Kumuh'); break;
    case 'POST':
        $d=getInput(); if(empty($d['nama_kawasan'])||empty($d['geometry'])) sendError('Data tidak lengkap');
        $pdo->prepare("INSERT INTO kawasan_kumuh(nama_kawasan,geom) VALUES(?,ST_GeomFromGeoJSON(?))")->execute([$d['nama_kawasan'],json_encode($d['geometry'])]);
        sendSuccess(['id'=>$pdo->lastInsertId()],'Kawasan disimpan',201); break;
    case 'PUT':
        $d=getInput(); $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("UPDATE kawasan_kumuh SET nama_kawasan=? WHERE id=?")->execute([$d['nama_kawasan'],$id]);
        sendSuccess(null,'Kawasan diperbarui'); break;
    case 'DELETE':
        $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("DELETE FROM kawasan_kumuh WHERE id=?")->execute([$id]); sendSuccess(null,'Kawasan dihapus'); break;
    default: sendError('Method not allowed',405);
}
