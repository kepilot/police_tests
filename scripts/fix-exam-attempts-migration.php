<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Fixing Exam Attempts Migration ===\n\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Check if exam_attempts table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'exam_attempts'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Exam_attempts table already exists\n";
        exit(0);
    }
    
    // Check if users and exams tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Users table does not exist\n";
        exit(1);
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'exams'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Exams table does not exist\n";
        exit(1);
    }
    
    echo "✅ Users and exams tables exist\n";
    
    // Check column types
    echo "\nChecking column types...\n";
    
    $stmt = $pdo->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($userColumns as $column) {
        if ($column['Field'] === 'id') {
            echo "Users.id: " . $column['Type'] . "\n";
            break;
        }
    }
    
    $stmt = $pdo->query("DESCRIBE exams");
    $examColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($examColumns as $column) {
        if ($column['Field'] === 'id') {
            echo "Exams.id: " . $column['Type'] . "\n";
            break;
        }
    }
    
    // Create exam_attempts table without foreign keys first
    echo "\nCreating exam_attempts table without foreign keys...\n";
    
    $sql = "CREATE TABLE exam_attempts (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        exam_id VARCHAR(36) NOT NULL,
        score INT DEFAULT 0 NOT NULL,
        passed BOOLEAN DEFAULT FALSE NOT NULL,
        started_at DATETIME NOT NULL,
        completed_at DATETIME NULL,
        deleted_at DATETIME NULL
    )";
    
    $pdo->exec($sql);
    echo "✅ Created exam_attempts table without foreign keys\n";
    
    // Add foreign keys separately
    echo "\nAdding foreign keys...\n";
    
    try {
        $pdo->exec("ALTER TABLE exam_attempts ADD CONSTRAINT fk_exam_attempts_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
        echo "✅ Added foreign key for user_id\n";
    } catch (Exception $e) {
        echo "⚠️  Could not add foreign key for user_id: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE exam_attempts ADD CONSTRAINT fk_exam_attempts_exam_id FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE");
        echo "✅ Added foreign key for exam_id\n";
    } catch (Exception $e) {
        echo "⚠️  Could not add foreign key for exam_id: " . $e->getMessage() . "\n";
    }
    
    // Add indexes
    echo "\nAdding indexes...\n";
    
    $pdo->exec("CREATE INDEX idx_exam_attempts_user_id ON exam_attempts(user_id)");
    $pdo->exec("CREATE INDEX idx_exam_attempts_exam_id ON exam_attempts(exam_id)");
    $pdo->exec("CREATE INDEX idx_exam_attempts_started_at ON exam_attempts(started_at)");
    $pdo->exec("CREATE INDEX idx_exam_attempts_completed_at ON exam_attempts(completed_at)");
    $pdo->exec("CREATE INDEX idx_exam_attempts_passed ON exam_attempts(passed)");
    $pdo->exec("CREATE INDEX idx_exam_attempts_deleted_at ON exam_attempts(deleted_at)");
    
    echo "✅ Created all indexes for exam_attempts table\n";
    
    echo "\n=== Exam Attempts Migration Fixed Successfully! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 