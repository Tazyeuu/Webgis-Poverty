<?php
require_once __DIR__ . '/config/session.php';
startAppSession();

if (isset($_SESSION['user_id'])) {
    header($_SESSION['role'] === 'admin' ? 'Location: admin/index.php' : 'Location: user/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/config/db.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            header($user['role'] === 'admin' ? 'Location: admin/index.php' : 'Location: user/index.php');
            exit;
        }

        $error = 'Username atau password salah. Silakan coba lagi.';
    } else {
        $error = 'Username dan password wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WebGIS Smart City</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">

    <div class="auth-center">
        <div class="auth-form-wrap">
            <h2 class="auth-form-title">Selamat Datang</h2>
            <p class="auth-form-subtitle">Masuk ke sistem WebGIS Smart City Pontianak.</p>

            <div class="role-selector">
                <label class="role-card">
                    <input type="radio" name="role_display" value="admin" checked>
                    <div class="role-card-label">
                        <div class="role-card-icon"><i class="fas fa-user-shield"></i></div>
                        <div class="role-card-name">Admin</div>
                        <div class="role-card-desc">Kelola semua data</div>
                    </div>
                </label>
                <label class="role-card">
                    <input type="radio" name="role_display" value="user">
                    <div class="role-card-label">
                        <div class="role-card-icon"><i class="fas fa-user"></i></div>
                        <div class="role-card-name">Pengguna</div>
                        <div class="role-card-desc">Lihat & analisis peta</div>
                    </div>
                </label>
            </div>

            <?php if ($error): ?>
            <div class="auth-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; padding:12px; margin-bottom:16px; font-size:0.85rem; color:#1E3A8A;">
                <strong><i class="fas fa-info-circle"></i> Akun Testing:</strong><br>
                Admin: <code>admin</code> / <code>admin123</code><br>
                User: <code>pengguna</code> / <code>user123</code>
            </div>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-control"
                               placeholder="Masukkan username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control"
                               placeholder="Masukkan password"
                               required autocomplete="current-password"
                               id="passwordInput">
                        <button type="button" class="input-icon" style="right:12px;left:auto;cursor:pointer;background:none;border:none;color:var(--text-muted);" onclick="togglePass()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk ke Sistem
                </button>
            </form>
        </div>
    </div>

    <script>
    function togglePass() {
        const inp = document.getElementById('passwordInput');
        const ico = document.getElementById('eyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            ico.className = 'fas fa-eye-slash';
        } else {
            inp.type = 'password';
            ico.className = 'fas fa-eye';
        }
    }
    </script>
</body>
</html>
