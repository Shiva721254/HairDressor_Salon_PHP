<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\HairdresserRepository;

final class HairdresserController
{
    public function index(): void
    {
        $repo = new HairdresserRepository();
        $hairdressers = $repo->all();

        require __DIR__ . '/../../views/hairdressers/index.php';
    }

    public function show(array $vars): void
    {
        $id = (int)($vars['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Invalid hairdresser id';
            return;
        }

        $repo = new HairdresserRepository();
        $hairdresser = $repo->findById($id);

        if ($hairdresser === null) {
            http_response_code(404);
            echo 'Hairdresser not found';
            return;
        }

        require __DIR__ . '/../../views/hairdressers/show.php';
    }
}
