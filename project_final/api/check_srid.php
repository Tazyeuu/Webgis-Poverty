<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT id, ST_SRID(geom) as srid FROM warga_miskin LIMIT 5");
    echo "warga_miskin SRIDs: \n";
    while ($row = $stmt->fetch()) echo $row['id'] . " => " . $row['srid'] . "\n";
    
    $stmt = $pdo->query("SELECT id, ST_SRID(geom) as srid FROM rumah_ibadah LIMIT 5");
    echo "rumah_ibadah SRIDs: \n";
    while ($row = $stmt->fetch()) echo $row['id'] . " => " . $row['srid'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
