<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppointmentRepository;
use App\Repositories\GdprRequestRepository;
use App\Repositories\UserRepository;

final class ProfileController extends Controller
{
    public function show(): string
    {
        $user = $this->requireLogin();

        return $this->render('auth/profile', [
            'title' => 'My Profile',
            'errors' => [],
            'old' => [
                'email' => (string)($user['email'] ?? ''),
            ],
            'success' => $this->flash('success'),
        ]);
    }

    public function update(): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $userId = (int)($user['id'] ?? 0);

        $email = trim((string)($_POST['email'] ?? ''));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['new_password_confirm'] ?? '');

        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if ($currentPassword === '') {
            $errors[] = 'Current password is required to save changes.';
        }

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            }
            if ($confirmPassword === '' || $confirmPassword !== $newPassword) {
                $errors[] = 'New passwords do not match.';
            }
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('auth/profile', [
                'title' => 'My Profile',
                'errors' => $errors,
                'old' => ['email' => $email],
                'success' => null,
            ]);
        }

        $repo = new UserRepository();
        $authUser = $repo->findAuthById($userId);

        if ($authUser === null || !password_verify($currentPassword, (string)$authUser['password_hash'])) {
            http_response_code(401);
            return $this->render('auth/profile', [
                'title' => 'My Profile',
                'errors' => ['Current password is incorrect.'],
                'old' => ['email' => $email],
                'success' => null,
            ]);
        }

        $existing = $repo->findByEmail($email);
        if ($existing !== null && (int)$existing['id'] !== $userId) {
            http_response_code(422);
            return $this->render('auth/profile', [
                'title' => 'My Profile',
                'errors' => ['That email address is already in use.'],
                'old' => ['email' => $email],
                'success' => null,
            ]);
        }

        $changed = false;

        if ($email !== (string)($authUser['email'] ?? '')) {
            $repo->updateEmail($userId, $email);
            $_SESSION['user']['email'] = $email;
            $changed = true;
        }

        if ($newPassword !== '') {
            $repo->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
            $changed = true;
        }

        if (!$changed) {
            $this->flash('success', 'No changes were made.');
            return $this->redirect('/profile');
        }

        $this->flash('success', 'Profile updated successfully.');
        return $this->redirect('/profile');
    }

    public function export(): string
    {
        $user = $this->requireLogin();

        $userId = (int)($user['id'] ?? 0);

        $repo = new UserRepository();
        $userRow = $repo->findById($userId);

        if ($userRow === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'User not found']);
        }

        $appointments = (new AppointmentRepository())->allWithDetails('all', $userId);

        (new GdprRequestRepository())->create($userId, 'export');

        $payload = [
            'user' => $userRow,
            'appointments' => $appointments,
            'exported_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];

        $filename = 'salon-data-export-' . $userId . '-' . date('Ymd-His') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        return (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function requestDeletion(): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $userId = (int)($user['id'] ?? 0);
        (new GdprRequestRepository())->create($userId, 'deletion');

        $this->flash('success', 'Deletion request received. We will contact you to confirm.');
        return $this->redirect('/profile');
    }
}
