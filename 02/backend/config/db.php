<?php
class Database {
    private static $conn = null;

    private static function env(string $key, string $default = ''): string {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    public static function getConnection() {
        if (self::$conn === null) {
            try {
                $host = self::env('DB_HOST', '127.0.0.1');
                $port = self::env('DB_PORT', '3306');
                $dbName = self::env('DB_DATABASE', 'webgis_db');
                $username = self::env('DB_USERNAME', 'root');
                $password = self::env('DB_PASSWORD', '');

                self::$conn = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
                    $username,
                    $password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
        }
        return self::$conn;
    }
}
