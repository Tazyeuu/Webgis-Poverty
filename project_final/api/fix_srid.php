<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo = Database::getConnection();
    $tables = ['spbu', 'rumah_ibadah', 'jalan', 'kavling', 'kawasan_kumuh', 'warga_miskin'];
    foreach ($tables as $t) {
        $pdo->exec("UPDATE $t SET geom = ST_SRID(geom, 4326) WHERE ST_SRID(geom) != 4326");
    }
    echo "SRID FIXED";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
