<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection(); $method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id,nama_pemilik,status_kepemilikan,luas,created_at,ST_AsGeoJSON(geom) as geojson FROM kavling ORDER BY id DESC");
        $features=[];
        while($r=$stmt->fetch()) $features[]=['type'=>'Feature','geometry'=>json_decode($r['geojson']),'properties'=>['id'=>$r['id'],'nama_pemilik'=>$r['nama_pemilik'],'status_kepemilikan'=>$r['status_kepemilikan'],'luas'=>$r['luas'],'created_at'=>$r['created_at']]];
        sendSuccess(['type'=>'FeatureCollection','features'=>$features],'Data Kavling'); break;
    case 'POST':
        $d=getInput(); if(empty($d['nama_pemilik'])||empty($d['status_kepemilikan'])||empty($d['geometry'])) sendError('Data tidak lengkap');
        $pdo->prepare("INSERT INTO kavling(nama_pemilik,status_kepemilikan,luas,geom) VALUES(?,?,?,ST_GeomFromGeoJSON(?))")->execute([$d['nama_pemilik'],$d['status_kepemilikan'],$d['luas']??0,json_encode($d['geometry'])]);
        sendSuccess(['id'=>$pdo->lastInsertId()],'Kavling disimpan',201); break;
    case 'PUT':
        $d=getInput(); $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        if(!empty($d['geometry'])){
            $pdo->prepare("UPDATE kavling SET geom=ST_GeomFromGeoJSON(?) WHERE id=?")->execute([json_encode($d['geometry']),$id]);
        } else {
            $pdo->prepare("UPDATE kavling SET nama_pemilik=?,status_kepemilikan=?,luas=? WHERE id=?")->execute([$d['nama_pemilik'],$d['status_kepemilikan'],$d['luas']??0,$id]);
        }
        sendSuccess(null,'Kavling diperbarui'); break;
    case 'DELETE':
        $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("DELETE FROM kavling WHERE id=?")->execute([$id]); sendSuccess(null,'Kavling dihapus'); break;
    default: sendError('Method not allowed',405);
}
