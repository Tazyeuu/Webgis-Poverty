-- ============================================================
-- WebGIS Pontianak - Seed Data Spasial
-- ============================================================
-- Koordinat menggunakan urutan longitude, latitude.

USE webgis_db;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE spbu;
TRUNCATE TABLE rumah_ibadah;
TRUNCATE TABLE jalan;
TRUNCATE TABLE kavling;
TRUNCATE TABLE kawasan_kumuh;
TRUNCATE TABLE warga_miskin;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. SPBU
-- ============================================================
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

-- ============================================================
-- 2. Rumah Ibadah
-- ============================================================
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

-- ============================================================
-- 3. Jalan
-- ============================================================
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

-- ============================================================
-- 4. Kavling
-- ============================================================
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

-- ============================================================
-- 5. Kawasan Kumuh
-- ============================================================
INSERT INTO kawasan_kumuh (nama_kawasan, geom) VALUES
    (
        'Kawasan Rawan Bantaran Sungai',
        ST_GeomFromText('POLYGON((109.360 -0.050, 109.365 -0.050, 109.365 -0.055, 109.360 -0.055, 109.360 -0.050))')
    ),
    (
        'Kawasan Padat Parit Tokaya',
        ST_GeomFromText('POLYGON((109.342 -0.038, 109.346 -0.038, 109.346 -0.042, 109.342 -0.042, 109.342 -0.038))')
    );

-- ============================================================
-- 6. Warga Miskin
-- ============================================================
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
