<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepositoryInterface;

final class AuthController extends Controller
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    private function redirectIfLoggedIn(): void
    {
        if ($this->currentUser() !== null) {
            $this->redirect('/appointments');
        }
    }

    /** @param array<int, string> $errors */
    private function renderLogin(string $title, string $action, string $mode, array $errors = [], string $oldEmail = ''): string
    {
        return $this->render('auth/login', [
            'title' => $title,
            'errors' => $errors,
            'oldEmail' => $oldEmail,
            'action' => $action,
            'mode' => $mode,
        ]);
    }

    public function showRegister(): string
    {
        $this->redirectIfLoggedIn();

        return $this->render('auth/register', [
            'title' => 'Register',
            'errors' => [],
            'old' => ['name' => '', 'email' => ''],
        ]);
    }

    public function showLogin(): string
    {
        $this->redirectIfLoggedIn();
        return $this->renderLogin('Client Login', '/login', 'client');
    }

    public function showAdminLogin(): string
    {
        $this->redirectIfLoggedIn();
        return $this->renderLogin('Admin Login', '/admin/login', 'admin');
    }

    public function showStaffLogin(): string
    {
        $this->redirectIfLoggedIn();
        return $this->renderLogin('Staff Login', '/staff/login', 'staff');
    }

    private function loginByRole(string $requiredRole, string $actionPath, string $title, string $mode): string
    {
        $this->requireCsrf();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->renderLogin($title, $actionPath, $mode, $errors, $email);
        }

        $user = $this->users->findByEmail($email);
        if (
            $user === null ||
            !password_verify($password, (string)$user['password_hash']) ||
            (string)($user['role'] ?? '') !== $requiredRole
        ) {
            http_response_code(401);
            return $this->renderLogin(
                $title,
                $actionPath,
                $mode,
                ['Invalid email or password for this login route.'],
                $email
            );
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => (string)($user['name'] ?? ''),
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
            'hairdresser_id' => isset($user['hairdresser_id']) ? (int)$user['hairdresser_id'] : null,
        ];

        $this->flash('success', 'Logged in successfully.');

        if ($requiredRole === 'admin') {
            return $this->redirect('/appointments?filter=all');
        }

        if ($requiredRole === 'staff') {
            return $this->redirect('/staff/appointments');
        }

        return $this->redirect('/appointments');
    }

    public function login(): string
    {
        return $this->loginByRole('client', '/login', 'Client Login', 'client');
    }

    public function adminLogin(): string
    {
        return $this->loginByRole('admin', '/admin/login', 'Admin Login', 'admin');
    }

    public function staffLogin(): string
    {
        return $this->loginByRole('staff', '/staff/login', 'Staff Login', 'staff');
    }

    public function logout(): string
    {
        // ✅ CSRF protection
        $this->requireCsrf();

        unset($_SESSION['user']);
        session_regenerate_id(true);
        $this->flash('success', 'Logged out.');
        return $this->redirect('/');
    }

    public function register(): string
    {
        $this->requireCsrf();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $errors = [];

        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors[] = 'Name must be between 2 and 100 characters.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($confirm === '' || $confirm !== $password) {
            $errors[] = 'Passwords do not match.';
        }

        if (!$errors && $this->users->findByEmail($email) !== null) {
            $errors[] = 'An account with this email already exists.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('auth/register', [
                'title' => 'Register',
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->users->create($name, $email, $hash, 'client');

        $_SESSION['user'] = [
            'id' => (int)$userId,
            'name' => $name,
            'email' => $email,
            'role' => 'client',
            'hairdresser_id' => null,
        ];

        $this->flash('success', 'Account created. Welcome!');
        return $this->redirect('/appointments');
    }
}
