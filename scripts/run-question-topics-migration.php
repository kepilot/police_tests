<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Running Question Topics Migration ===\n";

try {
    $dbConnection = new DatabaseConnection();
    $pdo = $dbConnection->getConnection();
    
    echo "✅ Database connection successful\n";
    
    // Check if question_topics table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'question_topics'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Question_topics table already exists, skipping...\n";
        exit(0);
    }
    
    // Read and execute the migration
    $migrationPath = __DIR__ . '/../database/migrations/007_create_question_topics_table.sql';
    $sql = file_get_contents($migrationPath);
    
    if ($sql === false) {
        throw new Exception("Could not read migration file: $migrationPath");
    }
    
    $pdo->exec($sql);
    echo "✅ Question_topics table created successfully\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 