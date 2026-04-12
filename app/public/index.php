<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\AvailabilityRepository;
use App\Repositories\AvailabilityRepositoryInterface;
use App\Repositories\GdprRequestRepositoryInterface;
use App\Repositories\HairdresserRepository;
use App\Repositories\HairdresserRepositoryInterface;
use App\Repositories\ServiceRepository;
use App\Repositories\ServiceRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\GdprRequestRepository;
use App\Repositories\UserRepository;

// --------------------------------------------------
// Start session (needed for auth later)
// --------------------------------------------------
session_start();
Env::load(__DIR__ . '/../.env');

$container = new Container();
$container->bind(UserRepositoryInterface::class, UserRepository::class);
$container->bind(AppointmentRepositoryInterface::class, AppointmentRepository::class);
$container->bind(AvailabilityRepositoryInterface::class, AvailabilityRepository::class);
$container->bind(HairdresserRepositoryInterface::class, HairdresserRepository::class);
$container->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
$container->bind(GdprRequestRepositoryInterface::class, GdprRequestRepository::class);
// --------------------------------------------------
// Define routes
// --------------------------------------------------
$dispatcherFactory = 'FastRoute\\simpleDispatcher';
$dispatcher = $dispatcherFactory(function ($r) {
    // ==================================================
    // PUBLIC PAGES
    // ==================================================
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);

    // ==================================================
    // AUTHENTICATION
    // ==================================================
    $r->addRoute('GET',  '/admin/login',  ['App\Controllers\AuthController', 'showAdminLogin']);
    $r->addRoute('POST', '/admin/login',  ['App\Controllers\AuthController', 'adminLogin']);
    $r->addRoute('GET',  '/staff/login',  ['App\Controllers\AuthController', 'showStaffLogin']);
    $r->addRoute('POST', '/staff/login',  ['App\Controllers\AuthController', 'staffLogin']);
    $r->addRoute('GET',  '/login',  ['App\Controllers\AuthController', 'showLogin']);
    $r->addRoute('POST', '/login',  ['App\Controllers\AuthController', 'login']);
    $r->addRoute('POST', '/logout', ['App\Controllers\AuthController', 'logout']);
    $r->addRoute('GET',  '/register',  ['App\Controllers\AuthController', 'showRegister']);
    $r->addRoute('POST', '/register',  ['App\Controllers\AuthController', 'register']);
    $r->addRoute('GET',  '/profile',   ['App\Controllers\ProfileController', 'show']);
    $r->addRoute('POST', '/profile',   ['App\Controllers\ProfileController', 'update']);
    $r->addRoute('POST', '/profile/export', ['App\Controllers\ProfileController', 'export']);
    $r->addRoute('POST', '/profile/delete', ['App\Controllers\ProfileController', 'requestDeletion']);

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

    // Admin - Appointment CRUD
    $r->addRoute('GET',  '/admin/appointments/new',           ['App\Controllers\AppointmentController', 'adminCreate']);
    $r->addRoute('POST', '/admin/appointments',               ['App\Controllers\AppointmentController', 'adminStore']);
    $r->addRoute('GET',  '/admin/appointments/{id:\d+}/edit', ['App\Controllers\AppointmentController', 'adminEdit']);
    $r->addRoute('POST', '/admin/appointments/{id:\d+}',      ['App\Controllers\AppointmentController', 'adminUpdate']);
    $r->addRoute('POST', '/admin/appointments/{id:\d+}/delete', ['App\Controllers\AppointmentController', 'adminDelete']);

    $r->addRoute('GET', '/api/slots', ['App\Controllers\AppointmentController', 'slots']);
    $r->addRoute('GET', '/api/availability', ['App\Controllers\AppointmentController', 'availabilityApi']);

    // Staff - own schedule only
    $r->addRoute('GET',  '/staff/appointments', ['App\Controllers\StaffController', 'appointments']);
    $r->addRoute('GET',  '/staff/availability', ['App\Controllers\StaffController', 'availability']);
    $r->addRoute('POST', '/staff/availability', ['App\Controllers\StaffController', 'storeWeeklyAvailability']);
    $r->addRoute('POST', '/staff/availability/{id:\d+}/update', ['App\Controllers\StaffController', 'updateWeeklyAvailability']);
    $r->addRoute('POST', '/staff/availability/{id:\d+}/delete', ['App\Controllers\StaffController', 'deleteWeeklyAvailability']);
    $r->addRoute('POST', '/staff/overview/adjust', ['App\Controllers\StaffController', 'adjustOverview']);
    $r->addRoute('POST', '/staff/overview/{slotId:\d+}/clear', ['App\Controllers\StaffController', 'clearOverviewAdjustment']);
    $r->addRoute('POST', '/staff/unavailability', ['App\Controllers\StaffController', 'storeBlockedSlot']);
    $r->addRoute('POST', '/staff/unavailability/{id:\d+}/delete', ['App\Controllers\StaffController', 'deleteBlockedSlot']);


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

    // Admin - Staff Management
    $r->addRoute('GET',  '/admin/staff',               ['App\Controllers\Admin\StaffAdminController', 'index']);
    $r->addRoute('GET',  '/admin/staff/new',           ['App\Controllers\Admin\StaffAdminController', 'create']);
    $r->addRoute('POST', '/admin/staff',               ['App\Controllers\Admin\StaffAdminController', 'store']);
    $r->addRoute('GET',  '/admin/staff/{id:\d+}/edit', ['App\Controllers\Admin\StaffAdminController', 'edit']);
    $r->addRoute('POST', '/admin/staff/{id:\d+}',      ['App\Controllers\Admin\StaffAdminController', 'update']);
    $r->addRoute('POST', '/admin/staff/{id:\d+}/delete', ['App\Controllers\Admin\StaffAdminController', 'delete']);

    // Admin - GDPR Requests
    $r->addRoute('GET', '/admin/gdpr-requests', ['App\Controllers\Admin\GdprAdminController', 'index']);
    $r->addRoute('POST', '/admin/gdpr-requests/{id:\d+}/process', ['App\Controllers\Admin\GdprAdminController', 'process']);
    $r->addRoute('GET', '/admin/clients', ['App\Controllers\Admin\ClientAdminController', 'index']);
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
        case 0: // FastRoute\Dispatcher::NOT_FOUND
        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
        break;

        case 1: // FastRoute\Dispatcher::FOUND
            [$controllerClass, $method] = $routeInfo[1];
            $vars = $routeInfo[2];

            if (!class_exists($controllerClass)) {
                throw new RuntimeException("Controller not found: $controllerClass");
            }

            $controller = $container->make($controllerClass);

            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Method not found: $method");
            }

            echo $controller->$method(...array_values($vars));
            break;

        case 2: // FastRoute\Dispatcher::METHOD_NOT_ALLOWED
    http_response_code(405);
    require __DIR__ . '/../views/errors/404.php';
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
