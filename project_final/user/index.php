<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('user');

$pdo = Database::getConnection();
$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM spbu) as spbu,
    (SELECT COUNT(*) FROM jalan) as jalan,
    (SELECT COUNT(*) FROM kavling) as kavling,
    (SELECT COUNT(*) FROM rumah_ibadah) as rumah_ibadah,
    (SELECT COUNT(*) FROM warga_miskin) as warga_miskin,
    (SELECT COUNT(*) FROM kawasan_kumuh) as kawasan_kumuh,
    (SELECT COUNT(*) FROM spbu WHERE buka_24_jam=1) as spbu_24jam
")->fetch();

// Hitung kawasan merah (rawan)
$merahResult = $pdo->query("SELECT COUNT(*) as cnt FROM (
    SELECT k.id FROM kawasan_kumuh k
    LEFT JOIN warga_miskin w ON ST_Contains(k.geom, w.geom)
    GROUP BY k.id HAVING COUNT(w.id) > 3
) sub")->fetch();
$kawasanMerah = (int)($merahResult['cnt'] ?? 0);

// Hitung blank spot
$blankCount = $pdo->query("
    SELECT COUNT(*) as cnt
    FROM warga_miskin w
    WHERE NOT EXISTS (
        SELECT 1
        FROM rumah_ibadah ri
        WHERE ST_Distance_Sphere(w.geom, ri.geom) <= ri.radius_bantuan_meter
    )
")->fetch();
$blankSpot = (int)($blankCount['cnt'] ?? 0);

$pageTitle = 'Beranda';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/header.php';
?>

<div class="card" style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: var(--radius-lg); padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <h2 style="font-size:1.4rem;font-weight:700;margin-bottom:6px;color:var(--text-primary);">👋 Selamat Datang, <?= htmlspecialchars(currentUser()['nama_lengkap'] ?: currentUser()['username']) ?>!</h2>
    <p style="color:var(--text-secondary);font-size:0.9rem;max-width:600px;">
        Anda masuk sebagai <strong>Pengguna</strong>. Anda dapat melihat seluruh data spasial, analisis, dan berpartisipasi dengan mengirim Laporan Warga atau memberikan ulasan fasilitas.
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-gas-pump"></i></div>
        <div class="stat-body">
            <div class="stat-label">Total SPBU</div>
            <div class="stat-value"><?= $stats['spbu'] ?></div>
            <div class="stat-change"><?= $stats['spbu_24jam'] ?> buka 24 jam</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-road"></i></div>
        <div class="stat-body">
            <div class="stat-label">Ruas Jalan</div>
            <div class="stat-value"><?= $stats['jalan'] ?></div>
            <div class="stat-change">Segmen terpetakan</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fas fa-mosque"></i></div>
        <div class="stat-body">
            <div class="stat-label">Rumah Ibadah</div>
            <div class="stat-value"><?= $stats['rumah_ibadah'] ?></div>
            <div class="stat-change">Lokasi tercatat</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-body">
            <div class="stat-label">Warga Miskin</div>
            <div class="stat-value"><?= $stats['warga_miskin'] ?></div>
            <div class="stat-change"><?= $blankSpot ?> blank spot</div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-body">
            <div class="stat-label">Kawasan Rawan</div>
            <div class="stat-value"><?= $kawasanMerah ?></div>
            <div class="stat-change">dari <?= $stats['kawasan_kumuh'] ?> kawasan</div>
        </div>
    </div>
    <div class="stat-card violet">
        <div class="stat-icon"><i class="fas fa-vector-square"></i></div>
        <div class="stat-body">
            <div class="stat-label">Kavling / Parsil</div>
            <div class="stat-value"><?= $stats['kavling'] ?></div>
            <div class="stat-change">Bidang tanah</div>
        </div>
    </div>
</div>

<div class="card" style="background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: var(--radius-lg); padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:6px;">🗺️ Buka Peta Interaktif</h3>
            <p style="color:var(--text-secondary); font-size:0.88rem; max-width:500px;">
                Lihat semua layer data spasial: SPBU, jalan, kavling, rumah ibadah, warga miskin, kawasan kumuh, dan analisis spasial.
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= app_url('user/peta.php') ?>" class="btn btn-success">
                <i class="fas fa-map-marked-alt"></i> Buka Peta
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
