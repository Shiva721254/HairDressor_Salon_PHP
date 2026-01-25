<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\ServiceRepository;

final class ServiceAdminController extends Controller
{
    public function index(): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $repo = new ServiceRepository();

        return $this->render('admin/services/index', [
            'title' => 'Admin - Services',
            'services' => $repo->all(),
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        return $this->render('admin/services/create', [
            'title' => 'Add Service',
            'errors' => [],
            'old' => ['name' => '', 'duration_minutes' => '', 'price' => ''],
        ]);
    }

    public function store(): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $this->requireCsrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        $errors = [];
        if ($name === '') $errors[] = 'Service name is required.';
        if ($duration <= 0) $errors[] = 'Duration must be greater than 0.';
        if ($price < 0) $errors[] = 'Price must be 0 or more.';

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

        $repo = new ServiceRepository();
        $repo->create($name, $duration, $price);

        $this->flash('success', 'Service created.');
        return $this->redirect('/admin/services');
    }

    public function edit(string $id): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $repo = new ServiceRepository();
        $service = $repo->findById($sid);

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
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $this->requireCsrf();

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);

        $errors = [];
        if ($name === '') $errors[] = 'Service name is required.';
        if ($duration <= 0) $errors[] = 'Duration must be greater than 0.';
        if ($price < 0) $errors[] = 'Price must be 0 or more.';

        $repo = new ServiceRepository();
        $service = $repo->findById($sid);
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

        $repo->update($sid, $name, $duration, $price);

        $this->flash('success', 'Service updated.');
        return $this->redirect('/admin/services');
    }

    public function delete(string $id): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $this->requireCsrf();

        $sid = (int)$id;
        if ($sid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Service not found']);
        }

        $repo = new ServiceRepository();
        $repo->delete($sid);

        $this->flash('success', 'Service deleted.');
        return $this->redirect('/admin/services');
    }
}
