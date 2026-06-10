<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection(); $method = $_SERVER['REQUEST_METHOD'];
requireAdminForMutation();
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id,nama_kk,penghasilan,jumlah_tanggungan,created_at,ST_AsGeoJSON(geom) as geojson FROM warga_miskin ORDER BY id DESC");
        $features=[];
        while($r=$stmt->fetch()) $features[]=['type'=>'Feature','geometry'=>json_decode($r['geojson']),'properties'=>['id'=>$r['id'],'nama_kk'=>$r['nama_kk'],'penghasilan'=>$r['penghasilan'],'jumlah_tanggungan'=>$r['jumlah_tanggungan'],'created_at'=>$r['created_at']]];
        sendSuccess(['type'=>'FeatureCollection','features'=>$features],'Data Warga Miskin'); break;
    case 'POST':
        $d=getInput(); if(empty($d['nama_kk'])||empty($d['geometry'])) sendError('Data tidak lengkap');
        $pdo->prepare("INSERT INTO warga_miskin(nama_kk,penghasilan,jumlah_tanggungan,geom) VALUES(?,?,?,ST_GeomFromGeoJSON(?))")->execute([$d['nama_kk'],$d['penghasilan']??0,$d['jumlah_tanggungan']??0,json_encode($d['geometry'])]);
        sendSuccess(['id'=>$pdo->lastInsertId()],'Warga disimpan',201); break;
    case 'PUT':
        $d=getInput(); $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        if(!empty($d['geometry'])){
            $pdo->prepare("UPDATE warga_miskin SET geom=ST_GeomFromGeoJSON(?) WHERE id=?")->execute([json_encode($d['geometry']),$id]);
        } else {
            $pdo->prepare("UPDATE warga_miskin SET nama_kk=?,penghasilan=?,jumlah_tanggungan=? WHERE id=?")->execute([$d['nama_kk'],$d['penghasilan']??0,$d['jumlah_tanggungan']??0,$id]);
        }
        sendSuccess(null,'Warga diperbarui'); break;
    case 'DELETE':
        $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        $pdo->prepare("DELETE FROM warga_miskin WHERE id=?")->execute([$id]); sendSuccess(null,'Warga dihapus'); break;
    default: sendError('Method not allowed',405);
}
