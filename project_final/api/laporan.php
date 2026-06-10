<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
startAppSession();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Jika parameter my_reports=1, hanya laporan milik user yang login
        // Jika tidak dan role=admin, ambil semua.
        $my_reports = $_GET['my_reports'] ?? 0;
        
        $sql = "SELECT l.*, ST_X(l.geometry) as lng, ST_Y(l.geometry) as lat, u.nama_lengkap, u.username 
                FROM laporan_warga l 
                JOIN users u ON l.user_id = u.id ";
                
        if ($my_reports || $_SESSION['role'] !== 'admin') {
            // User biasa hanya boleh melihat laporannya sendiri di tabel dashboard, 
            // tapi mungkin butuh lihat semua laporan di peta publik? 
            // Untuk peta public (semua laporan disetujui atau diproses), tambahkan parameter public=1
            if (isset($_GET['public']) && $_GET['public'] == 1) {
                $sql .= " WHERE l.status IN ('diproses', 'selesai') ";
            } else {
                $sql .= " WHERE l.user_id = " . (int)$_SESSION['user_id'];
            }
        }
        $sql .= " ORDER BY l.id DESC";

        $rows = $pdo->query($sql)->fetchAll();
        
        $features = [];
        foreach ($rows as $row) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'pelapor' => $row['nama_lengkap'] ?: $row['username'],
                    'kategori' => $row['kategori'],
                    'deskripsi' => $row['deskripsi'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at']
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float)$row['lng'], (float)$row['lat']]
                ]
            ];
        }
        echo json_encode([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['kategori'], $input['deskripsi'], $input['geometry'])) {
            throw new Exception("Data tidak lengkap.");
        }
        $coords = $input['geometry']['coordinates'];
        $stmt = $pdo->prepare("INSERT INTO laporan_warga (user_id, kategori, deskripsi, geometry) VALUES (?, ?, ?, ST_GeomFromText(?))");
        $stmt->execute([
            $_SESSION['user_id'],
            $input['kategori'],
            $input['deskripsi'],
            "POINT({$coords[0]} {$coords[1]})"
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dibuat.']);
    }
    elseif ($method === 'PUT') {
        if ($_SESSION['role'] !== 'admin') throw new Exception("Unauthorized");
        $id = $_GET['id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true);
        $status = $input['status'] ?? 'menunggu';
        $stmt = $pdo->prepare("UPDATE laporan_warga SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Status laporan diperbarui.']);
    }
    elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        // User bisa hapus kalau status menunggu. Admin bisa hapus semua.
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM laporan_warga WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM laporan_warga WHERE id = ? AND user_id = ? AND status = 'menunggu'");
            $stmt->execute([$id, $_SESSION['user_id']]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Laporan dihapus.']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
