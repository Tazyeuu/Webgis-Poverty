<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id,nama_pemilik,status_kepemilikan,luas,created_at FROM kavling ORDER BY id DESC")->fetchAll();
$pageTitle='Data Kavling'; $activeNav='kavling';
require_once __DIR__ . '/partials/header.php';
$statusColors = ['SHM'=>'badge-success','HGB'=>'badge-info','HGU'=>'badge-warning','HP'=>'badge-primary'];
$statusFull = ['SHM'=>'Sertifikat Hak Milik','HGB'=>'Hak Guna Bangunan','HGU'=>'Hak Guna Usaha','HP'=>'Hak Pakai'];
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Kavling</span></div>
    <h1>🏘️ Manajemen Kavling / Parsil Tanah</h1>
    <p>Kelola data bidang tanah berdasarkan status kepemilikan sertifikat.</p>
</div>
<div class="page-toolbar">
    <div class="search-box"><i class="fas fa-search search-icon"></i><input type="text" id="searchInput" placeholder="Cari kavling..." oninput="filterTable('searchInput','kavTable')"></div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah Kavling</button>
</div>
<div class="table-wrap">
<table class="data-table" id="kavTable">
    <thead><tr><th>#</th><th>Nama Pemilik</th><th>Status Kepemilikan</th><th>Luas (m²)</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($rows as $i=>$r): $sk=$r['status_kepemilikan']; ?>
    <tr>
        <td style="color:var(--text-muted)"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($r['nama_pemilik'])?></strong></td>
        <td><span class="badge <?=$statusColors[$sk]??'badge-primary'?>" title="<?=$statusFull[$sk]??$sk?>"><?=$sk?></span></td>
        <td><?=$r['luas'] ? number_format($r['luas'],0,',','.') : '-'?></td>
        <td style="color:var(--text-muted);font-size:0.8rem;"><?=date('d M Y',strtotime($r['created_at']))?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?=htmlspecialchars(json_encode($r))?>)"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?=$r['id']?>,'<?=addslashes($r['nama_pemilik'])?>')"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$rows):?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data kavling.</td></tr><?php endif;?>
    </tbody>
</table></div>

<div class="modal-overlay" id="modalTambah"><div class="modal">
    <div class="modal-header"><span class="modal-title">🏘️ Tambah Kavling</span><button class="modal-close" onclick="closeModal('modalTambah')">×</button></div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Nama Pemilik *</label><input type="text" name="nama_pemilik" class="form-control" placeholder="Nama lengkap pemilik" required></div>
        <div class="form-group"><label class="form-label">Status Kepemilikan *</label>
            <select name="status_kepemilikan" class="form-control">
                <option value="SHM">Sertifikat Hak Milik (SHM)</option>
                <option value="HGB">Sertifikat Hak Guna Bangunan (HGB)</option>
                <option value="HGU">Sertifikat Hak Guna Usaha (HGU)</option>
                <option value="HP">Sertifikat Hak Pakai (HP)</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Luas (m²)</label><input type="number" name="luas" class="form-control" placeholder="Luas bidang tanah" min="0" step="0.01"></div>
        <p style="font-size:0.82rem;color:var(--text-muted);background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:8px;padding:10px;"><i class="fas fa-info-circle"></i> Polygon kavling bisa digambar di <a href="<?= app_url('admin/peta.php') ?>" style="color:var(--primary-light);">Peta Interaktif</a>.</p>
    </form>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button><button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan</button></div>
</div></div>

<div class="modal-overlay" id="modalEdit"><div class="modal">
    <div class="modal-header"><span class="modal-title">✏️ Edit Kavling</span><button class="modal-close" onclick="closeModal('modalEdit')">×</button></div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Nama Pemilik *</label><input type="text" id="editNama" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Status Kepemilikan</label>
        <select id="editStatus" class="form-control">
            <option value="SHM">SHM</option><option value="HGB">HGB</option><option value="HGU">HGU</option><option value="HP">HP</option>
        </select>
    </div>
    <div class="form-group"><label class="form-label">Luas (m²)</label><input type="number" id="editLuas" class="form-control" min="0" step="0.01"></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button><button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button></div>
</div></div>

<?php $extraScript = <<<'JS'
<script>
const API=APP_BASE + '/api/kavling.php';
function editRow(r){ document.getElementById('editId').value=r.id; document.getElementById('editNama').value=r.nama_pemilik; document.getElementById('editStatus').value=r.status_kepemilikan; document.getElementById('editLuas').value=r.luas||0; openModal('modalEdit'); }
async function simpan(){ const f=document.getElementById('formTambah'); if(!f.nama_pemilik.value){showToast('Nama pemilik wajib diisi!','error');return;} const res=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama_pemilik:f.nama_pemilik.value,status_kepemilikan:f.status_kepemilikan.value,luas:parseFloat(f.luas.value)||0,geometry:{type:'Polygon',coordinates:[[]]}})}); const d=await res.json(); if(d.status==='success'){showToast('Kavling disimpan!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function update(){ const id=document.getElementById('editId').value; const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama_pemilik:document.getElementById('editNama').value,status_kepemilikan:document.getElementById('editStatus').value,luas:parseFloat(document.getElementById('editLuas').value)||0})}); const d=await res.json(); if(d.status==='success'){showToast('Kavling diperbarui!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function hapus(id,nama){ if(!confirm(`Hapus kavling milik "${nama}"?`))return; const res=await fetch(API+'?id='+id,{method:'DELETE'}); const d=await res.json(); if(d.status==='success'){showToast('Kavling dihapus!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
