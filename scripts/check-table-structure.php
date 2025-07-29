<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Checking Table Structure ===\n\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Check users table structure
    echo "Users table structure:\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")";
        if ($column['Key'] === 'PRI') {
            echo " PRIMARY KEY";
        }
        echo "\n";
    }
    
    echo "\nExams table structure:\n";
    $stmt = $pdo->query("DESCRIBE exams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")";
        if ($column['Key'] === 'PRI') {
            echo " PRIMARY KEY";
        }
        echo "\n";
    }
    
    echo "\nExam_attempts table structure:\n";
    $stmt = $pdo->query("DESCRIBE exam_attempts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")";
        if ($column['Key'] === 'PRI') {
            echo " PRIMARY KEY";
        }
        echo "\n";
    }
    
    // Check foreign keys
    echo "\nForeign keys:\n";
    $stmt = $pdo->query("SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = 'ddd_db' 
        AND REFERENCED_TABLE_NAME IS NOT NULL");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($foreignKeys)) {
        echo "  No foreign keys found\n";
    } else {
        foreach ($foreignKeys as $fk) {
            echo "  - " . $fk['TABLE_NAME'] . "." . $fk['COLUMN_NAME'] . " -> " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 