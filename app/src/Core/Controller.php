<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    /**
     * Render a view inside the main layout.
     *
     * @param string $view View name without ".php", e.g. "home" or "appointments/create"
     * @param array  $data Variables for the view
     */
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

        // Make variables available in the view as $title, $errors, etc.
        extract($data, EXTR_SKIP);

        // Render view into $content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout with $content inside
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    /**
     * Render JSON response (for API endpoints).
     */
    protected function json(array $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    /**
     * Redirect and terminate execution.
     */
    protected function redirect(string $to): never
    {
        header('Location: ' . $to);
        exit;
    }

    /**
     * Flash message helper.
     * - Set:  flash('success', 'Saved!')
     * - Get:  flash('success')
     */
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

    /**
     * Current logged-in user (minimal session identity).
     */
    protected function currentUser(): ?array
    {
        $u = $_SESSION['user'] ?? null;
        return is_array($u) ? $u : null;
    }

    /**
     * Require login or redirect to /login.
     */
    protected function requireLogin(): array
    {
        $u = $this->currentUser();
        if ($u === null) {
            $this->flash('error', 'Please login first.');
            $this->redirect('/login');
        }
        return $u;
    }

    /**
     * Require a role; if forbidden, redirect or render a 403 page.
     * Keep the return type as array for callers that need the user identity.
     */
    protected function requireRole(string $role): array
    {
        $u = $this->requireLogin();

        if (($u['role'] ?? '') !== $role) {
            http_response_code(403);
            // Redirecting is simpler because this method must return array.
            // You can also redirect to a dedicated /forbidden page if you want.
            $this->flash('error', 'You do not have permission to access that page.');
            $this->redirect('/');
        }

        return $u;
    }

    /**
     * CSRF token stored in session.
     */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Hidden field helper for forms.
     */
    protected function csrfField(): string
    {
        $token = $this->csrfToken();
        return '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8') .
            '">';
    }

    /**
     * Validate CSRF token for POST handlers.
     */
    protected function requireCsrf(): void
    {
        $posted  = $_POST['csrf_token'] ?? '';
        $session = $_SESSION['csrf_token'] ?? '';

        if (!is_string($posted) || !is_string($session) || $posted === '' || !hash_equals($session, $posted)) {
            http_response_code(419);
            $this->flash('error', 'Security check failed (CSRF). Please try again.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
