<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\HairdresserRepositoryInterface;

final class StaffAdminController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private HairdresserRepositoryInterface $hairdressers
    ) {
    }

    private function requireAdmin(): void
    {
        $this->requireRole('admin');
    }

    public function index(): string
    {
        $this->requireAdmin();

        $staff = $this->users->allStaff();

        return $this->render('admin/staff/index', [
            'title' => 'Admin - Staff Management',
            'staff' => $staff,
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        $hairdressers = $this->hairdressers->all();

        return $this->render('admin/staff/create', [
            'title' => 'Add Staff Member',
            'hairdressers' => $hairdressers,
            'errors' => [],
            'old' => ['name' => '', 'email' => '', 'password' => '', 'hairdresser_id' => ''],
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Staff name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Staff name must be 100 characters or less.';
        }

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (mb_strlen($email) > 255) {
            $errors[] = 'Email must be 255 characters or less.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (mb_strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($hairdresserId <= 0) {
            $errors[] = 'Please select a hairdresser profile.';
        }

        // Check email doesn't already exist
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $existing = $this->users->findByEmail($email);
            if ($existing !== null) {
                $errors[] = 'This email is already registered.';
            }
        }

        if ($errors) {
            http_response_code(422);
            $hairdressers = $this->hairdressers->all();
            return $this->render('admin/staff/create', [
                'title' => 'Add Staff Member',
                'hairdressers' => $hairdressers,
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email, 'password' => '', 'hairdresser_id' => $hairdresserId],
            ]);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $this->users->createStaff($name, $email, $passwordHash, $hairdresserId);

        $this->flash('success', "Staff member '$name' created successfully.");
        return $this->redirect('/admin/staff');
    }

    public function edit(string $id): string
    {
        $this->requireAdmin();

        $staffId = (int)$id;
        $staffUser = $this->users->findById($staffId);

        if ($staffUser === null || (string)($staffUser['role'] ?? '') !== 'staff') {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Staff not found']);
        }

        $hairdressers = $this->hairdressers->all();

        return $this->render('admin/staff/edit', [
            'title' => 'Edit Staff Member',
            'staff' => $staffUser,
            'hairdressers' => $hairdressers,
            'errors' => [],
            'old' => [
                'name' => $staffUser['name'],
                'email' => $staffUser['email'],
                'hairdresser_id' => (int)($staffUser['hairdresser_id'] ?? 0),
            ],
        ]);
    }

    public function update(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $staffId = (int)$id;
        $staffUser = $this->users->findById($staffId);

        if ($staffUser === null || (string)($staffUser['role'] ?? '') !== 'staff') {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Staff not found']);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Staff name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Staff name must be 100 characters or less.';
        }

        if ($email === '') {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (mb_strlen($email) > 255) {
            $errors[] = 'Email must be 255 characters or less.';
        } elseif ($email !== $staffUser['email']) {
            $existing = $this->users->findByEmail($email);
            if ($existing !== null) {
                $errors[] = 'This email is already registered.';
            }
        }

        if ($hairdresserId <= 0) {
            $errors[] = 'Please select a hairdresser profile.';
        }

        if ($errors) {
            http_response_code(422);
            $hairdressers = $this->hairdressers->all();
            return $this->render('admin/staff/edit', [
                'title' => 'Edit Staff Member',
                'staff' => $staffUser,
                'hairdressers' => $hairdressers,
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'email' => $email,
                    'hairdresser_id' => $hairdresserId,
                ],
            ]);
        }

        $this->users->updateName($staffId, $name);
        $this->users->updateEmail($staffId, $email);
        $this->users->updateStaffHairdresser($staffId, $hairdresserId);

        $this->flash('success', "Staff member '$name' updated successfully.");
        return $this->redirect('/admin/staff');
    }

    public function delete(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $staffId = (int)$id;
        $staffUser = $this->users->findById($staffId);

        if ($staffUser === null || (string)($staffUser['role'] ?? '') !== 'staff') {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Staff not found']);
        }

        $staffName = (string)($staffUser['name'] ?? '');
        $this->users->deleteStaff($staffId);

        $this->flash('success', "Staff member '$staffName' deleted successfully.");
        return $this->redirect('/admin/staff');
    }
}
