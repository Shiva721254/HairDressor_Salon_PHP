<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\HairdresserRepositoryInterface;

final class HairdresserAdminController extends Controller
{
    public function __construct(private HairdresserRepositoryInterface $hairdressers)
    {
    }

    private function requireAdmin(): void
    {
        $this->requireRole('admin');
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->render('admin/hairdressers/index', [
            'title' => 'Admin - Hairdressers',
            'hairdressers' => $this->hairdressers->all(),
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->render('admin/hairdressers/create', [
            'title' => 'Add Hairdresser',
            'errors' => [],
            'old' => ['name' => ''],
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $name = trim((string)($_POST['name'] ?? ''));

        $errors = [];
        if ($name === '') $errors[] = 'Hairdresser name is required.';
        if (mb_strlen($name) > 100) $errors[] = 'Hairdresser name must be 100 characters or less.';

        if ($errors) {
            http_response_code(422);
            return $this->render('admin/hairdressers/create', [
                'title' => 'Add Hairdresser',
                'errors' => $errors,
                'old' => ['name' => $name],
            ]);
        }

        $this->hairdressers->create($name);

        $this->flash('success', 'Hairdresser created.');
        return $this->redirect('/admin/hairdressers');
    }

    public function edit(string $id): string
    {
        $this->requireAdmin();

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $hairdresser = $this->hairdressers->findById($hid);

        if ($hairdresser === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        return $this->render('admin/hairdressers/edit', [
            'title' => 'Edit Hairdresser',
            'errors' => [],
            'hairdresser' => $hairdresser,
        ]);
    }

    public function update(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $hairdresser = $this->hairdressers->findById($hid);
        if ($hairdresser === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $name = trim((string)($_POST['name'] ?? ''));

        $errors = [];
        if ($name === '') $errors[] = 'Hairdresser name is required.';
        if (mb_strlen($name) > 100) $errors[] = 'Hairdresser name must be 100 characters or less.';

        if ($errors) {
            http_response_code(422);
            $hairdresser['name'] = $name;

            return $this->render('admin/hairdressers/edit', [
                'title' => 'Edit Hairdresser',
                'errors' => $errors,
                'hairdresser' => $hairdresser,
            ]);
        }

        $this->hairdressers->update($hid, $name);

        $this->flash('success', 'Hairdresser updated.');
        return $this->redirect('/admin/hairdressers');
    }

    public function delete(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        // If appointments exist with this hairdresser, DB may throw due to FK.
        // If that happens, show a clean error message.
        try {
            $this->hairdressers->delete($hid);
            $this->flash('success', 'Hairdresser deleted.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Cannot delete hairdresser because there are appointments linked to it.');
        }

        return $this->redirect('/admin/hairdressers');
    }
}
