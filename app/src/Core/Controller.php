<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    /**
     * Render a view inside the main layout.
     *
     * @param string $view View name without ".php", e.g. "home" or "appointments/new"
     * @param array  $data Variables for the view
     */
    protected function render(string $view, array $data = []): string
    {
        // Views live under /app/views inside the container
        // From app/src/Core, go up 2 levels to reach /app, then into /views
        $viewsRoot = dirname(__DIR__, 2) . '/views';
        $viewFile  = $viewsRoot . '/' . $view . '.php';
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
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $to): string
{
    header('Location: ' . $to);
    return '';
}

protected function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}





}
