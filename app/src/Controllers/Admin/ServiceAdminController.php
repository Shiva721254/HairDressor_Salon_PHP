<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\ServiceRepositoryInterface;

final class ServiceAdminController extends Controller
{
    public function __construct(private ServiceRepositoryInterface $services)
    {
    }

    private function requireAdmin(): void
    {
        $this->requireRole('admin');
    }

    /** @return array<int, string> */
    private function validateServiceInput(string $name, int $duration, float $price): array
    {
        $errors = [];
        if ($name === '') $errors[] = 'Service name is required.';
        if ($duration < 15) $errors[] = 'Duration must be at least 15 minutes.';
        if ($duration % 15 !== 0) $errors[] = 'Duration must be in 15-minute steps (15, 30, 45, ...).';
        if ($price < 0) $errors[] = 'Price must be 0 or more.';

        return $errors;
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->render('admin/services/index', [
            'title' => 'Admin - Services',
            'services' => $this->services->all(),
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->render('admin/services/create', [
            'title' => 'Add Service',
            'errors' => [],
            'old' => ['name' => '', 'duration_minutes' => '', 'price' => ''],
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        $this->requireCsrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        $errors = $this->validateServiceInput($name, $duration, $price);

        if ($errors) {
            http_response_code(422);
            return $this->render('admin/services/create', [
                'title' => 'Add Service',
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'duration_minutes' => (string)$duration,
                    'price' => (string)$price,
                ],
            ]);
        }

        $this->services->create($name, $duration, $price);

        $this->flash('success', 'Service created.');
        return $this->redirect('/admin/services');
    }

    public function edit(string $id): string
    {
        $this->requireAdmin();

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $service = $this->services->findById($sid);

        if ($service === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        return $this->render('admin/services/edit', [
            'title' => 'Edit Service',
            'errors' => [],
            'service' => $service,
        ]);
    }

    public function update(string $id): string
    {
        $this->requireAdmin();

        $this->requireCsrf();

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        $errors = $this->validateServiceInput($name, $duration, $price);

        $service = $this->services->findById($sid);
        if ($service === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        if ($errors) {
            http_response_code(422);
            // Re-render edit with entered values
            $service['name'] = $name;
            $service['duration_minutes'] = $duration;
            $service['price'] = $price;

            return $this->render('admin/services/edit', [
                'title' => 'Edit Service',
                'errors' => $errors,
                'service' => $service,
            ]);
        }

        $this->services->update($sid, $name, $duration, $price);

        $this->flash('success', 'Service updated.');
        return $this->redirect('/admin/services');
    }

    public function delete(string $id): string
    {
        $this->requireAdmin();

        $this->requireCsrf();

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $this->services->delete($sid);

        $this->flash('success', 'Service deleted.');
        return $this->redirect('/admin/services');
    }
}
