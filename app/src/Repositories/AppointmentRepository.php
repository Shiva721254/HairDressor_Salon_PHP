<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class AppointmentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    public function existsSlot(int $hairdresserId, string $dateYmd, string $timeHi): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM appointments
             WHERE hairdresser_id = :hid
               AND appointment_date = :d
               AND appointment_time = :t
             LIMIT 1'
        );

        $stmt->execute([
            'hid' => $hairdresserId,
            'd'   => $dateYmd,
            't'   => $timeHi . ':00',
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Returns bookings for a hairdresser on a date with duration.
     * @return array<int, array{start_time:string,duration_minutes:int}>
     */
    public function bookingsForDate(int $hairdresserId, string $dateYmd): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                TIME_FORMAT(a.appointment_time, "%H:%i") AS start_time,
                s.duration_minutes AS duration_minutes
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.hairdresser_id = :hid
               AND a.appointment_date = :d
               AND (a.status IS NULL OR a.status <> "cancelled")'
        );

        $stmt->execute([
            'hid' => $hairdresserId,
            'd'   => $dateYmd,
        ]);

        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['duration_minutes'] = (int)$r['duration_minutes'];
        }
        return $rows;
    }

    public function create(
        int $hairdresserId,
        int $serviceId,
        int $userId,
        string $dateYmd,
        string $timeHi,
        string $status = 'booked'
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO appointments (hairdresser_id, service_id, user_id, appointment_date, appointment_time, status)
             VALUES (:hid, :sid, :uid, :d, :t, :status)'
        );

        $stmt->execute([
            'hid'    => $hairdresserId,
            'sid'    => $serviceId,
            'uid'    => $userId,
            'd'      => $dateYmd,
            't'      => $timeHi . ':00',
            'status' => $status,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function allWithDetails(?string $filter = null): array
    {
        $filter = $filter ? strtolower(trim($filter)) : 'all';

        $where = '';
        $params = [];

        switch ($filter) {
            case 'cancelled':
                $where = "WHERE a.status = :status";
                $params[':status'] = 'cancelled';
                $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
                break;

            case 'completed':
                $where = "WHERE a.status = :status";
                $params[':status'] = 'completed';
                $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
                break;

            case 'all':
                $orderBy = "ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC";
                break;

            case 'upcoming':
            default:
                // Upcoming: booked appointments from today onward
                $where = "WHERE a.status = :status AND (a.appointment_date > CURDATE()
                OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME()))";
                $params[':status'] = 'booked';
                $orderBy = "ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC";
                break;
        }

        $sql = "
        SELECT
            a.id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            h.name AS hairdresser_name,
            s.name AS service_name,
            s.duration_minutes AS service_duration_minutes,
            s.price AS service_price,
            u.email AS user_email,
            u.role AS user_role
        FROM appointments a
        JOIN hairdressers h ON h.id = a.hairdresser_id
        JOIN services s ON s.id = a.service_id
        JOIN users u ON u.id = a.user_id
        $where
        $orderBy
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }



    public function findWithDetails(int $id): ?array
    {
        $sql = "
            SELECT 
                a.id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                hd.name AS hairdresser_name,
                s.name  AS service_name,
                s.duration_minutes,
                s.price,
                u.email AS user_email,
                u.role  AS user_role
            FROM appointments a
            JOIN hairdressers hd ON hd.id = a.hairdresser_id
            JOIN services s      ON s.id  = a.service_id
            LEFT JOIN users u    ON u.id  = a.user_id
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function cancel(int $id): bool
    {
        $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
