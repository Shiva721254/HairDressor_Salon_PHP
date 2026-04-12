<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\GdprRequestRepositoryInterface;
use App\Repositories\UserRepositoryInterface;

final class ProfileController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AppointmentRepositoryInterface $appointments,
        private GdprRequestRepositoryInterface $gdprRequests
    ) {
    }

    /**
     * @param array<int, string> $errors
     * @param array{name:string,email:string} $old
     */
    private function renderProfileForm(array $errors, array $old, ?string $success = null, ?int $statusCode = null): string
    {
        if ($statusCode !== null) {
            http_response_code($statusCode);
        }

        return $this->render('auth/profile', [
            'title' => 'My Profile',
            'errors' => $errors,
            'old' => $old,
            'success' => $success,
        ]);
    }

    public function show(): string
    {
        $user = $this->requireLogin();

        return $this->renderProfileForm(
            [],
            [
                'name' => (string)($user['name'] ?? ''),
                'email' => (string)($user['email'] ?? ''),
            ],
            $this->flash('success')
        );
    }

    public function update(): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $userId = (int)($user['id'] ?? 0);

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['new_password_confirm'] ?? '');

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }

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
            return $this->renderProfileForm($errors, ['name' => $name, 'email' => $email], null, 422);
        }

        $authUser = $this->users->findAuthById($userId);

        if ($authUser === null || !password_verify($currentPassword, (string)$authUser['password_hash'])) {
            return $this->renderProfileForm(['Current password is incorrect.'], ['name' => $name, 'email' => $email], null, 401);
        }

        $existing = $this->users->findByEmail($email);
        if ($existing !== null && (int)$existing['id'] !== $userId) {
            return $this->renderProfileForm(['That email address is already in use.'], ['name' => $name, 'email' => $email], null, 422);
        }

        $changed = false;

        if ($name !== (string)($authUser['name'] ?? '')) {
            $this->users->updateName($userId, $name);
            $_SESSION['user']['name'] = $name;
            $changed = true;
        }

        if ($email !== (string)($authUser['email'] ?? '')) {
            $this->users->updateEmail($userId, $email);
            $_SESSION['user']['email'] = $email;
            $changed = true;
        }

        if ($newPassword !== '') {
            $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
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
        $this->requireCsrf();

        $userId = (int)($user['id'] ?? 0);

        $userRow = $this->users->findById($userId);

        if ($userRow === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'User not found']);
        }

        $appointments = $this->appointments->allWithDetails('all', $userId);

        $this->gdprRequests->create($userId, 'export');

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
        $this->gdprRequests->create($userId, 'deletion');

        $this->flash('success', 'Deletion request received. We will contact you to confirm.');
        return $this->redirect('/profile');
    }
}
