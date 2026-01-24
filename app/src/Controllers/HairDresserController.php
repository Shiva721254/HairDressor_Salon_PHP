<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\HairdresserRepository;

final class HairdresserController extends Controller
{
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
}
