<?php
/**
 * Admin Layout — sidebar + topbar
 * Include di awal setiap halaman admin setelah auth check.
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
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?> — WebGIS Smart City</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/main.css') ?>?v=20260610-spbu-snackbar">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin.css') ?>">
    <script>const APP_BASE = '<?= app_url() ?>';</script>
    <?= $extraHead ?? '' ?>
</head>
<body class="admin-page <?= $bodyClass ?? '' ?>">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🗺️</div>
        <div class="brand-text">
            <span class="brand-name">WebGIS</span>
            <span class="brand-sub">Smart City Admin</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Utama</div>
        <a href="<?= app_url('admin/index.php') ?>"
           class="nav-item <?= ($activeNav==='dashboard') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-chart-pie"></i></span> Dashboard
        </a>
        <a href="<?= app_url('admin/peta.php') ?>"
           class="nav-item <?= ($activeNav==='peta') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-map-marked-alt"></i></span> Peta Interaktif
        </a>

        <div class="nav-section-title">Data Spasial</div>
        <a href="<?= app_url('admin/data_spbu.php') ?>"
           class="nav-item <?= ($activeNav==='spbu') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-gas-pump"></i></span> SPBU
        </a>
        <a href="<?= app_url('admin/data_jalan.php') ?>"
           class="nav-item <?= ($activeNav==='jalan') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-road"></i></span> Jalan
        </a>
        <a href="<?= app_url('admin/data_kavling.php') ?>"
           class="nav-item <?= ($activeNav==='kavling') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-vector-square"></i></span> Kavling / Parsil
        </a>
        <a href="<?= app_url('admin/data_rumah.php') ?>"
           class="nav-item <?= ($activeNav==='rumah') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-mosque"></i></span> Rumah Ibadah
        </a>
        <a href="<?= app_url('admin/data_warga.php') ?>"
           class="nav-item <?= ($activeNav==='warga') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-users"></i></span> Warga Miskin
        </a>
        <a href="<?= app_url('admin/data_kawasan.php') ?>"
           class="nav-item <?= ($activeNav==='kawasan') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-draw-polygon"></i></span> Kawasan Kumuh
        </a>
        <a href="<?= app_url('admin/data_laporan.php') ?>"
           class="nav-item <?= ($activeNav==='laporan') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-bullhorn"></i></span> Laporan Warga
        </a>

        <div class="nav-section-title">Manajemen</div>
        <a href="<?= app_url('admin/users.php') ?>"
           class="nav-item <?= ($activeNav==='users') ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fas fa-user-shield"></i></span> Kelola Users
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= $initial ?></div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?></div>
                <div class="user-role">Administrator</div>
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
        <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
        <div class="topbar-actions">
            <div class="topbar-time" id="clock">
                <i class="fas fa-clock"></i> <span id="clockText"></span>
            </div>
            <a href="<?= app_url('admin/peta.php') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-map"></i> Buka Peta
            </a>
        </div>
    </div>
    <div class="admin-content">
