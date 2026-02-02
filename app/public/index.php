<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// --------------------------------------------------
// Start session (needed for auth later)
// --------------------------------------------------
session_start();
Env::load(__DIR__ . '/../.env');
// --------------------------------------------------
// Define routes
// --------------------------------------------------
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // ==================================================
    // PUBLIC PAGES
    // ==================================================
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);

    // ==================================================
    // AUTHENTICATION
    // ==================================================
    $r->addRoute('GET',  '/login',  ['App\Controllers\AuthController', 'showLogin']);
    $r->addRoute('POST', '/login',  ['App\Controllers\AuthController', 'login']);
    $r->addRoute('POST', '/logout', ['App\Controllers\AuthController', 'logout']);
    $r->addRoute('GET',  '/register',  ['App\Controllers\AuthController', 'showRegister']);
    $r->addRoute('POST', '/register',  ['App\Controllers\AuthController', 'register']);

    // ==================================================
    // DATABASE & SYSTEM
    // ==================================================
    $r->addRoute('GET', '/db/health', ['App\Controllers\DbController', 'health']);

    // ==================================================
    // PUBLIC-FACING RESOURCES (Browse Services & Hairdressers)
    // ==================================================
    // Services
    $r->addRoute('GET', '/services', ['App\Controllers\ServiceController', 'index']);

    // Hairdressers
    $r->addRoute('GET',  '/hairdressers',  ['App\Controllers\HairdresserController', 'index']);
    $r->addRoute('GET',  '/hairdressers/{id:\d+}', ['App\Controllers\HairdresserController', 'show']);
    $r->addRoute('GET', '/api/hairdressers/{id:\d+}/availability', ['App\Controllers\HairdresserController', 'availability']);


    // ==================================================
    // APPOINTMENTS (User Booking Flow)
    // ==================================================
    $r->addRoute('GET',  '/appointments',              ['App\Controllers\AppointmentController', 'index']);
    $r->addRoute('GET',  '/appointments/new',          ['App\Controllers\AppointmentController', 'create']);
    $r->addRoute('GET',  '/appointments/create',       ['App\Controllers\AppointmentController', 'create']); // Backwards compatibility
    $r->addRoute('GET',  '/appointments/slots',        ['App\Controllers\AppointmentController', 'slots']);
    $r->addRoute('POST', '/appointments/confirm',      ['App\Controllers\AppointmentController', 'confirm']);
    $r->addRoute('POST', '/appointments/finalize',     ['App\Controllers\AppointmentController', 'finalize']);
    $r->addRoute('GET',  '/appointments/{id:\d+}',     ['App\Controllers\AppointmentController', 'show']);
    $r->addRoute('POST', '/appointments/{id:\d+}/cancel',   ['App\Controllers\AppointmentController', 'cancel']);
    $r->addRoute('POST', '/appointments/{id:\d+}/complete', ['App\Controllers\AppointmentController', 'complete']);

    $r->addRoute('GET', '/api/slots', ['App\Controllers\AppointmentController', 'slots']);


    // ==================================================
    // ADMIN SECTION
    // ==================================================

    // Admin - Services CRUD
    $r->addRoute('GET',  '/admin/services',               ['App\Controllers\Admin\ServiceAdminController', 'index']);
    $r->addRoute('GET',  '/admin/services/new',           ['App\Controllers\Admin\ServiceAdminController', 'create']);
    $r->addRoute('POST', '/admin/services',               ['App\Controllers\Admin\ServiceAdminController', 'store']);
    $r->addRoute('GET',  '/admin/services/{id:\d+}/edit', ['App\Controllers\Admin\ServiceAdminController', 'edit']);
    $r->addRoute('POST', '/admin/services/{id:\d+}',      ['App\Controllers\Admin\ServiceAdminController', 'update']);
    $r->addRoute('POST', '/admin/services/{id:\d+}/delete', ['App\Controllers\Admin\ServiceAdminController', 'delete']);

    // Admin - Hairdressers CRUD
    $r->addRoute('GET',  '/admin/hairdressers',                ['App\Controllers\Admin\HairdresserAdminController', 'index']);
    $r->addRoute('GET',  '/admin/hairdressers/new',            ['App\Controllers\Admin\HairdresserAdminController', 'create']);
    $r->addRoute('POST', '/admin/hairdressers',                ['App\Controllers\Admin\HairdresserAdminController', 'store']);
    $r->addRoute('GET',  '/admin/hairdressers/{id:\d+}/edit',  ['App\Controllers\Admin\HairdresserAdminController', 'edit']);
    $r->addRoute('POST', '/admin/hairdressers/{id:\d+}',       ['App\Controllers\Admin\HairdresserAdminController', 'update']);
    $r->addRoute('POST', '/admin/hairdressers/{id:\d+}/delete', ['App\Controllers\Admin\HairdresserAdminController', 'delete']);

    // Admin - Availability CRUD
    $r->addRoute('GET',  '/admin/availability',               ['App\Controllers\Admin\AvailabilityAdminController', 'index']);
    $r->addRoute('GET',  '/admin/availability/new',           ['App\Controllers\Admin\AvailabilityAdminController', 'create']);
    $r->addRoute('POST', '/admin/availability',               ['App\Controllers\Admin\AvailabilityAdminController', 'store']);
    $r->addRoute('GET',  '/admin/availability/{id:\d+}/edit', ['App\Controllers\Admin\AvailabilityAdminController', 'edit']);
    $r->addRoute('POST', '/admin/availability/{id:\d+}',      ['App\Controllers\Admin\AvailabilityAdminController', 'update']);
    $r->addRoute('POST', '/admin/availability/{id:\d+}/delete', ['App\Controllers\Admin\AvailabilityAdminController', 'delete']);
});

// --------------------------------------------------
// Fetch HTTP method and URI
// --------------------------------------------------
$httpMethod = $_SERVER['REQUEST_METHOD'];

// Prefer explicit query routing: /index.php?route=/appointments
$uri = $_GET['route'] ?? $_SERVER['REQUEST_URI'];

// If using REQUEST_URI, remove query string
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

// Normalize: ensure leading slash; trim trailing slash (except root)
$uri = '/' . ltrim($uri, '/');
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}


// --------------------------------------------------
// Dispatch route
// --------------------------------------------------
try {
    $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND:
            http_response_code(404);
            echo '404 - Page not found';
            break;

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            http_response_code(405);
            echo '405 - Method not allowed';
            break;

        case FastRoute\Dispatcher::FOUND:
            [$controllerClass, $method] = $routeInfo[1];
            $vars = $routeInfo[2];

            if (!class_exists($controllerClass)) {
                throw new RuntimeException("Controller not found: $controllerClass");
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Method not found: $method");
            }

            echo $controller->$method(...array_values($vars));
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);

    // Default: do not leak error details
    echo '500 - Internal Server Error';

    // Dev-only diagnostics (optional)
    if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
        echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
}
