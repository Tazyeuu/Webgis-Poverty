<?php
/**
 * User Layout — sidebar + topbar (read-only mode, beda style dari admin)
 * Variables expected from parent: $pageTitle, $activeNav
 */
$user = currentUser();
$initial = strtoupper(substr($user['nama_lengkap'] ?: $user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'User Portal') ?> — WebGIS Smart City</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/main.css') ?>?v=20260610-spbu-snackbar">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin.css') ?>">
    <script>const APP_BASE = '<?= app_url() ?>';</script>
    <?= $extraHead ?? '' ?>
</head>
<body class="admin-page user-portal <?= $bodyClass ?? '' ?>">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon" style="background:linear-gradient(135deg,#10B981,#059669);">🗺️</div>
        <div class="brand-text">
            <span class="brand-name">WebGIS</span>
            <span class="brand-sub">Pengguna</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu</div>
        <a href="<?= app_url('user/index.php') ?>"
           class="nav-item <?= ($activeNav==='dashboard') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Beranda
        </a>
        <a href="<?= app_url('user/peta.php') ?>"
           class="nav-item <?= ($activeNav==='peta') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-map-marked-alt"></i></span> Peta Interaktif
        </a>
        <a href="<?= app_url('user/laporan.php') ?>"
           class="nav-item <?= ($activeNav==='laporan') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-bullhorn"></i></span> Laporan Warga
        </a>

        <div class="nav-section-title">Informasi</div>
        <div class="user-info" style="background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.2);">
            <i class="fas fa-info-circle" style="color:#059669;font-size:1.1rem;"></i>
            <div style="font-size:0.78rem;color:var(--text-secondary);line-height:1.4;">
                Anda masuk sebagai <strong style="color:#059669">Pengguna</strong>. Data hanya dapat dilihat, tidak dapat diubah.
            </div>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" style="background:linear-gradient(135deg,#10B981,#059669);"><?= $initial ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></div>
                <div class="user-role">Pengguna</div>
            </div>
            <a href="<?= app_url('logout.php') ?>" class="user-logout" title="Keluar">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<main class="admin-main">
    <div class="topbar">
        <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Beranda') ?></span>
        <div class="topbar-actions">
            <div class="topbar-time" id="clock">
                <i class="fas fa-clock"></i> <span id="clockText"></span>
            </div>
            <span class="badge badge-success" style="font-size:0.7rem;padding:5px 10px;">
                <i class="fas fa-eye"></i> Mode Baca
            </span>
        </div>
    </div>
    <div class="admin-content">
