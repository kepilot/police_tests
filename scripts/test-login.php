<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../env.local';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Infrastructure\Container\Container;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Services\JwtService;
use App\Application\Commands\LoginUserCommand;
use App\Application\Commands\LoginUserHandler;
use App\Domain\ValueObjects\Email;

try {
    echo "Testing login functionality...\n";
    
    // Initialize container
    $container = new Container();
    
    // Test database connection
    $db = $container->get(\App\Infrastructure\Persistence\DatabaseConnection::class);
    echo "Database connection: OK\n";
    
    // Test user repository
    $userRepo = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    echo "User repository: OK\n";
    
    // Test JWT service
    $jwtService = $container->get(JwtService::class);
    echo "JWT service: OK\n";
    
    // Test login handler
    $loginHandler = $container->get(LoginUserHandler::class);
    echo "Login handler: OK\n";
    
    // List all users
    $users = $userRepo->findAll();
    echo "\nAll users in database:\n";
    foreach ($users as $user) {
        echo "- ID: " . $user->getId()->toString() . "\n";
        echo "  Name: " . $user->getName() . "\n";
        echo "  Email: " . $user->getEmail()->value() . "\n";
        echo "  Role: " . $user->getRole() . "\n";
        echo "  Active: " . ($user->isActive() ? 'Yes' : 'No') . "\n";
        echo "  Password hash: " . substr($user->getPasswordHash(), 0, 20) . "...\n";
        echo "\n";
    }
    
    // Try to find a non-admin user
    $nonAdminUser = null;
    
    foreach ($users as $user) {
        if ($user->getRole() !== 'admin' && $user->getRole() !== 'superadmin') {
            $nonAdminUser = $user;
            break;
        }
    }
    
    if (!$nonAdminUser) {
        echo "No non-admin user found. Creating one...\n";
        
        // Create a non-admin user
        $nonAdminUser = new \App\Domain\Entities\User(
            'Test User',
            new Email('testuser@example.com'),
            password_hash('password123', PASSWORD_DEFAULT),
            'user'
        );
        
        $userRepo->save($nonAdminUser);
        echo "Created non-admin user: " . $nonAdminUser->getEmail()->value() . "\n";
    } else {
        echo "Found non-admin user: " . $nonAdminUser->getEmail()->value() . " (role: " . $nonAdminUser->getRole() . ")\n";
    }
    
    // Test password verification directly with the correct default password
    echo "\nTesting password verification...\n";
    $testPassword = 'defaultPassword123!';
    $passwordValid = $nonAdminUser->verifyPassword($testPassword);
    echo "Password verification result: " . ($passwordValid ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Test login with non-admin user
    echo "\nTesting login with non-admin user...\n";
    echo "Email: " . $nonAdminUser->getEmail()->value() . "\n";
    echo "Password: defaultPassword123!\n";
    
    try {
        $command = new LoginUserCommand(
            new Email($nonAdminUser->getEmail()->value()),
            'defaultPassword123!'
        );
        
        $result = $loginHandler($command);
        
        echo "Login successful!\n";
        echo "User ID: " . $result['user']['id'] . "\n";
        echo "User name: " . $result['user']['name'] . "\n";
        echo "User email: " . $result['user']['email'] . "\n";
        echo "Token generated: " . (isset($result['token']) ? 'YES' : 'NO') . "\n";
        
        // Test JWT token validation
        if (isset($result['token'])) {
            $payload = $jwtService->validateToken($result['token']);
            if ($payload) {
                echo "JWT token validation: OK\n";
                echo "JWT payload:\n";
                print_r($payload);
            } else {
                echo "JWT token validation: FAILED\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "Login failed: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 