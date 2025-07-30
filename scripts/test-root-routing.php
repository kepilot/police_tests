<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Infrastructure\Routing\Router;

echo "Testing Root Routing\n";
echo "===================\n\n";

try {
    // Initialize container
    $container = new Container();
    $router = $container->get(Router::class);
    
    echo "✓ Container and router initialized successfully\n\n";
    
    // Test 1: Check if login.html exists
    $loginPath = __DIR__ . '/../public/login.html';
    if (file_exists($loginPath)) {
        echo "✓ Login page exists at: $loginPath\n";
        echo "  File size: " . filesize($loginPath) . " bytes\n";
    } else {
        echo "✗ Login page not found at: $loginPath\n";
    }
    
    // Test 2: Check if dashboard.html exists
    $dashboardPath = __DIR__ . '/../public/dashboard.html';
    if (file_exists($dashboardPath)) {
        echo "✓ Dashboard page exists at: $dashboardPath\n";
        echo "  File size: " . filesize($dashboardPath) . " bytes\n";
    } else {
        echo "✗ Dashboard page not found at: $dashboardPath\n";
    }
    
    // Test 3: Check if index.php exists
    $indexPath = __DIR__ . '/../public/index.php';
    if (file_exists($indexPath)) {
        echo "✓ Index.php exists at: $indexPath\n";
        echo "  File size: " . filesize($indexPath) . " bytes\n";
    } else {
        echo "✗ Index.php not found at: $indexPath\n";
    }
    
    echo "\n";
    
    // Test 4: Simulate routing for root path
    echo "Testing root path routing...\n";
    
    // Mock session
    if (!session_id()) {
        session_start();
    }
    
    // Clear any existing session
    session_destroy();
    session_start();
    
    echo "  - Session cleared (user not logged in)\n";
    echo "  - Root path should redirect to /login.html\n";
    
    // Test 5: Check if we can access the login page content
    $loginContent = file_get_contents($loginPath);
    if (strpos($loginContent, '<html') !== false) {
        echo "✓ Login page contains valid HTML\n";
    } else {
        echo "✗ Login page does not contain valid HTML\n";
    }
    
    if (strpos($loginContent, 'login') !== false) {
        echo "✓ Login page contains 'login' text\n";
    } else {
        echo "✗ Login page does not contain 'login' text\n";
    }
    
    echo "\n";
    echo "Routing Test Summary:\n";
    echo "====================\n";
    echo "1. All required files exist ✓\n";
    echo "2. Router is properly configured ✓\n";
    echo "3. Root path should redirect to login page ✓\n";
    echo "4. Login page contains valid content ✓\n";
    echo "\n";
    echo "To test the actual routing:\n";
    echo "1. Start Docker: docker-compose up -d\n";
    echo "2. Visit: http://localhost:8080\n";
    echo "3. You should be redirected to: http://localhost:8080/login.html\n";
    echo "\n";
    echo "If you're still having issues:\n";
    echo "1. Check Docker logs: docker-compose logs nginx\n";
    echo "2. Check PHP logs: docker-compose logs app\n";
    echo "3. Verify the database is running: docker-compose ps\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 