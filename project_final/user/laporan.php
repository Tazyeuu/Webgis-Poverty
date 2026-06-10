<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('user');

$pdo = Database::getConnection();
// Fetch user's own reports
$stmt = $pdo->prepare("SELECT id, kategori, deskripsi, status, created_at FROM laporan_warga WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();

$pageTitle = 'Laporan Warga';
$activeNav = 'laporan';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require_once __DIR__ . '/partials/header.php';

function badgeStatus($status) {
    if($status === 'menunggu') return 'badge-warning';
    if($status === 'diproses') return 'badge-info';
    if($status === 'selesai') return 'badge-success';
    if($status === 'ditolak') return 'badge-danger';
    return 'badge-primary';
}
?>

<div class="page-header">
    <div class="breadcrumb"><span>User</span><span class="sep">/</span><span>Laporan Warga</span></div>
    <h1>📢 Laporan Fasilitas & Infrastruktur</h1>
    <p>Laporkan masalah infrastruktur seperti jalan rusak, banjir, atau fasilitas umum yang terbengkalai.</p>
</div>

<div class="page-toolbar">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Cari laporan saya..." oninput="filterTable('searchInput','lapTable')">
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">
        <i class="fas fa-plus"></i> Buat Laporan Baru
    </button>
</div>

<div class="table-wrap">
    <table class="data-table" id="lapTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Kategori</th>
                <th>Deskripsi Singkat</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($r['kategori']) ?></strong></td>
                <td style="color:var(--text-secondary); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($r['deskripsi']) ?>
                </td>
                <td><span class="badge <?= badgeStatus($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                <td style="color:var(--text-muted); font-size:0.8rem;"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></td>
                <td>
                    <div class="actions">
                        <?php if($r['status'] === 'menunggu'): ?>
                            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?= $r['id'] ?>)" title="Batalkan Laporan"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <span style="font-size:0.75rem; color:var(--text-muted);">Dikunci</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">
                    Anda belum pernah membuat laporan.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Tambah Laporan -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">📢 Buat Laporan Baru</span>
            <button class="modal-close" onclick="closeModal('modalTambah')">×</button>
        </div>
        <form id="formTambah">
            <div class="form-group">
                <label class="form-label">Kategori Masalah *</label>
                <select name="kategori" class="form-control" required>
                    <option value="Jalan Rusak">Jalan Rusak</option>
                    <option value="Banjir">Banjir</option>
                    <option value="Fasilitas Terbengkalai">Fasilitas Terbengkalai</option>
                    <option value="Lampu Jalan Mati">Lampu Jalan Mati</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi Lengkap *</label>
                <textarea name="deskripsi" class="form-control" placeholder="Jelaskan detail masalahnya..." required rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Pilih Lokasi di Peta *</label>
                <div id="miniMapTambah" style="height:200px;border-radius:8px;border:1px solid #E5E7EB;margin-bottom:10px;z-index:1;"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <input type="number" name="lat" id="latTambah" class="form-control" placeholder="Latitude" step="any" required readonly style="background:#F9FAFB;">
                    <input type="number" name="lng" id="lngTambah" class="form-control" placeholder="Longitude" step="any" required readonly style="background:#F9FAFB;">
                </div>
            </div>
        </form>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button>
            <button class="btn btn-primary" onclick="simpan()"><i class="fas fa-paper-plane"></i> Kirim Laporan</button>
        </div>
    </div>
</div>

<?php $extraScript = <<<'JS'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API = APP_BASE + '/api/laporan.php';

let miniMap = null, miniMarker = null;
const originalOpenModal = window.openModal;
window.openModal = function(id) {
    originalOpenModal(id);
    if(id === 'modalTambah') {
        setTimeout(() => {
            if(!miniMap) {
                miniMap = L.map('miniMapTambah').setView([-0.0583, 109.3448], 13);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(miniMap);
                miniMap.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    if(!miniMarker) miniMarker = L.marker([lat, lng]).addTo(miniMap);
                    else miniMarker.setLatLng([lat, lng]);
                    document.getElementById('latTambah').value = lat.toFixed(6);
                    document.getElementById('lngTambah').value = lng.toFixed(6);
                });
            } else {
                miniMap.invalidateSize();
            }
        }, 100);
    }
};

async function simpan() {
    const f = document.getElementById('formTambah');
    const lat = parseFloat(f.lat.value), lng = parseFloat(f.lng.value);
    if (!f.kategori.value || !f.deskripsi.value || isNaN(lat) || isNaN(lng)) { 
        showToast('Lengkapi kategori, deskripsi, dan pilih lokasi!','error'); 
        return; 
    }
    const res = await fetch(API, { 
        method:'POST', 
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ 
            kategori: f.kategori.value, 
            deskripsi: f.deskripsi.value,
            geometry: { type:'Point', coordinates:[lng, lat] }
        }) 
    });
    const d = await res.json();
    if(d.status === 'success') { 
        showToast('Laporan berhasil dikirim!'); 
        setTimeout(() => location.reload(), 900); 
    } else {
        showToast(d.message,'error');
    }
}

async function hapus(id) {
    if(!confirm('Apakah Anda yakin ingin membatalkan laporan ini?')) return;
    const res = await fetch(API + '?id=' + id, { method:'DELETE' });
    const d = await res.json();
    if(d.status === 'success') { 
        showToast('Laporan dibatalkan!'); 
        setTimeout(() => location.reload(), 900); 
    } else {
        showToast(d.message,'error');
    }
}
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
