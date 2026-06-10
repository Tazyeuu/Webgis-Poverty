<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id,nama,agama,radius_bantuan_meter,created_at FROM rumah_ibadah ORDER BY id DESC")->fetchAll();
$pageTitle='Rumah Ibadah'; $activeNav='rumah';
$extraHead = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
require_once __DIR__ . '/partials/header.php';
$agamaIcon = ['Islam'=>'🕌','Kristen'=>'⛪','Katolik'=>'⛪','Hindu'=>'🛕','Buddha'=>'☸️','Konghucu'=>'🏯'];
$agamaBadge = ['Islam'=>'badge-success','Kristen'=>'badge-info','Katolik'=>'badge-primary','Hindu'=>'badge-warning','Buddha'=>'badge-danger','Konghucu'=>'badge-warning'];

$grouped = [];
foreach($agamaIcon as $ag => $ic) { $grouped[$ag] = []; }
foreach($rows as $r) {
    if(!isset($grouped[$r['agama']])) $grouped[$r['agama']] = [];
    $grouped[$r['agama']][] = $r;
}
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Rumah Ibadah</span></div>
    <h1>🕌 Manajemen Rumah Ibadah</h1>
    <p>Kelola data rumah ibadah untuk 5 agama yang diakui di Indonesia.</p>
</div>
<div class="page-toolbar">
    <div class="search-box"><i class="fas fa-search search-icon"></i><input type="text" id="searchInput" placeholder="Cari rumah ibadah..." oninput="filterTableMulti()"></div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah</button>
</div>
<?php foreach($grouped as $agama => $list): ?>
    <h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 1.1rem; color: var(--text-primary); border-bottom: 2px solid var(--border-light); padding-bottom: 8px;">
        <?= $agamaIcon[$agama] ?? '🏛' ?> <?= $agama ?> <span class="badge badge-info" style="margin-left: 8px;"><?= count($list) ?></span>
    </h3>
    <div class="table-wrap" style="margin-bottom: 32px;">
    <table class="data-table ri-table">
        <thead><tr><th>#</th><th>Nama</th><th>Radius Bantuan</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach($list as $i=>$r): ?>
        <tr>
            <td style="color:var(--text-muted)"><?=$i+1?></td>
            <td><strong><?=htmlspecialchars($r['nama'])?></strong></td>
            <td><span class="badge badge-info"><?= number_format((int)$r['radius_bantuan_meter'], 0, ',', '.') ?> m</span></td>
            <td style="color:var(--text-muted);font-size:0.8rem;"><?=date('d M Y',strtotime($r['created_at']))?></td>
            <td><div class="actions">
                <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?=htmlspecialchars(json_encode($r))?>)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?=$r['id']?>,'<?=addslashes($r['nama'])?>')"><i class="fas fa-trash"></i></button>
            </div></td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$list):?><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted);">Belum ada data <?=$agama?>.</td></tr><?php endif;?>
        </tbody>
    </table></div>
<?php endforeach; ?>

<div class="modal-overlay" id="modalTambah"><div class="modal">
    <div class="modal-header"><span class="modal-title">🕌 Tambah Rumah Ibadah</span><button class="modal-close" onclick="closeModal('modalTambah')">×</button></div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Nama *</label><input type="text" name="nama" class="form-control" placeholder="Nama rumah ibadah" required></div>
        <div class="form-group"><label class="form-label">Agama *</label>
            <select name="agama" class="form-control">
                <option value="Islam">🕌 Islam</option><option value="Kristen">⛪ Kristen</option>
                <option value="Katolik">⛪ Katolik</option><option value="Hindu">🛕 Hindu</option>
                <option value="Buddha">☸️ Buddha</option><option value="Konghucu">🏯 Konghucu</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Radius Bantuan (meter) *</label><input type="number" name="radius_bantuan_meter" class="form-control" min="100" max="10000" step="50" value="1000" required></div>
        <div class="form-group"><label class="form-label">Pilih Lokasi di Peta</label>
            <div id="miniMapTambah" style="height:200px;border-radius:8px;border:1px solid #E5E7EB;margin-bottom:10px;z-index:1;"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <input type="number" name="lat" id="latTambah" class="form-control" placeholder="Latitude" step="any" readonly style="background:#F9FAFB;">
                <input type="number" name="lng" id="lngTambah" class="form-control" placeholder="Longitude" step="any" readonly style="background:#F9FAFB;">
            </div>
        </div>
    </form>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button><button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan</button></div>
</div></div>

<div class="modal-overlay" id="modalEdit"><div class="modal">
    <div class="modal-header"><span class="modal-title">✏️ Edit Rumah Ibadah</span><button class="modal-close" onclick="closeModal('modalEdit')">×</button></div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Nama *</label><input type="text" id="editNama" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Agama</label>
        <select id="editAgama" class="form-control">
            <option value="Islam">🕌 Islam</option><option value="Kristen">⛪ Kristen</option>
            <option value="Katolik">⛪ Katolik</option><option value="Hindu">🛕 Hindu</option>
            <option value="Buddha">☸️ Buddha</option><option value="Konghucu">🏯 Konghucu</option>
        </select>
    </div>
    <div class="form-group"><label class="form-label">Radius Bantuan (meter) *</label><input type="number" id="editRadius" class="form-control" min="100" max="10000" step="50" required></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button><button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button></div>
</div></div>

<?php $extraScript = <<<'JS'
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const API=APP_BASE + '/api/rumah_ibadah.php';

let miniMap = null, miniMarker = null;

// Override openModal to initialize map
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

function filterTableMulti() {
    const v = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.ri-table').forEach(tbl => {
        tbl.querySelectorAll('tbody tr').forEach(tr => {
            if(tr.cells.length > 1) tr.style.display = tr.innerText.toLowerCase().includes(v) ? '' : 'none';
        });
    });
}
function normalizeRadius(v){ const n=parseInt(v,10); if(Number.isNaN(n)) return 1000; return Math.max(100, Math.min(10000, n)); }
function editRow(r){ document.getElementById('editId').value=r.id; document.getElementById('editNama').value=r.nama; document.getElementById('editAgama').value=r.agama; document.getElementById('editRadius').value=normalizeRadius(r.radius_bantuan_meter); openModal('modalEdit'); }
async function simpan(){ const f=document.getElementById('formTambah'); if(!f.nama.value){showToast('Nama wajib!','error');return;}
    const lat=parseFloat(f.lat.value)||(-0.03), lng=parseFloat(f.lng.value)||(109.34);
    const res=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama:f.nama.value,agama:f.agama.value,radius_bantuan_meter:normalizeRadius(f.radius_bantuan_meter.value),geometry:{type:'Point',coordinates:[lng,lat]}})}); const d=await res.json(); if(d.status==='success'){showToast('Rumah ibadah disimpan!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function update(){ const id=document.getElementById('editId').value; const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama:document.getElementById('editNama').value,agama:document.getElementById('editAgama').value,radius_bantuan_meter:normalizeRadius(document.getElementById('editRadius').value)})}); const d=await res.json(); if(d.status==='success'){showToast('Diperbarui!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function hapus(id,nama){ if(!confirm(`Hapus "${nama}"?`))return; const res=await fetch(API+'?id='+id,{method:'DELETE'}); const d=await res.json(); if(d.status==='success'){showToast('Dihapus!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
