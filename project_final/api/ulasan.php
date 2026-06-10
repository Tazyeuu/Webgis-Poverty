<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
startAppSession();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Hanya pengguna terdaftar yang dapat memberikan ulasan']);
    exit;
}

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['fasilitas_tipe'], $input['fasilitas_id'], $input['rating'])) {
            throw new Exception("Data ulasan tidak lengkap.");
        }
        
        $tipe = $input['fasilitas_tipe'];
        if (!in_array($tipe, ['spbu', 'rumah_ibadah'])) throw new Exception("Tipe fasilitas tidak valid");
        
        $fasilitas_id = (int)$input['fasilitas_id'];
        $rating = (int)$input['rating'];
        $komentar = trim($input['komentar'] ?? '');
        
        if ($rating < 1 || $rating > 5) throw new Exception("Rating harus antara 1 sampai 5");

        // Cek apakah sudah pernah mengulas
        $check = $pdo->prepare("SELECT id FROM ulasan_fasilitas WHERE user_id = ? AND fasilitas_tipe = ? AND fasilitas_id = ?");
        $check->execute([$_SESSION['user_id'], $tipe, $fasilitas_id]);
        if ($check->fetch()) {
            throw new Exception("Anda sudah memberikan ulasan untuk fasilitas ini.");
        }

        $stmt = $pdo->prepare("INSERT INTO ulasan_fasilitas (user_id, fasilitas_tipe, fasilitas_id, rating, komentar) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $tipe, $fasilitas_id, $rating, $komentar]);
        
        echo json_encode(['status' => 'success', 'message' => 'Ulasan berhasil disimpan! Terima kasih atas partisipasi Anda.']);
    } else {
        throw new Exception("Method not allowed");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
