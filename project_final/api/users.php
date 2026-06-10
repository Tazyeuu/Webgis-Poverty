<?php require_once __DIR__ . '/../config/db.php'; require_once __DIR__ . '/helpers.php';
$pdo = Database::getConnection(); $method = $_SERVER['REQUEST_METHOD'];
requireApiRole('admin');
switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id,username,role,nama_lengkap,created_at FROM users ORDER BY id ASC");
        sendSuccess($stmt->fetchAll(), 'Data Users'); break;
    case 'POST':
        $d=getInput();
        if(empty($d['username'])||empty($d['password'])||empty($d['role'])) sendError('Data tidak lengkap');
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        try {
            $pdo->prepare("INSERT INTO users(username,password,role,nama_lengkap) VALUES(?,?,?,?)")->execute([$d['username'],$hash,$d['role'],$d['nama_lengkap']??'']);
            sendSuccess(['id'=>$pdo->lastInsertId()],'User disimpan',201);
        } catch(Exception $e) { sendError('Username sudah digunakan'); }
        break;
    case 'PUT':
        $d=getInput(); $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        if(!empty($d['password'])) {
            $hash=password_hash($d['password'],PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET username=?,password=?,role=?,nama_lengkap=? WHERE id=?")->execute([$d['username'],$hash,$d['role'],$d['nama_lengkap']??'',$id]);
        } else {
            $pdo->prepare("UPDATE users SET username=?,role=?,nama_lengkap=? WHERE id=?")->execute([$d['username'],$d['role'],$d['nama_lengkap']??'',$id]);
        }
        sendSuccess(null,'User diperbarui'); break;
    case 'DELETE':
        $id=$_GET['id']??null; if(!$id) sendError('ID wajib');
        if($id==1) sendError('Admin utama tidak dapat dihapus');
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]); sendSuccess(null,'User dihapus'); break;
    default: sendError('Method not allowed',405);
}
