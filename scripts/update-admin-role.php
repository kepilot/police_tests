<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Update Admin Role ===\n\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Update the admin user's role to superadmin
    $sql = "UPDATE users SET role = 'superadmin' WHERE email = 'admin@learningportal.com'";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Admin user role updated to superadmin\n";
        
        // Verify the update
        $sql = "SELECT name, email, role FROM users WHERE email = 'admin@learningportal.com'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "\nUser details:\n";
            echo "  Name: " . $user['name'] . "\n";
            echo "  Email: " . $user['email'] . "\n";
            echo "  Role: " . $user['role'] . "\n";
        }
    } else {
        echo "❌ Failed to update admin user role\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 