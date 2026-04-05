<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Database Connection Singleton
 * 
 * Provides a single, lazy-loaded PDO connection instance.
 * Connection is established on first use and reused for all subsequent requests.
 * 
 * Environment variables (from .env):
 *   - DB_HOST: Database server hostname (default: mysql)
 *   - DB_PORT: Database server port (default: 3306)
 *   - DB_NAME: Database name (default: developmentdb)
 *   - DB_USER: Database user (default: developer)
 *   - DB_PASS: Database password (default: secret123)
 */
final class Db
{
    /** @var ?PDO Singleton PDO instance */
    private static ?PDO $pdo = null;

    /**
     * Get the database PDO connection
     * 
     * Creates a new connection on first call and returns the same instance
     * on subsequent calls (singleton pattern).
     * 
     * @return PDO
     * @throws \PDOException If connection fails
     */
    public static function pdo(): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        $host = Env::get('DB_HOST', 'mysql');
        $port = Env::get('DB_PORT', '3306');
            // Load database credentials from environment variables
            // Falls back to defaults if not set (for Docker development)
            $db   = Env::get('DB_NAME', 'developmentdb');
            $user = Env::get('DB_USER', 'developer');
            $pass = Env::get('DB_PASS', 'secret123');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pdo;
    }
}
