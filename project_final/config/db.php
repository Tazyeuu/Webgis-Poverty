<?php
/**
 * Singleton PDO connection.
 *
 * Coolify: set DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, and DB_PASSWORD
 * from the database service credentials.
 */
class Database {
    private static $conn = null;

    private static function env(string $key, string $default = ''): string {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }

    public static function getConnection() {
        if (self::$conn === null) {
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
        }
        return self::$conn;
    }
}
