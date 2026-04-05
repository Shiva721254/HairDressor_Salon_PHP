<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class AvailabilityRepository implements AvailabilityRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    /**
     * Normalizes time input to 'HH:MM:SS' (accepts 'HH:MM' or 'HH:MM:SS').
     */
    private function normalizeTime(string $time): string
    {
        $time = trim($time);

        // If already has seconds, validate H:i:s
        $dt = \DateTimeImmutable::createFromFormat('H:i:s', $time);
        if ($dt !== false && $dt->format('H:i:s') === $time) {
            return $time;
        }

        // Otherwise validate H:i and append ':00'
        $dt2 = \DateTimeImmutable::createFromFormat('H:i', $time);
        if ($dt2 !== false && $dt2->format('H:i') === $time) {
            return $time . ':00';
        }

        // Invalid -> return a safe default (controller should validate anyway)
        return '00:00:00';
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

        $stmt->execute([
            'hid' => $hairdresserId,
            'dow' => $dayOfWeek,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function allWithHairdresserNames(): array
    {
        $sql = "
            SELECT
                a.id,
                a.hairdresser_id,
                h.name AS hairdresser_name,
                a.day_of_week,
                TIME_FORMAT(a.start_time, '%H:%i') AS start_time,
                TIME_FORMAT(a.end_time, '%H:%i') AS end_time
            FROM availability a
            JOIN hairdressers h ON h.id = a.hairdresser_id
            ORDER BY h.name ASC, a.day_of_week ASC, a.start_time ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByIdWithHairdresserName(int $id): ?array
    {
        $sql = "
            SELECT
                a.id,
                a.hairdresser_id,
                h.name AS hairdresser_name,
                a.day_of_week,
                TIME_FORMAT(a.start_time, '%H:%i') AS start_time,
                TIME_FORMAT(a.end_time, '%H:%i') AS end_time
            FROM availability a
            JOIN hairdressers h ON h.id = a.hairdresser_id
            WHERE a.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): int
    {
        $start = $this->normalizeTime($startHi);
        $end   = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time)
             VALUES (:hid, :dow, :start, :end)'
        );

        $stmt->execute([
            'hid'   => $hairdresserId,
            'dow'   => $dayOfWeek,
            'start' => $start,
            'end'   => $end,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): bool
    {
        $start = $this->normalizeTime($startHi);
        $end   = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'UPDATE availability
             SET hairdresser_id = :hid,
                 day_of_week = :dow,
                 start_time = :start,
                 end_time = :end
             WHERE id = :id'
        );

        $stmt->execute([
            'id'    => $id,
            'hid'   => $hairdresserId,
            'dow'   => $dayOfWeek,
            'start' => $start,
            'end'   => $end,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM availability WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Prevent overlapping windows for same hairdresser + day.
     * If $ignoreId is provided, that row is excluded (useful for update).
     */
    public function overlapsWindow(
        int $hairdresserId,
        int $dayOfWeek,
        string $startHi,
        string $endHi,
        ?int $ignoreId = null
    ): bool {
        $start = $this->normalizeTime($startHi);
        $end   = $this->normalizeTime($endHi);

        $sql = "
            SELECT 1
            FROM availability
            WHERE hairdresser_id = :hid
              AND day_of_week = :dow
              AND NOT (end_time <= :start OR start_time >= :end)
        ";

        $params = [
            'hid'   => $hairdresserId,
            'dow'   => $dayOfWeek,
            'start' => $start,
            'end'   => $end,
        ];

        if ($ignoreId !== null) {
            $sql .= " AND id <> :ignoreId";
            $params['ignoreId'] = $ignoreId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Returns distinct working weekdays for a given hairdresser.
     * Values follow PHP/JS convention: 0=Sunday ... 6=Saturday.
     *
     * @return array<int, int> Example: [1,2,3,4,5]
     */
    public function workingDaysForHairdresser(int $hairdresserId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT day_of_week
             FROM availability
             WHERE hairdresser_id = :hid
             ORDER BY day_of_week ASC'
        );
        $stmt->execute(['hid' => $hairdresserId]);

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_map('intval', $rows);
    }

    public function allWeeklyForHairdresser(int $hairdresserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                day_of_week,
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time
             FROM availability
             WHERE hairdresser_id = :hid
             ORDER BY day_of_week ASC, start_time ASC"
        );
        $stmt->execute(['hid' => $hairdresserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findWeeklyForHairdresserDay(int $hairdresserId, int $dayOfWeek): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                day_of_week,
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time
             FROM availability
             WHERE hairdresser_id = :hid AND day_of_week = :dow
             ORDER BY start_time ASC, id ASC
             LIMIT 1"
        );
        $stmt->execute(['hid' => $hairdresserId, 'dow' => $dayOfWeek]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findWeeklyForHairdresserById(int $id, int $hairdresserId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                day_of_week,
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time
             FROM availability
             WHERE id = :id AND hairdresser_id = :hid
             LIMIT 1"
        );
        $stmt->execute(['id' => $id, 'hid' => $hairdresserId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function updateForHairdresser(int $id, int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): bool
    {
        $start = $this->normalizeTime($startHi);
        $end   = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'UPDATE availability
             SET day_of_week = :dow,
                 start_time = :start,
                 end_time = :end
             WHERE id = :id AND hairdresser_id = :hid'
        );

        $stmt->execute([
            'id' => $id,
            'hid' => $hairdresserId,
            'dow' => $dayOfWeek,
            'start' => $start,
            'end' => $end,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteForHairdresser(int $id, int $hairdresserId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM availability WHERE id = :id AND hairdresser_id = :hid');
        $stmt->execute(['id' => $id, 'hid' => $hairdresserId]);
        return $stmt->rowCount() > 0;
    }

    public function allBlockedForHairdresser(int $hairdresserId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                slot_date,
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time,
                note
             FROM unavailability_slots
             WHERE hairdresser_id = :hid
             ORDER BY slot_date ASC, start_time ASC"
        );
        $stmt->execute(['hid' => $hairdresserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createBlockedSlot(int $hairdresserId, string $dateYmd, string $startHi, string $endHi, ?string $note = null): int
    {
        $start = $this->normalizeTime($startHi);
        $end = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'INSERT INTO unavailability_slots (hairdresser_id, slot_date, start_time, end_time, note)
             VALUES (:hid, :d, :start, :end, :note)'
        );
        $stmt->execute([
            'hid' => $hairdresserId,
            'd' => $dateYmd,
            'start' => $start,
            'end' => $end,
            'note' => $note,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function overlapsBlockedSlot(int $hairdresserId, string $dateYmd, string $startHi, string $endHi): bool
    {
        $start = $this->normalizeTime($startHi);
        $end = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM unavailability_slots
             WHERE hairdresser_id = :hid
               AND slot_date = :d
               AND NOT (end_time <= :start OR start_time >= :end)
             LIMIT 1'
        );

        $stmt->execute([
            'hid' => $hairdresserId,
            'd' => $dateYmd,
            'start' => $start,
            'end' => $end,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function findBlockedForHairdresserById(int $id, int $hairdresserId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                slot_date,
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time,
                note
             FROM unavailability_slots
             WHERE id = :id AND hairdresser_id = :hid
             LIMIT 1"
        );
        $stmt->execute(['id' => $id, 'hid' => $hairdresserId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function updateBlockedSlotForHairdresser(int $id, int $hairdresserId, string $dateYmd, string $startHi, string $endHi, ?string $note = null): bool
    {
        $start = $this->normalizeTime($startHi);
        $end = $this->normalizeTime($endHi);

        $stmt = $this->pdo->prepare(
            'UPDATE unavailability_slots
             SET slot_date = :d,
                 start_time = :start,
                 end_time = :end,
                 note = :note
             WHERE id = :id AND hairdresser_id = :hid'
        );

        $stmt->execute([
            'id' => $id,
            'hid' => $hairdresserId,
            'd' => $dateYmd,
            'start' => $start,
            'end' => $end,
            'note' => $note,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deleteBlockedSlot(int $id, int $hairdresserId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM unavailability_slots WHERE id = :id AND hairdresser_id = :hid');
        $stmt->execute(['id' => $id, 'hid' => $hairdresserId]);
        return $stmt->rowCount() > 0;
    }

    public function blockedWindowsForDate(int $hairdresserId, string $dateYmd): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                TIME_FORMAT(start_time, '%H:%i') AS start_time,
                TIME_FORMAT(end_time, '%H:%i') AS end_time
             FROM unavailability_slots
             WHERE hairdresser_id = :hid
               AND slot_date = :d
             ORDER BY start_time ASC"
        );
        $stmt->execute(['hid' => $hairdresserId, 'd' => $dateYmd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
