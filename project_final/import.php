<?php
require_once __DIR__ . '/config/app.php';

$host = env('DB_HOST', 'localhost');
$port = env('DB_PORT', 3306);
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');
$db = env('DB_DATABASE', 'webgis_db');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../database/webgis_db.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found at: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into statements and execute
    $pdo->exec($sql);
    
    echo "Database imported successfully to $db at $host!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
