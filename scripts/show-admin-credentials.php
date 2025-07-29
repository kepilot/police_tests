<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;

echo "=== Admin User Credentials ===\n\n";

try {
    $container = new Container();
    $userRepository = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    
    // Find admin users
    $allUsers = $userRepository->findAll();
    $adminUsers = array_filter($allUsers, fn($user) => $user->getRole() === 'admin');
    $superAdminUsers = array_filter($allUsers, fn($user) => $user->getRole() === 'superadmin');
    
    $allAdminUsers = array_merge($adminUsers, $superAdminUsers);
    
    if (empty($allAdminUsers)) {
        echo "❌ No admin users found in the database.\n";
        echo "Run 'php scripts/create-default-admin.php' to create one.\n";
        exit(1);
    }
    
    echo "Found " . count($allAdminUsers) . " admin user(s):\n\n";
    
    foreach ($allAdminUsers as $user) {
        echo "👤 User Details:\n";
        echo "  ID: " . $user->getId()->toString() . "\n";
        echo "  Name: " . $user->getName() . "\n";
        echo "  Email: " . $user->getEmail()->value() . "\n";
        echo "  Role: " . $user->getRole() . "\n";
        echo "  Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
        echo "  Is Super Admin: " . ($user->isSuperAdmin() ? 'Yes' : 'No') . "\n";
        echo "  Active: " . ($user->isActive() ? 'Yes' : 'No') . "\n";
        echo "  Created: " . $user->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
        echo "\n";
    }
    
    // Show default credentials for the superadmin
    $superAdmin = $superAdminUsers[0] ?? $adminUsers[0];
    echo "🔑 Login Credentials:\n";
    echo "  Email: " . $superAdmin->getEmail()->value() . "\n";
    echo "  Password: Admin123! (default password)\n\n";
    
    echo "🌐 Access URLs:\n";
    echo "  Login Page: http://localhost:8080/login.html\n";
    echo "  Dashboard: http://localhost:8080/dashboard.html\n";
    echo "  API Base: http://localhost:8080\n";
    echo "  PHPMyAdmin: http://localhost:8081\n\n";
    
    echo "📝 Note: If the password doesn't work, you may need to reset it or create a new admin user.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 