<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AppointmentRepositoryInterface;

final class AvailabilityService
{
    public function __construct(private AppointmentRepositoryInterface $appointments)
    {
    }

    /** @return array<int, string> */
    public function availableSlots(int $hairdresserId, int $serviceId, string $dateYmd): array
    {
        return $this->appointments->getAvailableSlots($hairdresserId, $serviceId, $dateYmd);
    }
}
