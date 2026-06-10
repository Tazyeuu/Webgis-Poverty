<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id, nama, deskripsi, buka_24_jam, created_at FROM spbu ORDER BY id DESC")->fetchAll();
$pageTitle = 'Data SPBU'; $activeNav = 'spbu';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Data SPBU</span></div>
    <h1>⛽ Manajemen Data SPBU</h1>
    <p>Kelola data Stasiun Pengisian Bahan Bakar Umum (SPBU) di wilayah pemetaan.</p>
</div>
<div class="page-toolbar">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Cari SPBU..." oninput="filterTable('searchInput','spbuTable')">
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">
        <i class="fas fa-plus"></i> Tambah SPBU
    </button>
</div>
<div class="table-wrap">
<table class="data-table" id="spbuTable">
    <thead><tr>
        <th>#</th><th>Nama SPBU</th><th>Deskripsi</th><th>Status</th><th>Ditambahkan</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
    <tr>
        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
        <td><strong><?= htmlspecialchars($r['nama']) ?></strong></td>
        <td style="color:var(--text-secondary)"><?= htmlspecialchars($r['deskripsi'] ?: '-') ?></td>
        <td><span class="badge <?= $r['buka_24_jam'] ? 'badge-success' : 'badge-warning' ?>"><?= $r['buka_24_jam'] ? '✅ 24 Jam' : '🕐 Terbatas' ?></span></td>
        <td style="color:var(--text-muted); font-size:0.8rem;"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?= htmlspecialchars(json_encode($r)) ?>)" title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?= $r['id'] ?>,'<?= addslashes($r['nama']) ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data SPBU. <a href="#" onclick="openModal('modalTambah')">Tambah sekarang</a></td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title">⛽ Tambah SPBU Baru</span>
        <button class="modal-close" onclick="closeModal('modalTambah')">×</button>
    </div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Nama SPBU *</label><input type="text" name="nama" class="form-control" placeholder="Nama SPBU" required></div>
        <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" placeholder="Keterangan tambahan..."></textarea></div>
        <div class="form-group"><label class="form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;"><input type="checkbox" name="buka_24_jam" id="cb24jam" style="width:16px;height:16px;accent-color:var(--accent);"> Buka 24 Jam</label></div>
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
        <button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan</button>
    </div>
</div></div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title">✏️ Edit Data SPBU</span>
        <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
    </div>
    <form id="formEdit">
        <input type="hidden" name="id" id="editId">
        <div class="form-group"><label class="form-label">Nama SPBU *</label><input type="text" name="nama" id="editNama" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="deskripsi" id="editDesk" class="form-control"></textarea></div>
        <div class="form-group"><label class="form-label" style="display:flex;align-items:center;gap:10px;cursor:pointer;"><input type="checkbox" id="editCb24" style="width:16px;height:16px;accent-color:var(--accent);"> Buka 24 Jam</label></div>
    </form>
    <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button>
        <button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button>
    </div>
</div></div>

<?php $extraScript = <<<'JS'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API = APP_BASE + '/api/spbu.php';

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

function editRow(r) {
    document.getElementById('editId').value   = r.id;
    document.getElementById('editNama').value = r.nama;
    document.getElementById('editDesk').value = r.deskripsi || '';
    document.getElementById('editCb24').checked = !!r.buka_24_jam;
    openModal('modalEdit');
}

async function simpan() {
    const f = document.getElementById('formTambah');
    const lat = parseFloat(f.lat.value), lng = parseFloat(f.lng.value);
    if (!f.nama.value || isNaN(lat) || isNaN(lng)) { showToast('Lengkapi nama dan pilih lokasi di peta!','error'); return; }
    const res = await fetch(API, { method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ nama:f.nama.value, deskripsi:f.deskripsi.value,
            buka_24_jam: f.querySelector('[name=buka_24_jam]').checked ? 1:0,
            geometry:{ type:'Point', coordinates:[lng,lat] }}) });
    const d = await res.json();
    if(d.status==='success') { showToast('SPBU berhasil disimpan!'); setTimeout(()=>location.reload(),900); }
    else showToast(d.message,'error');
}

async function update() {
    const id = document.getElementById('editId').value;
    const res = await fetch(API+'?id='+id, { method:'PUT', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ nama:document.getElementById('editNama').value,
            deskripsi:document.getElementById('editDesk').value,
            buka_24_jam: document.getElementById('editCb24').checked ? 1:0 }) });
    const d = await res.json();
    if(d.status==='success') { showToast('SPBU berhasil diperbarui!'); setTimeout(()=>location.reload(),900); }
    else showToast(d.message,'error');
}

async function hapus(id, nama) {
    if(!confirm(`Hapus SPBU "${nama}"?`)) return;
    const res = await fetch(API+'?id='+id, { method:'DELETE' });
    const d = await res.json();
    if(d.status==='success') { showToast('SPBU dihapus!'); setTimeout(()=>location.reload(),900); }
    else showToast(d.message,'error');
}
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
