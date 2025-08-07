<?php

require_once __DIR__ . '/../vendor/autoload.php';

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
use App\Application\Commands\CreateUserCommand;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

echo "=== Create Default Admin User ===\n\n";

try {
    $container = new Container();
    
    // Default admin user details
    $name = "Admin User";
    $email = "admin@learningportal.com";
    $password = "Admin123!";
    $role = "superadmin";
    
    echo "Creating admin user with default credentials:\n";
    echo "  Name: " . $name . "\n";
    echo "  Email: " . $email . "\n";
    echo "  Password: " . $password . "\n";
    echo "  Role: " . $role . "\n\n";
    
    // Create user command
    $command = new CreateUserCommand(
        $name,
        new Email($email),
        new Password($password),
        $role
    );
    
    $handler = $container->get(\App\Application\Commands\CreateUserHandler::class);
    $user = $handler($command);
    
    echo "✅ Admin user created successfully!\n\n";
    echo "User Details:\n";
    echo "  ID: " . $user->getId()->toString() . "\n";
    echo "  Name: " . $user->getName() . "\n";
    echo "  Email: " . $user->getEmail()->value() . "\n";
    echo "  Role: " . $user->getRole() . "\n";
    echo "  Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
    echo "  Is Super Admin: " . ($user->isSuperAdmin() ? 'Yes' : 'No') . "\n";
    echo "  Active: " . ($user->isActive() ? 'Yes' : 'No') . "\n";
    echo "  Created: " . $user->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    
    echo "\nYou can now log in with:\n";
    echo "  Email: " . $email . "\n";
    echo "  Password: " . $password . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 