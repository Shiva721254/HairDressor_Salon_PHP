<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
    public function showRegister(): string
    {
        // If already logged in, redirect to main flow
        if ($this->currentUser() !== null) {
            return $this->redirect('/appointments');
        }

        return $this->render('auth/register', [
            'title' => 'Register',
            'errors' => [],
            'old' => ['email' => ''],
        ]);
    }

    public function showLogin(): string
    {
        // Optional: redirect already logged-in users away from login page
        if ($this->currentUser() !== null) {
            return $this->redirect('/appointments');
        }

        return $this->render('auth/login', [
            'title' => 'Login',
            'errors' => [],
            'oldEmail' => '',
        ]);
    }

    public function login(): string
    {
        // ✅ CSRF protection
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
            return $this->render('auth/login', [
                'title' => 'Login',
                'errors' => $errors,
                'oldEmail' => $email,
            ]);
        }

        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            http_response_code(401);
            return $this->render('auth/login', [
                'title' => 'Login',
                'errors' => ['Invalid email or password.'],
                'oldEmail' => $email,
            ]);
        }

        // Store minimal identity in session
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];

        $this->flash('success', 'Logged in successfully.');
        return $this->redirect('/appointments');
    }

    public function logout(): string
    {
        // ✅ CSRF protection
        $this->requireCsrf();

        unset($_SESSION['user']);
        $this->flash('success', 'Logged out.');
        return $this->redirect('/');
    }

    public function register(): string
    {
        $this->requireCsrf();

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $errors = [];

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

        $repo = new UserRepository();
        if (!$errors && $repo->findByEmail($email) !== null) {
            $errors[] = 'An account with this email already exists.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('auth/register', [
                'title' => 'Register',
                'errors' => $errors,
                'old' => ['email' => $email],
            ]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $repo->create($email, $hash, 'client');

        $_SESSION['user'] = [
            'id' => (int)$userId,
            'email' => $email,
            'role' => 'client',
        ];

        $this->flash('success', 'Account created. Welcome!');
        return $this->redirect('/appointments');
    }
}
