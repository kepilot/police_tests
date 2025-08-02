<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Override environment variables for local testing
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'ddd_db';
$_ENV['DB_USER'] = 'ddd_user';
$_ENV['DB_PASSWORD'] = 'secret';

echo "Resetting database locally...\n";

try {
    // Connect without specifying database
    $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']}";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to MySQL server\n";
    
    // Drop database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS {$_ENV['DB_NAME']}");
    echo "✓ Dropped existing database\n";
    
    // Create database
    $pdo->exec("CREATE DATABASE {$_ENV['DB_NAME']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Created new database\n";
    
    // Connect to the new database
    $pdo->exec("USE {$_ENV['DB_NAME']}");
    echo "✓ Connected to new database\n";
    
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
    
    echo "\nDatabase reset completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error resetting database: " . $e->getMessage() . "\n";
    exit(1);
} 