<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');

$pdo = Database::getConnection();
$sql = "SELECT l.id, l.kategori, l.deskripsi, l.status, l.created_at, 
               u.nama_lengkap, u.username, ST_X(l.geometry) as lng, ST_Y(l.geometry) as lat
        FROM laporan_warga l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.id DESC";
$rows = $pdo->query($sql)->fetchAll();

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
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Laporan Warga</span></div>
    <h1>📢 Validasi Laporan Warga</h1>
    <p>Kelola dan perbarui status laporan kerusakan infrastruktur dari warga.</p>
</div>

<div class="page-toolbar">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Cari laporan..." oninput="filterTable('searchInput','lapTable')">
    </div>
</div>

<div class="table-wrap">
    <table class="data-table" id="lapTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Pelapor</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($r['nama_lengkap'] ?: $r['username']) ?></strong></td>
                <td><?= htmlspecialchars($r['kategori']) ?></td>
                <td style="color:var(--text-secondary); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($r['deskripsi']) ?>
                </td>
                <td><span class="badge <?= badgeStatus($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                <td style="color:var(--text-muted); font-size:0.8rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                    <div class="actions">
                        <button class="btn btn-ghost btn-sm btn-icon" onclick='lihatLokasi(<?= json_encode($r) ?>)' title="Lihat Lokasi"><i class="fas fa-map-marker-alt" style="color: #3B82F6;"></i></button>
                        <button class="btn btn-ghost btn-sm btn-icon" onclick='editStatus(<?= json_encode($r) ?>)' title="Ubah Status"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?= $r['id'] ?>)" title="Hapus Laporan"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada laporan dari warga.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Ubah Status -->
<div class="modal-overlay" id="modalStatus">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">✏️ Perbarui Status Laporan</span>
            <button class="modal-close" onclick="closeModal('modalStatus')">×</button>
        </div>
        <input type="hidden" id="editId">
        <div class="form-group">
            <label class="form-label">Ubah Status</label>
            <select id="editStatusSelect" class="form-control">
                <option value="menunggu">Menunggu</option>
                <option value="diproses">Diproses</option>
                <option value="selesai">Selesai</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalStatus')">Batal</button>
            <button class="btn btn-primary" onclick="updateStatus()"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </div>
</div>

<!-- Modal Lihat Peta -->
<div class="modal-overlay" id="modalMap">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">📍 Lokasi Laporan</span>
            <button class="modal-close" onclick="closeModal('modalMap')">×</button>
        </div>
        <div style="padding-bottom: 12px; font-size: 0.9rem;">
            <strong id="mapKategori"></strong><br>
            <span style="color: var(--text-secondary);" id="mapDeskripsi"></span>
        </div>
        <div id="miniMapLihat" style="height:300px;border-radius:8px;border:1px solid #E5E7EB;"></div>
    </div>
</div>

<?php $extraScript = <<<'JS'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API = APP_BASE + '/api/laporan.php';

function editStatus(r) {
    document.getElementById('editId').value = r.id;
    document.getElementById('editStatusSelect').value = r.status;
    openModal('modalStatus');
}

async function updateStatus() {
    const id = document.getElementById('editId').value;
    const status = document.getElementById('editStatusSelect').value;
    const res = await fetch(API + '?id=' + id, { 
        method:'PUT', 
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ status: status }) 
    });
    const d = await res.json();
    if(d.status === 'success') { 
        showToast('Status diperbarui!'); 
        setTimeout(() => location.reload(), 900); 
    } else {
        showToast(d.message,'error');
    }
}

async function hapus(id) {
    if(!confirm('Hapus laporan secara permanen?')) return;
    const res = await fetch(API + '?id=' + id, { method:'DELETE' });
    const d = await res.json();
    if(d.status === 'success') { 
        showToast('Laporan dihapus!'); 
        setTimeout(() => location.reload(), 900); 
    } else {
        showToast(d.message,'error');
    }
}

let mapInstance = null, mapMarker = null;
const originalOpenModal = window.openModal;
window.openModal = function(id) {
    originalOpenModal(id);
    if(id === 'modalMap') {
        setTimeout(() => {
            if(mapInstance) mapInstance.invalidateSize();
        }, 100);
    }
};

function lihatLokasi(r) {
    document.getElementById('mapKategori').innerText = r.kategori;
    document.getElementById('mapDeskripsi').innerText = r.deskripsi;
    
    openModal('modalMap');
    
    setTimeout(() => {
        if(!mapInstance) {
            mapInstance = L.map('miniMapLihat');
            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(mapInstance);
        }
        if(mapMarker) mapInstance.removeLayer(mapMarker);
        
        const latLng = [parseFloat(r.lat), parseFloat(r.lng)];
        mapMarker = L.marker(latLng).addTo(mapInstance);
        mapInstance.setView(latLng, 16);
    }, 150);
}
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
