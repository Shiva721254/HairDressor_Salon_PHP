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
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);
    $r->addRoute('GET', '/hello/{name}', ['App\Controllers\HelloController', 'greet']);   
   // Home and Contact
    $r->addRoute('GET', '/contact', ['App\Controllers\HomeController', 'contact']);
    $r->addRoute('POST', '/contact', ['App\Controllers\HomeController', 'submitContact']);
   
    // Services
    $r->addRoute('GET', '/db/health', ['App\Controllers\DbController', 'health']);
    $r->addRoute('GET', '/services', ['App\Controllers\ServiceController', 'index']);
  
    // Hairdressers
    $r->addRoute('GET',  '/hairdressers',  ['App\Controllers\HairdresserController', 'index']);
    $r->addRoute('GET',  '/hairdressers/{id:\d+}', ['App\Controllers\HairdresserController', 'show']);


// Appointments
$r->addRoute('GET',  '/appointments',        ['App\Controllers\AppointmentController', 'index']);
$r->addRoute('GET',  '/appointments/create', ['App\Controllers\AppointmentController', 'create']);
$r->addRoute('GET',  '/appointments/slots',  ['App\Controllers\AppointmentController', 'slots']);
$r->addRoute('POST', '/appointments/confirm', ['App\Controllers\AppointmentController', 'confirm']);
$r->addRoute('POST', '/appointments/finalize', ['App\Controllers\AppointmentController', 'finalize']);
$r->addRoute('GET', '/appointments/{id:\d+}', ['App\Controllers\AppointmentController', 'show']);
$r->addRoute('POST', '/appointments/{id:\d+}/cancel', ['App\Controllers\AppointmentController', 'cancel']);







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

        // Call controller method and output result
        echo $controller->$method(...array_values($vars));

        break;
}
