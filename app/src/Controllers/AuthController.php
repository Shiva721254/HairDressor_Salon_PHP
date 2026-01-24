<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
    public function showLogin(): string
    {
        return $this->render('auth/login', [
            'title' => 'Login',
            'errors' => [],
        ]);
    }

    public function login(): string
    {
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
            ]);
        }

        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            http_response_code(401);
            return $this->render('auth/login', [
                'title' => 'Login',
                'errors' => ['Invalid email or password.'],
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
        unset($_SESSION['user']);
        $this->flash('success', 'Logged out.');
        return $this->redirect('/');
    }
}
