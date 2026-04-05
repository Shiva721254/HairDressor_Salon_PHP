<?php
declare(strict_types=1);

namespace App\Repositories;

interface AvailabilityRepositoryInterface
{
    public function findWindowFor(int $hairdresserId, int $dayOfWeek): ?array;

    public function allWithHairdresserNames(): array;

    public function findByIdWithHairdresserName(int $id): ?array;

    public function create(int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): int;

    public function update(int $id, int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): bool;

    public function delete(int $id): bool;

    public function overlapsWindow(
        int $hairdresserId,
        int $dayOfWeek,
        string $startHi,
        string $endHi,
        ?int $ignoreId = null
    ): bool;

    public function workingDaysForHairdresser(int $hairdresserId): array;

    public function allWeeklyForHairdresser(int $hairdresserId): array;

    public function findWeeklyForHairdresserDay(int $hairdresserId, int $dayOfWeek): ?array;

    public function findWeeklyForHairdresserById(int $id, int $hairdresserId): ?array;

    public function updateForHairdresser(int $id, int $hairdresserId, int $dayOfWeek, string $startHi, string $endHi): bool;

    public function deleteForHairdresser(int $id, int $hairdresserId): bool;

    public function allBlockedForHairdresser(int $hairdresserId): array;

    public function createBlockedSlot(int $hairdresserId, string $dateYmd, string $startHi, string $endHi, ?string $note = null): int;

    public function overlapsBlockedSlot(int $hairdresserId, string $dateYmd, string $startHi, string $endHi): bool;

    public function findBlockedForHairdresserById(int $id, int $hairdresserId): ?array;

    public function updateBlockedSlotForHairdresser(int $id, int $hairdresserId, string $dateYmd, string $startHi, string $endHi, ?string $note = null): bool;

    public function deleteBlockedSlot(int $id, int $hairdresserId): bool;

    public function blockedWindowsForDate(int $hairdresserId, string $dateYmd): array;
}