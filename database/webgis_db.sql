-- ============================================================
-- WebGIS Pontianak - Database Lengkap
-- ============================================================
-- Import file ini jika ingin membuat ulang database dari awal.
-- Perhatian: tabel lama dengan nama yang sama akan dihapus.

-- Database selected via connection string

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ulasan_fasilitas;
DROP TABLE IF EXISTS laporan_warga;
DROP TABLE IF EXISTS warga_miskin;
DROP TABLE IF EXISTS kawasan_kumuh;
DROP TABLE IF EXISTS kavling;
DROP TABLE IF EXISTS jalan;
DROP TABLE IF EXISTS rumah_ibadah;
DROP TABLE IF EXISTS spbu;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. Users
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    nama_lengkap VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    );

-- ============================================================
-- 2. Data Spasial
-- ============================================================
CREATE TABLE spbu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    buka_24_jam TINYINT(1) NOT NULL DEFAULT 0,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_spbu_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO spbu (nama, deskripsi, buka_24_jam, geom) VALUES
    (
        'SPBU Bundaran Digulis Untan',
        'SPBU terdekat dari kampus',
        1,
        ST_GeomFromText('POINT(109.3440 -0.0565)')
    ),
    (
        'SPBU Ahmad Yani',
        'Dekat Mega Mall (24 Jam)',
        1,
        ST_GeomFromText('POINT(109.3360 -0.0450)')
    ),
    (
        'SPBU Imam Bonjol',
        'SPBU pinggir kota',
        0,
        ST_GeomFromText('POINT(109.3410 -0.0380)')
    ),
    (
        'SPBU Kota Baru',
        'Jl. Sultan Abdurrahman',
        0,
        ST_GeomFromText('POINT(109.3245 -0.0487)')
    );

CREATE TABLE rumah_ibadah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    agama VARCHAR(50) NOT NULL,
    radius_bantuan_meter INT NOT NULL DEFAULT 1000,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_rumah_ibadah_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO rumah_ibadah (nama, agama, radius_bantuan_meter, geom) VALUES
    (
        'Masjid Raya Mujahidin',
        'Islam',
        1000,
        ST_GeomFromText('POINT(109.3356 -0.0454)')
    ),
    (
        'Gereja Katedral St. Yosef',
        'Katolik',
        1000,
        ST_GeomFromText('POINT(109.3368 -0.0298)')
    ),
    (
        'Vihara Bodhisatva Karaniya Metta',
        'Buddha',
        1000,
        ST_GeomFromText('POINT(109.3468 -0.0233)')
    ),
    (
        'Masjid Jami Keraton Kadriyah',
        'Islam',
        1000,
        ST_GeomFromText('POINT(109.3523 -0.0229)')
    );

CREATE TABLE jalan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jenis_jalan VARCHAR(50) DEFAULT NULL,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_jalan_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO jalan (nama, jenis_jalan, geom) VALUES
    (
        'Jalan Ahmad Yani',
        'Arteri Primer',
        ST_GeomFromText('LINESTRING(109.3448 -0.0583, 109.3360 -0.0450, 109.3310 -0.0320)')
    ),
    (
        'Jalan Gajah Mada',
        'Kolektor',
        ST_GeomFromText('LINESTRING(109.3310 -0.0320, 109.3410 -0.0290, 109.3460 -0.0250)')
    ),
    (
        'Jalan Imam Bonjol',
        'Lokal',
        ST_GeomFromText('LINESTRING(109.3360 -0.0450, 109.3420 -0.0400, 109.3500 -0.0350)')
    );

CREATE TABLE kavling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pemilik VARCHAR(100) NOT NULL,
    status_kepemilikan VARCHAR(50) NOT NULL,
    luas DECIMAL(10,2) DEFAULT NULL,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_kavling_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kavling (nama_pemilik, status_kepemilikan, luas, geom) VALUES
    (
        'PT. Mega Karya',
        'HGB',
        25000.00,
        ST_GeomFromText('POLYGON((109.333 -0.048, 109.338 -0.048, 109.338 -0.053, 109.333 -0.053, 109.333 -0.048))')
    ),
    (
        'Bapak Sudirman',
        'SHM',
        1500.00,
        ST_GeomFromText('POLYGON((109.340 -0.035, 109.341 -0.035, 109.341 -0.036, 109.340 -0.036, 109.340 -0.035))')
    );

CREATE TABLE kawasan_kumuh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kawasan VARCHAR(100) NOT NULL,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_kawasan_kumuh_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kawasan_kumuh (nama_kawasan, geom) VALUES
    (
        'Kawasan Rawan Bantaran Sungai',
        ST_GeomFromText('POLYGON((109.360 -0.050, 109.365 -0.050, 109.365 -0.055, 109.360 -0.055, 109.360 -0.050))')
    ),
    (
        'Kawasan Padat Parit Tokaya',
        ST_GeomFromText('POLYGON((109.342 -0.038, 109.346 -0.038, 109.346 -0.042, 109.342 -0.042, 109.342 -0.038))')
    );

CREATE TABLE warga_miskin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kk VARCHAR(100) NOT NULL,
    penghasilan DECIMAL(15,2) NOT NULL,
    jumlah_tanggungan INT NOT NULL,
    geom GEOMETRY NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    SPATIAL INDEX idx_warga_miskin_geom (geom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO warga_miskin (nama_kk, penghasilan, jumlah_tanggungan, geom) VALUES
    (
        'Bpk. Budi (Terisolasi)',
        800000.00,
        4,
        ST_GeomFromText('POINT(109.361 -0.051)')
    ),
    (
        'Ibu Siti (Terisolasi)',
        600000.00,
        2,
        ST_GeomFromText('POINT(109.362 -0.052)')
    ),
    (
        'Bpk. Joko (Terisolasi)',
        1000000.00,
        5,
        ST_GeomFromText('POINT(109.363 -0.053)')
    ),
    (
        'Ibu Ani (Terisolasi)',
        700000.00,
        3,
        ST_GeomFromText('POINT(109.364 -0.054)')
    ),
    (
        'Bpk. Hasan',
        1200000.00,
        3,
        ST_GeomFromText('POINT(109.334 -0.050)')
    ),
    (
        'Mbah Warno',
        400000.00,
        1,
        ST_GeomFromText('POINT(109.336 -0.046)')
    ),
    (
        'Pak Junaidi',
        900000.00,
        4,
        ST_GeomFromText('POINT(109.344 -0.040)')
    );

-- ============================================================
-- 3. Data Interaksi Pengguna
-- ============================================================
CREATE TABLE laporan_warga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    deskripsi TEXT NOT NULL,
    geometry POINT NOT NULL,
    status ENUM('menunggu', 'diproses', 'selesai', 'ditolak') NOT NULL DEFAULT 'menunggu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_laporan_user_id (user_id),
    SPATIAL INDEX idx_laporan_geometry (geometry),
    CONSTRAINT fk_laporan_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ulasan_fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fasilitas_tipe ENUM('spbu', 'rumah_ibadah') NOT NULL,
    fasilitas_id INT NOT NULL,
    rating TINYINT NOT NULL,
    komentar TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ulasan_user_id (user_id),
    INDEX idx_ulasan_fasilitas (fasilitas_tipe, fasilitas_id),
    CONSTRAINT fk_ulasan_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_ulasan_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
