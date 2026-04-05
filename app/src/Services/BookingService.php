<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\ServiceRepositoryInterface;

final class BookingService
{
    public function __construct(
        private AppointmentRepositoryInterface $appointments,
        private ServiceRepositoryInterface $services
    ) {
    }

    public function overlapsExisting(
        int $hairdresserId,
        int $serviceId,
        string $dateYmd,
        string $timeHi,
        ?int $excludeAppointmentId = null
    ): bool {
        $service = $this->services->findById($serviceId);
        if ($service === null) {
            return true;
        }

        $duration = max(1, (int)($service['duration_minutes'] ?? 0));

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $timeHi);
        if ($start === false) {
            return true;
        }

        $end = $start->modify("+{$duration} minutes");

        $bookings = $this->appointments->bookingsForDate($hairdresserId, $dateYmd, $excludeAppointmentId);

        foreach ($bookings as $b) {
            $bStart = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $dateYmd . ' ' . (string)($b['start_time'] ?? '')
            );
            if ($bStart === false) {
                continue;
            }

            $bDur = max(1, (int)($b['duration_minutes'] ?? 0));
            $bEnd = $bStart->modify("+{$bDur} minutes");

            if ($start < $bEnd && $end > $bStart) {
                return true;
            }
        }

        return false;
    }
}
