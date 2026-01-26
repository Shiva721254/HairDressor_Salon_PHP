<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\HairdresserRepository;
use App\Repositories\AvailabilityRepository;

final class HairdresserController extends Controller
{
    private AvailabilityRepository $availabilityRepository;

    public function __construct()
    {
        $this->availabilityRepository = new AvailabilityRepository();
    }

    public function index(): string
    {
        $repo = new HairdresserRepository();
        $hairdressers = $repo->all();

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

        $repo = new HairdresserRepository();

        $hairdresser = $repo->findById($id);
        if ($hairdresser === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $availability = $repo->getWeeklyAvailability($id);

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
