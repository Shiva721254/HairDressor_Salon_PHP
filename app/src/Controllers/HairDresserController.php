<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\HairdresserRepositoryInterface;
use App\Repositories\AvailabilityRepositoryInterface;

final class HairdresserController extends Controller
{
    public function __construct(
        private HairdresserRepositoryInterface $hairdressers,
        private AvailabilityRepositoryInterface $availabilityRepository
    ) {
    }

    public function index(): string
    {
        $hairdressers = $this->hairdressers->all();

        return $this->render('hairdressers/index', [
            'title' => 'Hairdressers',
            'hairdressers' => $hairdressers,
        ]);
    }

    public function show(string $id): string
    {
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid hairdresser']);
        }

        $hairdresser = $this->hairdressers->findById($id);
        if ($hairdresser === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $availability = $this->hairdressers->getWeeklyAvailability($id);

        return $this->render('hairdressers/show', [
            'title' => 'Hairdresser Details',
            'hairdresser' => $hairdresser,
            'availability' => $availability,
        ]);
    }

    /**
     * API endpoint: returns weekly working days for a hairdresser.
     * Example: GET /api/hairdressers/1/availability
     * Response: { ok:true, hairdresser_id:1, working_days:[1,2,3,4,5] }
     */
    public function availability(string $id): string
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $hairdresserId = (int)$id;

            if ($hairdresserId <= 0) {
                http_response_code(400);
                return json_encode(
                    ['ok' => false, 'error' => 'Invalid hairdresser id'],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            }

            $days = $this->availabilityRepository->workingDaysForHairdresser($hairdresserId);

            http_response_code(200);
            return json_encode(
                [
                    'ok' => true,
                    'hairdresser_id' => $hairdresserId,
                    'working_days' => $days,
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            return json_encode(
                ['ok' => false, 'error' => 'Internal Server Error'],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }
    }
}
