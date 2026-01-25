<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
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
}
