<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT k.id, k.nama_kawasan, k.created_at, COUNT(w.id) as jumlah_warga
    FROM kawasan_kumuh k LEFT JOIN warga_miskin w ON ST_Contains(k.geom, w.geom)
    GROUP BY k.id ORDER BY k.id DESC")->fetchAll();
$pageTitle='Kawasan Kumuh'; $activeNav='kawasan';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Kawasan Kumuh</span></div>
    <h1>⚠️ Manajemen Kawasan Kumuh</h1>
    <p>Kelola data kawasan analisis spasial kepadatan warga miskin (choropleth).</p>
</div>
<div class="page-toolbar">
    <div class="search-box"><i class="fas fa-search search-icon"></i><input type="text" id="searchInput" placeholder="Cari kawasan..." oninput="filterTable('searchInput','kawasanTable')"></div>
    <a href="<?= app_url('admin/peta.php') ?>" class="btn btn-primary"><i class="fas fa-map"></i> Gambar di Peta</a>
</div>
<div class="table-wrap">
<table class="data-table" id="kawasanTable">
    <thead><tr><th>#</th><th>Nama Kawasan</th><th>Warga Terdampak</th><th>Status</th><th>Ditambahkan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach($rows as $i=>$r): $cnt=(int)$r['jumlah_warga']; $status=$cnt>3?'Rawan Kumuh':($cnt>0?'Perlu Perhatian':'Aman'); $badge=$cnt>3?'badge-danger':($cnt>0?'badge-warning':'badge-success'); ?>
    <tr>
        <td style="color:var(--text-muted)"><?=$i+1?></td>
        <td><strong><?=htmlspecialchars($r['nama_kawasan'])?></strong></td>
        <td style="text-align:center;font-size:1.1rem;font-weight:700;"><?=$cnt?></td>
        <td><span class="badge <?=$badge?>"><?=$status?></span></td>
        <td style="color:var(--text-muted);font-size:0.8rem;"><?=date('d M Y',strtotime($r['created_at']))?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick="editRow(<?=htmlspecialchars(json_encode($r))?>)"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?=$r['id']?>,'<?=addslashes($r['nama_kawasan'])?>')"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$rows):?><tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data kawasan. Gambar polygon di peta untuk menambahkan.</td></tr><?php endif;?>
    </tbody>
</table></div>

<div class="modal-overlay" id="modalEdit"><div class="modal">
    <div class="modal-header"><span class="modal-title">✏️ Edit Kawasan</span><button class="modal-close" onclick="closeModal('modalEdit')">×</button></div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Nama Kawasan *</label><input type="text" id="editNama" class="form-control" required></div>
    <div class="modal-footer"><button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button><button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button></div>
</div></div>

<?php $extraScript = <<<'JS'
<script>
const API=APP_BASE + '/api/kawasan_kumuh.php';
function editRow(r){ document.getElementById('editId').value=r.id; document.getElementById('editNama').value=r.nama_kawasan; openModal('modalEdit'); }
async function update(){ const id=document.getElementById('editId').value; const res=await fetch(API+'?id='+id,{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({nama_kawasan:document.getElementById('editNama').value})}); const d=await res.json(); if(d.status==='success'){showToast('Kawasan diperbarui!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
async function hapus(id,nama){ if(!confirm(`Hapus kawasan "${nama}"?`))return; const res=await fetch(API+'?id='+id,{method:'DELETE'}); const d=await res.json(); if(d.status==='success'){showToast('Kawasan dihapus!');setTimeout(()=>location.reload(),900);}else showToast(d.message,'error'); }
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
