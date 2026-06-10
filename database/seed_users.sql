-- ============================================================
-- WebGIS Pontianak - Seed Users
-- ============================================================
-- Akun demo:
--   admin    / admin123
--   pengguna / user123

USE webgis_db;

INSERT INTO users (username, password, role, nama_lengkap) VALUES
    (
        'admin',
        '$2y$12$oRvX1EtLHM/y8XtwlOy./Oi2UVpnHp98GvXqEuLCFimhr0doKus62',
        'admin',
        'Administrator WebGIS'
    ),
    (
        'pengguna',
        '$2y$12$djJkmhJBRCSCqqpAICIvGuLoVpICrDUS2IVECbZUodgR89VRTqdLW',
        'user',
        'Pengguna Publik'
    )
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    nama_lengkap = VALUES(nama_lengkap);
