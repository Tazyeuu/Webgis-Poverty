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
            $dbUrl = self::env('DATABASE_URL');
            if ($dbUrl) {
                $parsed = parse_url($dbUrl);
                $host = $parsed['host'] ?? '127.0.0.1';
                $port = $parsed['port'] ?? '3306';
                $username = $parsed['user'] ?? 'root';
                $password = $parsed['pass'] ?? '';
                $dbName = ltrim($parsed['path'], '/');
            } else {
                $host = self::env('DB_HOST', '127.0.0.1');
                $port = self::env('DB_PORT', '3306');
                $dbName = self::env('DB_DATABASE', 'webgis_db');
                $username = self::env('DB_USERNAME', 'root');
                $password = self::env('DB_PASSWORD', '');
            }

            self::$conn = new PDO(
                "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4",
                $username,
                $password
            );
            self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Disable strict mode for GROUP BY to prevent 1055 errors in Coolify's MariaDB
            self::$conn->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // Auto-initialize database if empty
            $stmt = self::$conn->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                $sqlFile = __DIR__ . '/../../../database/webgis_db.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    self::$conn->exec($sql);
                }
            }
        }
        return self::$conn;
    }
}
