<?php

echo "Testing web login interface...\n";

// Test login with non-admin user
$loginData = [
    'email' => 'test1754125791@example.com',
    'password' => 'defaultPassword123!'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

echo "Sending login request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

$token = null;

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ Login successful!\n";
        if (isset($data['data']['token'])) {
            echo "✅ JWT token generated\n";
            $token = $data['data']['token'];
            
            // Decode the JWT token to check the payload
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
                echo "JWT Payload:\n";
                print_r($payload);
                
                if (isset($payload['role'])) {
                    echo "✅ Role included in JWT: " . $payload['role'] . "\n";
                } else {
                    echo "❌ Role not found in JWT\n";
                }
            }
        }
    } else {
        echo "❌ Login failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }
} else {
    echo "❌ HTTP request failed with status code: $httpCode\n";
    exit(1);
}

// Test accessing admin-only route with non-admin user
if ($token) {
    echo "\nTesting access to admin-only route with non-admin user...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/admin/dashboard');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Admin route HTTP Status Code: $httpCode\n";
    echo "Admin route Response:\n";
    echo $response . "\n";
    
    if ($httpCode === 403) {
        echo "✅ Access correctly denied to non-admin user\n";
    } else {
        echo "❌ Access control not working - non-admin user got access to admin route\n";
    }
}

// Test accessing user dashboard with non-admin user (should work)
if ($token) {
    echo "\nTesting access to user dashboard with non-admin user...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/dashboard.html');
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: text/html'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Dashboard HTTP Status Code: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "✅ Non-admin user can access dashboard\n";
    } else {
        echo "❌ Non-admin user cannot access dashboard\n";
    }
} 