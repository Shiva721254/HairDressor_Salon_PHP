<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ServiceRepositoryInterface;

final class ServiceController extends Controller
{
    public function __construct(private ServiceRepositoryInterface $services)
    {
    }

    public function index(): string
    {
        $services = $this->services->all();

        return $this->render('services/index', [
            'title' => 'Services',
            'services' => $services,
        ]);
    }
}
