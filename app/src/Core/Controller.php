<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    protected function render(string $view, array $data = []): string
    {
        $viewsRoot  = dirname(__DIR__, 2) . '/views';
        $viewFile   = $viewsRoot . '/' . $view . '.php';
        $layoutFile = $viewsRoot . '/layouts/main.php';

        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: $viewFile");
        }
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: $layoutFile");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    protected function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }

    protected function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['flash'][$key] = $value;
            return null;
        }

        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return is_string($msg) ? $msg : null;
    }

    protected function currentUser(): ?array
    {
        $u = $_SESSION['user'] ?? null;
        return is_array($u) ? $u : null;
    }

    protected function requireLogin(): array
    {
        $u = $this->currentUser();
        if ($u === null) {
            $this->flash('error', 'Please login first.');
            $this->redirect('/login');
        }
        return $u;
    }

    protected function requireRole(string $role): array
    {
        $u = $this->requireLogin();
        if (($u['role'] ?? '') !== $role) {
            http_response_code(403);
            echo $this->render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        return $u;
    }

    // ---------------------------
    // CSRF helpers
    // ---------------------------
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    protected function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8') .
            '">';
    }

    protected function requireCsrf(): void
    {
        $token   = (string)($_POST['csrf_token'] ?? '');
        $session = (string)($_SESSION['csrf_token'] ?? '');

        if ($token === '' || $session === '' || !hash_equals($session, $token)) {
            http_response_code(403);
            echo $this->render('errors/403', ['title' => 'Invalid CSRF token']);
            exit;
        }
    }
}
