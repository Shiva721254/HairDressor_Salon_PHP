<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class AppointmentRepository implements AppointmentRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    private function fetchServiceDuration(int $serviceId): int
    {
        $stmt = $this->pdo->prepare('SELECT duration_minutes FROM services WHERE id = :sid LIMIT 1');
        $stmt->execute(['sid' => $serviceId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    private function overlaps(\DateTimeImmutable $startA, \DateTimeImmutable $endA, \DateTimeImmutable $startB, \DateTimeImmutable $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    /** @return array<int, array{start_time:string,end_time:string}> */
    private function blockedWindowsForDate(int $hairdresserId, string $dateYmd): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT
                    TIME_FORMAT(start_time, '%H:%i') AS start_time,
                    TIME_FORMAT(end_time, '%H:%i') AS end_time
                 FROM unavailability_slots
                 WHERE hairdresser_id = :hid AND slot_date = :d"
            );
            $stmt->execute(['hid' => $hairdresserId, 'd' => $dateYmd]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            // Migration may not be applied yet; keep slot API operational.
            return [];
        }
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
    public function bookingsForDate(int $hairdresserId, string $dateYmd, ?int $excludeAppointmentId = null): array
    {
        $sql = 'SELECT
                a.id AS id,
                TIME_FORMAT(a.appointment_time, "%H:%i") AS start_time,
                s.duration_minutes AS duration_minutes
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.hairdresser_id = :hid
               AND a.appointment_date = :d
               AND (a.status IS NULL OR a.status <> "cancelled")';

        $params = [
            'hid' => $hairdresserId,
            'd'   => $dateYmd,
        ];

        if ($excludeAppointmentId !== null) {
            $sql .= ' AND a.id <> :exclude_id';
            $params['exclude_id'] = $excludeAppointmentId;
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

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

    /**
     * Get appointment list with joined details.
     * If $userId is provided, results are restricted to that user (client view).
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithDetails(
        ?string $filter = null,
        ?int $userId = null,
        ?int $hairdresserId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array
    {
        $filter = $filter ? strtolower(trim($filter)) : 'all';

        $whereParts = [];
        $params = [];
        $orderBy = "ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC";

        // Optional scope: only appointments of a specific user (client view)
        if ($userId !== null) {
            $whereParts[] = "a.user_id = :uid";
            $params['uid'] = $userId;
        }

        if ($hairdresserId !== null) {
            $whereParts[] = "a.hairdresser_id = :hid";
            $params['hid'] = $hairdresserId;
        }

        if ($dateFrom !== null) {
            $whereParts[] = "a.appointment_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== null) {
            $whereParts[] = "a.appointment_date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        switch ($filter) {
            case 'cancelled':
                $whereParts[] = "a.status = :status";
                $params['status'] = 'cancelled';
                $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
                break;

            case 'completed':
                $whereParts[] = "a.status = :status";
                $params['status'] = 'completed';
                $orderBy = "ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
                break;

            case 'all':
                // no extra filters
                break;

            case 'upcoming':
            default:
                $whereParts[] = "a.status = :status";
                $params['status'] = 'booked';
                $whereParts[] = "(a.appointment_date > CURDATE()
                    OR (a.appointment_date = CURDATE() AND a.appointment_time >= CURTIME()))";
                $orderBy = "ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.id ASC";
                break;
        }

        $where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

        $sql = "
            SELECT
                a.id,
                a.user_id,
                a.hairdresser_id,
                a.service_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                h.name AS hairdresser_name,
                s.name AS service_name,
                s.duration_minutes AS duration_minutes,
                s.price AS price,
                u.name AS user_name,
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findWithDetails(int $id): ?array
    {
        $sql = "
            SELECT 
                a.id,
                a.user_id,
                a.appointment_date,
                a.appointment_time,
                a.status,
                hd.name AS hairdresser_name,
                s.name  AS service_name,
                s.duration_minutes,
                s.price,
                u.name AS user_name,
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

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function cancel(int $id): bool
    {
        $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function complete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE appointments
             SET status = 'completed'
             WHERE id = :id AND status = 'booked'"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateDetails(
        int $id,
        int $hairdresserId,
        int $serviceId,
        int $userId,
        string $dateYmd,
        string $timeHi,
        string $status
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE appointments
             SET hairdresser_id = :hid,
                 service_id = :sid,
                 user_id = :uid,
                 appointment_date = :d,
                 appointment_time = :t,
                 status = :status
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'hid' => $hairdresserId,
            'sid' => $serviceId,
            'uid' => $userId,
            'd' => $dateYmd,
            't' => $timeHi . ':00',
            'status' => $status,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM appointments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Returns available time slots for a hairdresser on a given date, taking into account:
     * - availability windows
     * - service duration
     * - already booked appointments (non-cancelled)
     *
     * @return array<int, string> Example: ["10:00","10:30","11:00"]
     */
    public function getAvailableSlots(int $hairdresserId, int $serviceId, string $dateYmd): array
    {
        $duration = $this->fetchServiceDuration($serviceId);

        if ($duration <= 0) {
            return [];
        }

        $dateObj = new \DateTimeImmutable($dateYmd);
        $dayOfWeekIso = (int)$dateObj->format('N'); // 1..7

        $stmt = $this->pdo->prepare('
            SELECT start_time, end_time
            FROM availability
            WHERE hairdresser_id = :hid
              AND day_of_week = :dow
            ORDER BY start_time ASC
        ');
        $stmt->execute(['hid' => $hairdresserId, 'dow' => $dayOfWeekIso]);
        $windows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$windows) {
            return [];
        }

        $bookings = $this->bookingsForDate($hairdresserId, $dateYmd);

        $blockedWindows = $this->blockedWindowsForDate($hairdresserId, $dateYmd);

        $stepMinutes = 30;
        $slots = [];

        foreach ($windows as $w) {
            $start = new \DateTimeImmutable($dateYmd . ' ' . $w['start_time']);
            $end   = new \DateTimeImmutable($dateYmd . ' ' . $w['end_time']);

            $businessStart = new \DateTimeImmutable($dateYmd . ' 08:00:00');
            $businessEnd = new \DateTimeImmutable($dateYmd . ' 17:00:00');

            if ($start < $businessStart) {
                $start = $businessStart;
            }
            if ($end > $businessEnd) {
                $end = $businessEnd;
            }

            if ($start >= $end) {
                continue;
            }

            for ($t = $start; $t < $end; $t = $t->modify("+{$stepMinutes} minutes")) {
                $slotStart = $t;
                $slotEnd   = $t->modify("+{$duration} minutes");

                if ($slotEnd > $end) {
                    break;
                }

                $conflict = false;

                foreach ($bookings as $b) {
                    $bStart = new \DateTimeImmutable($dateYmd . ' ' . $b['start_time']);
                    $bEnd   = $bStart->modify('+' . (int)$b['duration_minutes'] . ' minutes');

                    if ($this->overlaps($slotStart, $slotEnd, $bStart, $bEnd)) {
                        $conflict = true;
                        break;
                    }
                }

                if (!$conflict) {
                    foreach ($blockedWindows as $blocked) {
                        $blockedStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . (string)$blocked['start_time']);
                        $blockedEnd = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . (string)$blocked['end_time']);

                        if ($blockedStart === false || $blockedEnd === false) {
                            continue;
                        }

                        if ($this->overlaps($slotStart, $slotEnd, $blockedStart, $blockedEnd)) {
                            $conflict = true;
                            break;
                        }
                    }
                }

                if (!$conflict) {
                    $slots[] = $slotStart->format('H:i');
                }
            }
        }

        $slots = array_values(array_unique($slots));
        sort($slots);

        return $slots;
    }

}
