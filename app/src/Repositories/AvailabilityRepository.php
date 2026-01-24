<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class AvailabilityRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    /** @return array{start_time:string,end_time:string}|null */
    public function findWindowFor(int $hairdresserId, int $dayOfWeek): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT start_time, end_time
             FROM availability
             WHERE hairdresser_id = :hid AND day_of_week = :dow
             LIMIT 1'
        );
        $stmt->execute(['hid' => $hairdresserId, 'dow' => $dayOfWeek]);

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
