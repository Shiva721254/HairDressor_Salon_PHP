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
        $sql = "SELECT id, name, duration_minutes, price FROM services ORDER BY name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, duration_minutes, price FROM services WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $name, int $durationMinutes, float $price): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO services (name, duration_minutes, price)
             VALUES (:name, :duration, :price)'
        );

        $stmt->execute([
            'name' => $name,
            'duration' => $durationMinutes,
            'price' => $price,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, int $durationMinutes, float $price): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE services
             SET name = :name,
                 duration_minutes = :duration,
                 price = :price
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'duration' => $durationMinutes,
            'price' => $price,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM services WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
