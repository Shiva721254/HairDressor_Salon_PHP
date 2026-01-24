<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        // Match docker-compose service names + credentials
        $host = Env::get('DB_HOST', 'mysql');
        $port = Env::get('DB_PORT', '3306');
        $db   = Env::get('DB_DATABASE', 'developmentdb');
        $user = Env::get('DB_USERNAME', 'developer');
        $pass = Env::get('DB_PASSWORD', 'secret123');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$pdo;
    }
}
