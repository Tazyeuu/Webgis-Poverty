<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';
requireRole('admin');
$pdo = Database::getConnection();
$rows = $pdo->query("SELECT id, username, role, nama_lengkap, created_at FROM users ORDER BY id ASC")->fetchAll();
$pageTitle = 'Kelola Users';
$activeNav = 'users';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <div class="breadcrumb"><span>Admin</span><span class="sep">/</span><span>Users</span></div>
    <h1>👤 Manajemen User Sistem</h1>
    <p>Kelola akun administrator dan pengguna yang dapat masuk ke WebGIS Smart City.</p>
</div>
<div class="page-toolbar">
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Cari user..." oninput="filterTable('searchInput','usersTable')">
    </div>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">
        <i class="fas fa-plus"></i> Tambah User
    </button>
</div>
<div class="table-wrap">
<table class="data-table" id="usersTable">
    <thead><tr>
        <th>#</th><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Dibuat</th><th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): ?>
    <tr>
        <td style="color:var(--text-muted)"><?= $i+1 ?></td>
        <td><strong><?= htmlspecialchars($r['username']) ?></strong></td>
        <td><?= htmlspecialchars($r['nama_lengkap'] ?: '-') ?></td>
        <td>
            <?php if ($r['role'] === 'admin'): ?>
                <span class="badge badge-danger">👨‍💼 Admin</span>
            <?php else: ?>
                <span class="badge badge-info">👤 Pengguna</span>
            <?php endif; ?>
        </td>
        <td style="color:var(--text-muted); font-size:0.8rem;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        <td><div class="actions">
            <button class="btn btn-ghost btn-sm btn-icon" onclick='editRow(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Edit">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm btn-icon" onclick="hapus(<?= $r['id'] ?>,'<?= addslashes($r['username']) ?>')" title="Hapus">
                <i class="fas fa-trash"></i>
            </button>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title">👤 Tambah User Baru</span>
        <button class="modal-close" onclick="closeModal('modalTambah')">×</button>
    </div>
    <form id="formTambah">
        <div class="form-group"><label class="form-label">Username *</label><input type="text" name="username" id="tUsername" class="form-control" placeholder="username unik" required></div>
        <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" id="tNama" class="form-control" placeholder="Nama lengkap"></div>
        <div class="form-group"><label class="form-label">Role *</label>
            <select name="role" id="tRole" class="form-control">
                <option value="user">👤 Pengguna (read-only)</option>
                <option value="admin">👨‍💼 Administrator</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" id="tPassword" class="form-control" placeholder="Minimal 6 karakter" required minlength="6"></div>
    </form>
    <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modalTambah')">Batal</button>
        <button class="btn btn-primary" onclick="simpan()"><i class="fas fa-save"></i> Simpan</button>
    </div>
</div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title">✏️ Edit User</span>
        <button class="modal-close" onclick="closeModal('modalEdit')">×</button>
    </div>
    <input type="hidden" id="editId">
    <div class="form-group"><label class="form-label">Username *</label><input type="text" id="editUsername" class="form-control" required></div>
    <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" id="editNama" class="form-control"></div>
    <div class="form-group"><label class="form-label">Role *</label>
        <select id="editRole" class="form-control">
            <option value="user">👤 Pengguna</option>
            <option value="admin">👨‍💼 Administrator</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Password Baru</label>
        <input type="password" id="editPassword" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah" minlength="6">
        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">Kosongkan jika tidak ingin mengubah password.</div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-ghost" onclick="closeModal('modalEdit')">Batal</button>
        <button class="btn btn-primary" onclick="update()"><i class="fas fa-save"></i> Perbarui</button>
    </div>
</div>
</div>

<?php $extraScript = <<<'JS'
<script>
const API = APP_BASE + '/api/users.php';

function editRow(r) {
    document.getElementById('editId').value = r.id;
    document.getElementById('editUsername').value = r.username;
    document.getElementById('editNama').value = r.nama_lengkap || '';
    document.getElementById('editRole').value = r.role;
    document.getElementById('editPassword').value = '';
    openModal('modalEdit');
}

async function simpan() {
    const u = document.getElementById('tUsername').value.trim();
    const p = document.getElementById('tPassword').value;
    if (!u) return showToast('Username wajib!', 'error');
    if (p.length < 6) return showToast('Password minimal 6 karakter!', 'error');

    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            username: u,
            nama_lengkap: document.getElementById('tNama').value,
            role: document.getElementById('tRole').value,
            password: p
        })
    });
    const d = await res.json();
    if (d.status === 'success') { showToast('User berhasil ditambahkan!'); setTimeout(() => location.reload(), 900); }
    else showToast(d.message || 'Gagal', 'error');
}

async function update() {
    const id = document.getElementById('editId').value;
    const u = document.getElementById('editUsername').value.trim();
    const p = document.getElementById('editPassword').value;
    if (!u) return showToast('Username wajib!', 'error');
    if (p && p.length < 6) return showToast('Password minimal 6 karakter!', 'error');

    const body = {
        username: u,
        nama_lengkap: document.getElementById('editNama').value,
        role: document.getElementById('editRole').value
    };
    if (p) body.password = p;

    const res = await fetch(API + '?id=' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    const d = await res.json();
    if (d.status === 'success') { showToast('User diperbarui!'); setTimeout(() => location.reload(), 900); }
    else showToast(d.message || 'Gagal', 'error');
}

async function hapus(id, username) {
    if (id == 1) return showToast('Admin utama (ID=1) tidak dapat dihapus!', 'error');
    if (!confirm(`Hapus user "${username}"?`)) return;
    const res = await fetch(API + '?id=' + id, { method: 'DELETE' });
    const d = await res.json();
    if (d.status === 'success') { showToast('User dihapus!'); setTimeout(() => location.reload(), 900); }
    else showToast(d.message || 'Gagal', 'error');
}
</script>
JS; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
