<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('user');

$pageTitle = 'Peta Interaktif';
$activeNav = 'peta';
$bodyClass = 'admin-map-page map-page';
$extraHead = '
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="' . app_url('assets/css/map.css') . '?v=20260610-2">
';
require_once __DIR__ . '/partials/header.php';
?>

<div class="map-wrap">
    <div id="user-map"></div>

    <div class="read-only-badge">
        <i class="fas fa-eye"></i> Mode Baca
    </div>

    <aside class="map-sidebar">
        <div class="map-panel-header">
            <div class="map-panel-icon"><i class="fas fa-layer-group"></i></div>
            <div>
                <h3>Layer Peta</h3>
                <p>Kontrol data yang tampil di peta</p>
            </div>
        </div>

        <div class="layer-section">
            <div class="layer-section-title">Infrastruktur</div>

            <label class="layer-item">
                <input type="checkbox" id="ly-spbu" checked>
                <span class="layer-color layer-point" style="--layer-color:#F59E0B"><i class="fas fa-gas-pump"></i></span>
                <span class="layer-text">
                    <span class="layer-name">SPBU</span>
                    <span class="layer-meta">Stasiun pengisian bahan bakar</span>
                </span>
            </label>

            <label class="layer-item">
                <input type="checkbox" id="ly-jalan" checked>
                <span class="layer-color layer-line" style="--layer-color:#3B82F6"></span>
                <span class="layer-text">
                    <span class="layer-name">Jalan</span>
                    <span class="layer-meta">Ruas jalan utama dan lokal</span>
                </span>
            </label>

            <label class="layer-item">
                <input type="checkbox" id="ly-kavling" checked>
                <span class="layer-color layer-area" style="--layer-color:#7C3AED"></span>
                <span class="layer-text">
                    <span class="layer-name">Kavling / Parsil</span>
                    <span class="layer-meta">Batas bidang tanah</span>
                </span>
            </label>
        </div>

        <div class="layer-section">
            <div class="layer-section-title">Sosial</div>

            <label class="layer-item">
                <input type="checkbox" id="ly-rumah" checked>
                <span class="layer-color layer-point" style="--layer-color:#8B5CF6"><i class="fas fa-mosque"></i></span>
                <span class="layer-text">
                    <span class="layer-name">Rumah Ibadah</span>
                    <span class="layer-meta">Fasilitas keagamaan</span>
                </span>
            </label>

            <label class="layer-item">
                <input type="checkbox" id="ly-warga" checked>
                <span class="layer-color layer-point" style="--layer-color:#EF4444"><i class="fas fa-users"></i></span>
                <span class="layer-text">
                    <span class="layer-name">Warga Miskin</span>
                    <span class="layer-meta">Sebaran keluarga penerima bantuan</span>
                </span>
            </label>

            <label class="layer-item">
                <input type="checkbox" id="ly-kawasan">
                <span class="layer-color layer-area layer-hatch" style="--layer-color:#DC2626"></span>
                <span class="layer-text">
                    <span class="layer-name">Kawasan Kumuh</span>
                    <span class="layer-meta">Area prioritas penanganan</span>
                </span>
            </label>
        </div>

        <div class="layer-section">
            <div class="layer-section-title">Analisis</div>

            <label class="layer-item">
                <input type="checkbox" id="ly-choropleth">
                <span class="layer-color layer-gradient"></span>
                <span class="layer-text">
                    <span class="layer-name">Choropleth Kepadatan</span>
                    <span class="layer-meta">Kepadatan warga miskin per kawasan</span>
                </span>
            </label>

            <label class="layer-item">
                <input type="checkbox" id="ly-blankspot">
                <span class="layer-color layer-point" style="--layer-color:#F97316"><i class="fas fa-location-dot"></i></span>
                <span class="layer-text">
                    <span class="layer-name">Blank Spot Bansos</span>
                    <span class="layer-meta">Titik jauh dari fasilitas bantuan</span>
                </span>
            </label>
        </div>

        <div class="map-quickstats">
            <div class="qs-item"><div class="qs-value" id="qs-spbu" style="color:#F59E0B">-</div><div class="qs-label">SPBU</div></div>
            <div class="qs-item"><div class="qs-value" id="qs-jalan" style="color:#3B82F6">-</div><div class="qs-label">Jalan</div></div>
            <div class="qs-item"><div class="qs-value" id="qs-kavling" style="color:#7C3AED">-</div><div class="qs-label">Kavling</div></div>
            <div class="qs-item"><div class="qs-value" id="qs-warga" style="color:#EF4444">-</div><div class="qs-label">Warga</div></div>
        </div>
    </aside>

    <div class="map-analysis">
        <button id="btn-find-spbu" class="map-tool-accent" title="Cari SPBU terdekat dari pusat peta">
            <i class="fas fa-search-location"></i> SPBU Terdekat
        </button>
    </div>

    <div class="spbu-panel" id="spbuPanel" style="display:none;">
        <div class="spbu-panel-title">
            <i class="fas fa-search-location"></i> SPBU Terdekat
            <button class="modal-close" id="btn-close-spbu" style="margin-left:auto;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.2rem;">&times;</button>
        </div>
        <div id="spbuPanelResult"></div>
    </div>

    <div class="map-legend">
        <div class="map-legend-title"><i class="fas fa-list-ul"></i> Legenda</div>
        <div class="legend-grid">
            <div class="legend-item"><span class="legend-symbol legend-dot" style="--legend-color:#10B981"></span><span>SPBU 24 Jam</span></div>
            <div class="legend-item"><span class="legend-symbol legend-dot" style="--legend-color:#F59E0B"></span><span>SPBU Terbatas</span></div>
            <div class="legend-item"><span class="legend-symbol legend-line" style="--legend-color:#3B82F6"></span><span>Jalan</span></div>
            <div class="legend-item"><span class="legend-symbol legend-area" style="--legend-color:#7C3AED"></span><span>Kavling</span></div>
            <div class="legend-item"><span class="legend-symbol legend-dot" style="--legend-color:#8B5CF6"></span><span>Rumah Ibadah</span></div>
            <div class="legend-item"><span class="legend-symbol legend-dot" style="--legend-color:#EF4444"></span><span>Warga Miskin</span></div>
            <div class="legend-item"><span class="legend-symbol legend-hatch" style="--legend-color:#DC2626"></span><span>Kawasan Kumuh</span></div>
            <div class="legend-item"><span class="legend-symbol legend-dot" style="--legend-color:#F97316"></span><span>Blank Spot</span></div>
        </div>
    </div>

    <div class="map-loading" id="loadingOverlay" style="display:none;">
        <div class="spinner"></div>
        <span>Memuat data...</span>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= app_url('assets/js/user-map.js') ?>?v=20260610-radius"></script>
<?php $extraScript = ''; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
