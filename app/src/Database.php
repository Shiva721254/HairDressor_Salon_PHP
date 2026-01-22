<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

final class Database
{
    public static function pdo(): PDO
    {
        $host = getenv('DB_HOST') ?: 'mysql';
        $port = getenv('DB_PORT') ?: '3306';
        $db   = getenv('DB_NAME') ?: 'developmentdb';
        $user = getenv('DB_USER') ?: 'developer';
        $pass = getenv('DB_PASS') ?: 'secret123';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch as arrays
                PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepares
            ]);
        } catch (PDOException $e) {
            // For dev you can show message; for production you would log only
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), 0, $e);
        }
    }
}
