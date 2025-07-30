<?php

echo "Testing Web Access\n";
echo "=================\n\n";

// Test 1: Check if we can access the application via HTTP
echo "Testing HTTP access to localhost:8080...\n";

$url = 'http://localhost:8080/';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Test-Script/1.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ],
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "✗ Failed to access $url\n";
    echo "  Error: " . error_get_last()['message'] . "\n";
} else {
    echo "✓ Successfully accessed $url\n";
    echo "  Response length: " . strlen($response) . " bytes\n";
    
    // Check if it's a redirect
    $headers = $http_response_header ?? [];
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') === 0) {
            echo "  Redirect: " . trim(substr($header, 9)) . "\n";
        }
    }
    
    // Check if it contains login content
    if (strpos($response, 'login') !== false) {
        echo "  ✓ Response contains 'login' text\n";
    } else {
        echo "  ✗ Response does not contain 'login' text\n";
    }
    
    // Show first 500 characters of response
    echo "  First 500 characters:\n";
    echo "  " . str_replace("\n", "\n  ", substr($response, 0, 500)) . "\n";
}

echo "\n";

// Test 2: Check if we can access login.html directly
echo "Testing direct access to login.html...\n";
$loginUrl = 'http://localhost:8080/login.html';
$loginResponse = @file_get_contents($loginUrl, false, $context);

if ($loginResponse === false) {
    echo "✗ Failed to access $loginUrl\n";
    echo "  Error: " . error_get_last()['message'] . "\n";
} else {
    echo "✓ Successfully accessed $loginUrl\n";
    echo "  Response length: " . strlen($loginResponse) . " bytes\n";
    
    if (strpos($loginResponse, 'login') !== false) {
        echo "  ✓ Login page contains 'login' text\n";
    } else {
        echo "  ✗ Login page does not contain 'login' text\n";
    }
}

echo "\n";

// Test 3: Check if we can access the health endpoint
echo "Testing health endpoint...\n";
$healthUrl = 'http://localhost:8080/health';
$healthResponse = @file_get_contents($healthUrl, false, $context);

if ($healthResponse === false) {
    echo "✗ Failed to access $healthUrl\n";
    echo "  Error: " . error_get_last()['message'] . "\n";
} else {
    echo "✓ Successfully accessed $healthUrl\n";
    echo "  Response: " . $healthResponse . "\n";
}

echo "\n";
echo "Test Summary:\n";
echo "=============\n";
echo "1. Root path access: " . ($response !== false ? "✓ Working" : "✗ Failed") . "\n";
echo "2. Login page access: " . ($loginResponse !== false ? "✓ Working" : "✗ Failed") . "\n";
echo "3. Health endpoint: " . ($healthResponse !== false ? "✓ Working" : "✗ Failed") . "\n";
echo "\n";
echo "If all tests pass, the application should be working correctly.\n";
echo "Try visiting http://localhost:8080 in your browser.\n"; 