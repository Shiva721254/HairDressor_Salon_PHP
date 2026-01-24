<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ServiceRepository;

final class ServiceController extends Controller
{
    public function index(): string
    {
        $repo = new ServiceRepository();
        $services = $repo->all();

        return $this->render('services/index', [
            'title' => 'Services',
            'services' => $services,
        ]);
    }
}
