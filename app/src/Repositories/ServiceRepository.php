<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class ServiceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, price FROM services ORDER BY name');
        return $stmt->fetchAll();
    }

public function findById(int $id): ?array
{
    $stmt = $this->pdo->prepare('SELECT id, name, duration_minutes, price FROM services WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}







}
