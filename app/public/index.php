<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

// --------------------------------------------------
// Start session (needed for auth later)
// --------------------------------------------------
session_start();

// --------------------------------------------------
// Define routes
// --------------------------------------------------
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Home
    $r->addRoute('GET', '/', ['App\Controllers\HomeController', 'home']);

    // Hello (example)
    $r->addRoute('GET', '/hello/{name}', ['App\Controllers\HelloController', 'greet']);

    // Contact (MVC version)
    $r->addRoute('GET', '/contact', ['App\Controllers\HomeController', 'contact']);
    $r->addRoute('POST', '/contact', ['App\Controllers\HomeController', 'submitContact']);
});

// --------------------------------------------------
// Fetch HTTP method and URI
// --------------------------------------------------
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string (?a=b)
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

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
        echo $controller->$method($vars);
        break;
}
