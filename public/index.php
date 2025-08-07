<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-errors.log');

// Start session for authentication
session_start();

// Load environment variables
$envFile = __DIR__ . '/../env.local';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Infrastructure\Container\Container;
use App\Infrastructure\Routing\Router;

// Set content type to JSON
header('Content-Type: application/json');

try {
    $container = new Container();
    $router = $container->get(Router::class);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = $_SERVER['REQUEST_URI'] ?? '/';

    // Handle the request through the router
    $router->handleRequest($method, $path);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
} 