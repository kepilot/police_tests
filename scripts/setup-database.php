<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "Setting up database...\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✓ Connected to database successfully\n";
    
    // Read and execute the migration
    $migrationPath = __DIR__ . '/../database/migrations/001_create_users_table.sql';
    $sql = file_get_contents($migrationPath);
    
    if ($sql === false) {
        throw new RuntimeException("Could not read migration file: $migrationPath");
    }
    
    $pdo->exec($sql);
    
    echo "✓ Users table created successfully\n";
    
    // Verify the table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Users table verified\n";
    } else {
        echo "✗ Users table not found after creation\n";
        exit(1);
    }
    
    echo "\nDatabase setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
} 