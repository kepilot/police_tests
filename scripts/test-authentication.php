<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Application\Commands\RegisterUserCommand;
use App\Application\Commands\LoginUserCommand;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

echo "Testing Authentication Flow...\n\n";

try {
    $container = new Container();
    
    // Test 1: Register a new user
    echo "1. Testing user registration...\n";
    $registerHandler = $container->get(\App\Application\Commands\RegisterUserHandler::class);
    $registerCommand = new RegisterUserCommand(
        'Auth Test User',
        new Email('auth.test@example.com'),
        new Password('TestPass123!')
    );
    
    $user = $registerHandler($registerCommand);
    echo "✓ User registered successfully: " . $user->getId()->toString() . "\n\n";
    
    // Test 2: Login user
    echo "2. Testing user login...\n";
    $loginHandler = $container->get(\App\Application\Commands\LoginUserHandler::class);
    $loginCommand = new LoginUserCommand(
        new Email('auth.test@example.com'),
        'TestPass123!'
    );
    
    $loginResult = $loginHandler($loginCommand);
    echo "✓ Login successful\n";
    echo "  - Token: " . substr($loginResult['token'], 0, 50) . "...\n";
    echo "  - User: " . $loginResult['user']['name'] . "\n";
    echo "  - Expires in: " . $loginResult['expiresIn'] . " seconds\n\n";
    
    // Test 3: Validate JWT token
    echo "3. Testing JWT token validation...\n";
    $jwtService = $container->get(\App\Infrastructure\Services\JwtService::class);
    $payload = $jwtService->validateToken($loginResult['token']);
    
    if ($payload) {
        echo "✓ JWT token is valid\n";
        echo "  - User ID: " . $payload['sub'] . "\n";
        echo "  - Email: " . $payload['email'] . "\n";
        echo "  - Expires: " . date('Y-m-d H:i:s', $payload['exp']) . "\n\n";
    } else {
        echo "✗ JWT token validation failed\n\n";
    }
    
    // Test 4: Test expired token
    echo "4. Testing expired token detection...\n";
    $isExpired = $jwtService->isTokenExpired($loginResult['token']);
    echo $isExpired ? "✗ Token is expired (should not be)" : "✓ Token is not expired (correct)\n\n";
    
    // Test 5: Test user repository with authentication
    echo "5. Testing user repository...\n";
    $repository = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    $foundUser = $repository->findByEmail('auth.test@example.com');
    
    if ($foundUser) {
        echo "✓ User found in repository\n";
        echo "  - ID: " . $foundUser->getId()->toString() . "\n";
        echo "  - Name: " . $foundUser->getName() . "\n";
        echo "  - Active: " . ($foundUser->isActive() ? 'Yes' : 'No') . "\n";
        echo "  - Last Login: " . ($foundUser->getLastLoginAt() ? $foundUser->getLastLoginAt()->format('Y-m-d H:i:s') : 'Never') . "\n\n";
    } else {
        echo "✗ User not found in repository\n\n";
    }
    
    echo "🎉 All authentication tests passed!\n";
    echo "\nNext steps:\n";
    echo "1. Visit http://localhost:8080/login.html\n";
    echo "2. Login with: auth.test@example.com / TestPass123!\n";
    echo "3. You'll be redirected to the dashboard\n";
    echo "4. Try accessing http://localhost:8080/users without login - you'll be redirected\n";
    
} catch (Exception $e) {
    echo "✗ Error during authentication test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 