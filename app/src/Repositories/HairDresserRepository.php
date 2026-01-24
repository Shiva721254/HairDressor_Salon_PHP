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

    public function all(): array
    {
        $sql = "SELECT id, name FROM hairdressers ORDER BY name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }


    // ✅ ADD IT HERE
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM hairdressers WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
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

    return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
}







}
