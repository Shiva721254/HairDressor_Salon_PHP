<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\HairdresserRepository;

final class HairdresserAdminController extends Controller
{
    private function ensureAdmin(): array
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            // We return user anyway; caller will return render.
        }
        return $user;
    }

    private function isValidCsrf(): bool
    {
        $token = (string)($_POST['csrf_token'] ?? '');
        $session = (string)($_SESSION['csrf_token'] ?? '');
        return $token !== '' && $session !== '' && hash_equals($session, $token);
    }

    public function index(): string
    {
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $repo = new HairdresserRepository();

        return $this->render('admin/hairdressers/index', [
            'title' => 'Admin - Hairdressers',
            'hairdressers' => $repo->all(),
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        return $this->render('admin/hairdressers/create', [
            'title' => 'Add Hairdresser',
            'errors' => [],
            'old' => ['name' => ''],
        ]);
    }

    public function store(): string
    {
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

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

        $repo = new HairdresserRepository();
        $repo->create($name);

        $this->flash('success', 'Hairdresser created.');
        return $this->redirect('/admin/hairdressers');
    }

    public function edit(string $id): string
    {
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $repo = new HairdresserRepository();
        $hairdresser = $repo->findById($hid);

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
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $repo = new HairdresserRepository();
        $hairdresser = $repo->findById($hid);
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

        $repo->update($hid, $name);

        $this->flash('success', 'Hairdresser updated.');
        return $this->redirect('/admin/hairdressers');
    }

    public function delete(string $id): string
    {
        $user = $this->ensureAdmin();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

        $hid = (int)$id;
        if ($hid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Hairdresser not found']);
        }

        $repo = new HairdresserRepository();

        // If appointments exist with this hairdresser, DB may throw due to FK.
        // If that happens, show a clean error message.
        try {
            $repo->delete($hid);
            $this->flash('success', 'Hairdresser deleted.');
        } catch (\Throwable $e) {
            $this->flash('error', 'Cannot delete hairdresser because there are appointments linked to it.');
        }

        return $this->redirect('/admin/hairdressers');
    }
}
