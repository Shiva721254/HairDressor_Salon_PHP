<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;

final class BookingRepository
{
    /**
     * Returns booked times for a hairdresser on a given date.
     * Each item: ['appointment_time' => '10:00:00']
     */
    public function bookedTimes(int $hairdresserId, string $date): array
    {
        $sql = "SELECT appointment_time
                FROM appointments
                WHERE hairdresser_id = :hid
                  AND appointment_date = :adate
                  AND status = 'booked'";

        $stmt = Db::pdo()->prepare($sql);
        $stmt->execute(['hid' => $hairdresserId, 'adate' => $date]);

        return $stmt->fetchAll();
    }
}
