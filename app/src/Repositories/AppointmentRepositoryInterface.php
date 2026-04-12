<?php
declare(strict_types=1);

namespace App\Repositories;

interface AppointmentRepositoryInterface
{
    public function bookingsForDate(int $hairdresserId, string $dateYmd, ?int $excludeAppointmentId = null): array;

    public function create(
        int $hairdresserId,
        int $serviceId,
        int $userId,
        string $dateYmd,
        string $timeHi,
        string $status = 'booked'
    ): int;

    public function allWithDetails(
        ?string $filter = null,
        ?int $userId = null,
        ?int $hairdresserId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array;

    public function findWithDetails(int $id): ?array;

    public function cancel(int $id): bool;

    public function complete(int $id): bool;

    public function updateDetails(
        int $id,
        int $hairdresserId,
        int $serviceId,
        int $userId,
        string $dateYmd,
        string $timeHi,
        string $status
    ): bool;

    public function deleteById(int $id): bool;

    public function getAvailableSlots(
        int $hairdresserId,
        int $serviceId,
        string $dateYmd,
        ?int $excludeAppointmentId = null
    ): array;
}
