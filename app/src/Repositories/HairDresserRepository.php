<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class HairdresserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = "SELECT id, name FROM hairdressers ORDER BY name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, created_at FROM hairdressers WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $name): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO hairdressers (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE hairdressers SET name = :name WHERE id = :id');
        $stmt->execute(['id' => $id, 'name' => $name]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM hairdressers WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** @return array<int, array{day_of_week:int,start_time:string,end_time:string}> */
    public function getWeeklyAvailability(int $hairdresserId): array
    {
        $sql = "
            SELECT day_of_week, start_time, end_time
            FROM availability
            WHERE hairdresser_id = :id
            ORDER BY day_of_week ASC, start_time ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $hairdresserId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
