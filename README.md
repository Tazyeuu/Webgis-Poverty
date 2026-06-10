# WebGIS Poverty Map

Aplikasi WebGIS PHP + MySQL untuk pemetaan data kemiskinan, fasilitas, laporan warga, dan analisis blank spot bantuan.

## Stack

- PHP 8.2 Apache
- MySQL/MariaDB
- Leaflet
- PDO MySQL

## Deploy Coolify

1. Buat database MySQL/MariaDB di Coolify.
2. Import `database/webgis_db.sql` ke database tersebut.
3. Buat aplikasi baru dari repo ini.
4. Pilih build menggunakan `Dockerfile` di root repository.
5. Set environment variable:

```env
APP_BASE_PATH=
SESSION_SECURE=true
CORS_ALLOWED_ORIGIN=
DB_HOST=nama-service-database
DB_PORT=3306
DB_DATABASE=webgis_db
DB_USERNAME=user_database
DB_PASSWORD=password_database
```

Root aplikasi menampilkan halaman pemilih progres dari `index.html`. Versi final berjalan di `/project_final`, jadi gunakan `APP_BASE_PATH=/project_final` untuk deploy Coolify ini.

`CORS_ALLOWED_ORIGIN` boleh dikosongkan untuk same-origin deployment. Isi dengan domain aplikasi jika API memang perlu diakses dari origin lain.

## Akun Awal

Setelah import SQL:

- Admin: `admin / admin123`
- Pengguna: `pengguna / user123`

Ganti password akun demo setelah deploy jika aplikasi dibuka publik.

## Struktur Penting

- `index.html` - halaman awal untuk memilih progres.
- `01/`, `02/`, `03/`, `final/` - progres pertemuan.
- `project_final/` - aplikasi final yang berjalan di `/project_final`.
- `database/webgis_db.sql` - struktur dan seed database lengkap.
- `Dockerfile` - image PHP Apache untuk Coolify.
- `.env.example` - contoh environment variable deploy.

## Local XAMPP

Untuk menjalankan dari XAMPP seperti struktur lama, set base path:

```env
APP_BASE_PATH=/project/project_final
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webgis_db
DB_USERNAME=root
DB_PASSWORD=
```

Import `database/webgis_db.sql`, lalu buka `/project/project_final/login.php`.
