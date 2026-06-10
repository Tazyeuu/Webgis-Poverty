<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');

$pdo = Database::getConnection();

// Fetch summary stats
$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM spbu) as spbu,
    (SELECT COUNT(*) FROM jalan) as jalan,
    (SELECT COUNT(*) FROM kavling) as kavling,
    (SELECT COUNT(*) FROM rumah_ibadah) as rumah_ibadah,
    (SELECT COUNT(*) FROM warga_miskin) as warga_miskin,
    (SELECT COUNT(*) FROM kawasan_kumuh) as kawasan_kumuh,
    (SELECT COUNT(*) FROM spbu WHERE buka_24_jam=1) as spbu_24jam,
    (SELECT COUNT(*) FROM users) as total_users
")->fetch();

// Kawasan merah
$merahResult = $pdo->query("SELECT COUNT(*) as cnt FROM (
    SELECT k.id FROM kawasan_kumuh k
    LEFT JOIN warga_miskin w ON ST_Contains(k.geom, w.geom)
    GROUP BY k.id HAVING COUNT(w.id) > 3
) sub")->fetch();
$kawasanMerah = (int)($merahResult['cnt'] ?? 0);

// Warga miskin di luar semua radius bantuan rumah ibadah
$blankCount = $pdo->query("
    SELECT COUNT(*) as cnt
    FROM warga_miskin w
    WHERE NOT EXISTS (
        SELECT 1
        FROM rumah_ibadah ri
        WHERE ST_Distance_Sphere(ST_SRID(w.geom, 4326), ST_SRID(ri.geom, 4326)) <= ri.radius_bantuan_meter
    )
")->fetch();
$blankSpot = (int)($blankCount['cnt'] ?? 0);

// Recent data
$recentSpbu = $pdo->query("SELECT nama, buka_24_jam, created_at FROM spbu ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentWarga = $pdo->query("SELECT nama_kk, penghasilan, jumlah_tanggungan, created_at FROM warga_miskin ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/header.php';
?>

<!-- ── Quick Access Map ── -->
<div class="card" style="background: #EFF6FF; border: 1px solid #BFDBFE; margin-bottom: 24px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:6px; color:#1E3A8A;">🗺️ Buka Peta Interaktif</h3>
            <p style="color:#3B82F6; font-size:0.88rem; max-width:500px;">
                Tambahkan, edit, dan analisis data spasial langsung di peta. Mendukung drawing tool untuk semua layer data.
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?= app_url('admin/peta.php') ?>" class="btn btn-primary" style="box-shadow:0 4px 6px -1px rgba(37,99,235,0.2);">
                <i class="fas fa-map-marked-alt"></i> Buka Peta Admin
            </a>
        </div>
    </div>
</div>

<!-- ── Stats Grid ── -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon"><i class="fas fa-gas-pump"></i></div>
        <div class="stat-body">
            <div class="stat-label">Total SPBU</div>
            <div class="stat-value" id="cnt-spbu"><?= $stats['spbu'] ?></div>
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
            <div class="stat-change">5 agama tercakup</div>
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
            <div class="stat-change">Bidang tanah tercatat</div>
        </div>
    </div>
</div>

<!-- ── Content Row ── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">

    <!-- Recent SPBU -->
    <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700;">⛽ SPBU Terbaru</h3>
            <a href="<?= app_url('admin/data_spbu.php') ?>" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <?php if ($recentSpbu): ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($recentSpbu as $s): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                <div>
                    <div style="font-weight:600; font-size:0.88rem;"><?= htmlspecialchars($s['nama']) ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= date('d M Y', strtotime($s['created_at'])) ?></div>
                </div>
                <span class="badge <?= $s['buka_24_jam'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $s['buka_24_jam'] ? '24 Jam' : 'Terbatas' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted); text-align:center; padding:20px 0; font-size:0.85rem;">Belum ada data SPBU</p>
        <?php endif; ?>
    </div>

    <!-- Recent Warga Miskin -->
    <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700;">👥 Warga Terdaftar</h3>
            <a href="<?= app_url('admin/data_warga.php') ?>" class="btn btn-ghost btn-sm">Lihat Semua</a>
        </div>
        <?php if ($recentWarga): ?>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($recentWarga as $w): ?>
            <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px;">
                <div>
                    <div style="font-weight:600; font-size:0.88rem;"><?= htmlspecialchars($w['nama_kk']) ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted);">Rp <?= number_format($w['penghasilan'],0,',','.') ?>/bln · <?= $w['jumlah_tanggungan'] ?> tanggungan</div>
                </div>
                <span class="badge badge-warning"><?= $w['jumlah_tanggungan'] ?> org</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted); text-align:center; padding:20px 0; font-size:0.85rem;">Belum ada data warga</p>
        <?php endif; ?>
    </div>
</div>



<?php require_once __DIR__ . '/partials/footer.php'; ?>
