<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Running Exam Assignment Migration ===\n\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Check if exam_assignments table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'exam_assignments'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Exam_assignments table already exists, skipping...\n";
    } else {
        // Read and execute the migration
        $migrationFile = __DIR__ . '/../database/migrations/008_create_exam_assignments_table.sql';
        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: $migrationFile");
        }
        
        $sql = file_get_contents($migrationFile);
        $pdo->exec($sql);
        echo "✅ Created exam_assignments table\n";
    }
    
    echo "\n=== Migration Completed Successfully! ===\n";
    
    // Show final table structure
    echo "\nFinal database structure:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  ✅ " . $table . "\n";
    }
    
    echo "\nYou can now:\n";
    echo "  1. Test exam assignment functionality: php scripts/test-exam-assignment.php\n";
    echo "  2. Create a mock test: php scripts/create-mock-test.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 