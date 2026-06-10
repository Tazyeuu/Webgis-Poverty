<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id,nama,jenis_jalan,created_at FROM jalan ORDER BY id DESC")->fetchAll();
$pageTitle='Data Jalan'; $activeNav='jalan';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Data Jalan</span></div>
    <h1>🛣️ Manajemen Data Jalan</h1>
    <p>Kelola data ruas jalan yang terpetakan dalam sistem.</p>
</div>
<div class="page-toolbar">
    <div class="search-box"><i class="fas fa-search search-icon"></i><input type="text" id="searchInput" placeholder="Cari jalan..." oninput="filterTable('searchInput','jalanTable')"></div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah Jalan</button>
</div>
<div class="table-wrap">
<table class="data-table" id="jalanTable">
    <thead><tr><th>#</th><th>Nama Jalan</th><th>Jenis</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($rows as $i=>$r): ?>
    <tr>
        <td style="color:var(--text-muted)"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($r['nama'])?></strong></td>
        <td><span class="badge badge-info"><?=htmlspecialchars($r['jenis_jalan']??'-')?></span></td>
        <td style="color:var(--text-muted);font-size:0.8rem;"><?=date('d M Y',strtotime($r['created_at']))?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?=htmlspecialchars(json_encode($r))?>)"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?=$r['id']?>,'<?=addslashes($r['nama'])?>')"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$rows):?><tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data jalan.</td></tr><?php endif;?>
    </tbody>
</table></div>

<div class="modal-overlay" id="modalTambah"><div class="modal">
    <div class="modal-header"><span class="modal-title">🛣️ Tambah Jalan</span><button class="modal-close" onclick="closeModal('modalTambah')">×</button></div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Nama Jalan *</label><input type="text" name="nama" class="form-control" placeholder="Nama ruas jalan" required></div>
        <div class="form-group"><label class="form-label">Jenis Jalan *</label>
            <select name="jenis_jalan" class="form-control">
                <option value="Jalan Arteri">Jalan Arteri</option>
                <option value="Jalan Kolektor">Jalan Kolektor</option>
                <option value="Jalan Lokal" selected>Jalan Lokal</option>
                <option value="Jalan Lingkungan">Jalan Lingkungan</option>
                <option value="Jalan Tol">Jalan Tol</option>
            </select>
        </div>
        <p style="font-size:0.82rem;color:var(--text-muted);background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:8px;padding:10px;"><i class="fas fa-info-circle"></i> Geometri jalan (LineString) hanya bisa ditambahkan melalui <a href="<?= app_url('admin/peta.php') ?>" style="color:var(--primary-light);">Peta Interaktif</a>.</p>
    </form>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button><button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan (Tanpa Geometri)</button></div>
</div></div>

<div class="modal-overlay" id="modalEdit"><div class="modal">
    <div class="modal-header"><span class="modal-title">✏️ Edit Jalan</span><button class="modal-close" onclick="closeModal('modalEdit')">×</button></div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Nama Jalan *</label><input type="text" id="editNama" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Jenis Jalan</label>
        <select id="editJenis" class="form-control">
            <option value="Jalan Arteri">Jalan Arteri</option><option value="Jalan Kolektor">Jalan Kolektor</option>
            <option value="Jalan Lokal">Jalan Lokal</option><option value="Jalan Lingkungan">Jalan Lingkungan</option><option value="Jalan Tol">Jalan Tol</option>
        </select>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button><button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button></div>
</div></div>

<?php $extraScript = <<<'JS'
<script>
const API=APP_BASE + '/api/jalan.php';
function editRow(r){ document.getElementById('editId').value=r.id; document.getElementById('editNama').value=r.nama; document.getElementById('editJenis').value=r.jenis_jalan||'Jalan Lokal'; openModal('modalEdit'); }
async function simpan(){ const f=document.getElementById('formTambah'); const res=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama:f.nama.value,jenis_jalan:f.jenis_jalan.value,geometry:{type:'LineString',coordinates:[]}})}); const d=await res.json(); if(d.status==='success'){showToast('Jalan disimpan!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function update(){ const id=document.getElementById('editId').value; const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama:document.getElementById('editNama').value,jenis_jalan:document.getElementById('editJenis').value})}); const d=await res.json(); if(d.status==='success'){showToast('Jalan diperbarui!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function hapus(id,nama){ if(!confirm(`Hapus jalan "${nama}"?`))return; const res=await fetch(API+'?id='+id,{method:'DELETE'}); const d=await res.json(); if(d.status==='success'){showToast('Jalan dihapus!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
