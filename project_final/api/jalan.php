<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection(); $method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id,nama,jenis_jalan,created_at,ST_AsGeoJSON(geom) as geojson FROM jalan ORDER BY id DESC");
        $features=[];
        while($r=$stmt->fetch()) $features[]=['type'=>'Feature','geometry'=>json_decode($r['geojson']),'properties'=>['id'=>$r['id'],'nama'=>$r['nama'],'jenis_jalan'=>$r['jenis_jalan'],'created_at'=>$r['created_at']]];
        sendSuccess(['type'=>'FeatureCollection','features'=>$features],'Data Jalan'); break;
    case 'POST':
        $d=getInput(); if(empty($d['nama'])||empty($d['geometry'])) sendError('Nama & geometri wajib');
        $pdo->prepare("INSERT INTO jalan(nama,jenis_jalan,geom) VALUES(?,?,ST_GeomFromGeoJSON(?))")->execute([$d['nama'],$d['jenis_jalan']??'Lokal',json_encode($d['geometry'])]);
        sendSuccess(['id'=>$pdo->lastInsertId()],'Jalan disimpan',201); break;
    case 'PUT':
        $d=getInput(); $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("UPDATE jalan SET nama=?,jenis_jalan=? WHERE id=?")->execute([$d['nama'],$d['jenis_jalan'],$id]);
        sendSuccess(null,'Jalan diperbarui'); break;
    case 'DELETE':
        $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("DELETE FROM jalan WHERE id=?")->execute([$id]); sendSuccess(null,'Jalan dihapus'); break;
    default: sendError('Method not allowed',405);
}
