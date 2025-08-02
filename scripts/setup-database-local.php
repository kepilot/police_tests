<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Override environment variables for local testing
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'ddd_db';
$_ENV['DB_USER'] = 'ddd_user';
$_ENV['DB_PASSWORD'] = 'secret';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "Setting up database locally...\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✓ Connected to database successfully\n";
    
    // Read and execute all migrations
    $migrationsDir = __DIR__ . '/../database/migrations/';
    $migrationFiles = glob($migrationsDir . '*.sql');
    sort($migrationFiles); // Execute in order
    
    foreach ($migrationFiles as $migrationFile) {
        $filename = basename($migrationFile);
        echo "Executing migration: $filename\n";
        
        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration file: $migrationFile");
        }
        
        $pdo->exec($sql);
        echo "✓ Migration $filename completed\n";
    }
    
    echo "\n✓ All migrations completed successfully!\n";
    
    // Verify tables exist
    $expectedTables = ['users', 'topics', 'exams', 'questions', 'exam_attempts', 'question_topics', 'exam_assignments'];
    foreach ($expectedTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' not found\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
} 