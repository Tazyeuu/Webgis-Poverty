<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id,nama_kk,penghasilan,jumlah_tanggungan,created_at FROM warga_miskin ORDER BY id DESC")->fetchAll();
$pageTitle='Warga Miskin'; $activeNav='warga';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Warga Miskin</span></div>
    <h1>👥 Manajemen Data Warga Miskin</h1>
    <p>Kelola data kepala keluarga yang terdaftar sebagai penerima bantuan sosial.</p>
</div>
<div class="page-toolbar">
    <div class="search-box"><i class="fas fa-search search-icon"></i><input type="text" id="searchInput" placeholder="Cari warga..." oninput="filterTable('searchInput','wargaTable')"></div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah Data</button>
</div>
<div class="table-wrap">
<table class="data-table" id="wargaTable">
    <thead><tr><th>#</th><th>Nama KK</th><th>Penghasilan/Bln</th><th>Tanggungan</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($rows as $i=>$r): ?>
    <tr>
        <td style="color:var(--text-muted)"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($r['nama_kk'])?></strong></td>
        <td>Rp <?=number_format($r['penghasilan'],0,',','.')?></td>
        <td><span class="badge <?=$r['jumlah_tanggungan']>=4?'badge-danger':($r['jumlah_tanggungan']>=2?'badge-warning':'badge-success')?>"><?=$r['jumlah_tanggungan']?> orang</span></td>
        <td style="color:var(--text-muted);font-size:0.8rem;"><?=date('d M Y',strtotime($r['created_at']))?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?=htmlspecialchars(json_encode($r))?>)"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?=$r['id']?>,'<?=addslashes($r['nama_kk'])?>')"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$rows):?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data.</td></tr><?php endif;?>
    </tbody>
</table></div>

<div class="modal-overlay" id="modalTambah"><div class="modal">
    <div class="modal-header"><span class="modal-title">👥 Tambah Warga Miskin</span><button class="modal-close" onclick="closeModal('modalTambah')">×</button></div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Nama Kepala Keluarga *</label><input type="text" name="nama_kk" class="form-control" placeholder="Nama lengkap KK" required></div>
        <div class="form-group"><label class="form-label">Penghasilan per Bulan (Rp)</label><input type="number" name="penghasilan" class="form-control" placeholder="0" min="0"></div>
        <div class="form-group"><label class="form-label">Jumlah Tanggungan (jiwa)</label><input type="number" name="jumlah_tanggungan" class="form-control" placeholder="0" min="0" max="20"></div>
        <div class="form-group"><label class="form-label">Pilih Lokasi di Peta *</label>
            <div id="miniMapTambah" style="height:200px;border-radius:8px;border:1px solid #E5E7EB;margin-bottom:10px;z-index:1;"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <input type="number" name="lat" id="latTambah" class="form-control" placeholder="Latitude" step="any" required readonly style="background:#F9FAFB;">
                <input type="number" name="lng" id="lngTambah" class="form-control" placeholder="Longitude" step="any" required readonly style="background:#F9FAFB;">
            </div>
        </div>
    </form>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button><button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan</button></div>
</div></div>

<div class="modal-overlay" id="modalEdit"><div class="modal">
    <div class="modal-header"><span class="modal-title">✏️ Edit Data Warga</span><button class="modal-close" onclick="closeModal('modalEdit')">×</button></div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Nama KK *</label><input type="text" id="editNama" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Penghasilan/Bln (Rp)</label><input type="number" id="editPenghasilan" class="form-control" min="0"></div>
    <div class="form-group"><label class="form-label">Jumlah Tanggungan</label><input type="number" id="editTanggungan" class="form-control" min="0" max="20"></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button><button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button></div>
</div></div>

<?php $extraScript = <<<'JS'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API=APP_BASE + '/api/warga_miskin.php';

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
function editRow(r){ document.getElementById('editId').value=r.id; document.getElementById('editNama').value=r.nama_kk; document.getElementById('editPenghasilan').value=r.penghasilan; document.getElementById('editTanggungan').value=r.jumlah_tanggungan; openModal('modalEdit'); }
async function simpan(){ const f=document.getElementById('formTambah'); if(!f.nama_kk.value){showToast('Nama KK wajib!','error');return;} const lat=parseFloat(f.lat.value), lng=parseFloat(f.lng.value); if(isNaN(lat)||isNaN(lng)){showToast('Silakan pilih lokasi di peta!','error');return;} const res=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama_kk:f.nama_kk.value,penghasilan:parseFloat(f.penghasilan.value)||0,jumlah_tanggungan:parseInt(f.jumlah_tanggungan.value)||0,geometry:{type:'Point',coordinates:[lng,lat]}})}); const d=await res.json(); if(d.status==='success'){showToast('Data disimpan!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function update(){ const id=document.getElementById('editId').value; const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama_kk:document.getElementById('editNama').value,penghasilan:parseFloat(document.getElementById('editPenghasilan').value)||0,jumlah_tanggungan:parseInt(document.getElementById('editTanggungan').value)||0})}); const d=await res.json(); if(d.status==='success'){showToast('Diperbarui!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function hapus(id,nama){ if(!confirm(`Hapus data "${nama}"?`))return; const res=await fetch(API+'?id='+id,{method:'DELETE'}); const d=await res.json(); if(d.status==='success'){showToast('Dihapus!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
