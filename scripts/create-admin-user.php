<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateUserCommand;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

echo "=== Create Admin User ===\n\n";

try {
    $container = new Container();
    
    // Get user input
    echo "Enter admin user details:\n";
    
    $name = readline("Name: ");
    if (empty($name)) {
        $name = "Admin User";
    }
    
    $email = readline("Email: ");
    if (empty($email)) {
        $email = "admin@learningportal.com";
    }
    
    $password = readline("Password: ");
    if (empty($password)) {
        $password = "admin123";
    }
    
    $role = readline("Role (admin/superadmin) [admin]: ");
    if (empty($role)) {
        $role = "admin";
    }
    
    if (!in_array($role, ['admin', 'superadmin'])) {
        throw new InvalidArgumentException("Role must be 'admin' or 'superadmin'");
    }
    
    echo "\nCreating admin user...\n";
    
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
} 