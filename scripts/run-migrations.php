<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Running Database Migrations ===\n\n";

try {
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    // Migration 002: Add role column to users table
    echo "Running migration 002: Add role column to users table...\n";
    
    // Check if role column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Role column already exists, skipping...\n";
    } else {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash");
        echo "✅ Added role column to users table\n";
        
        $pdo->exec("CREATE INDEX idx_users_role ON users(role)");
        echo "✅ Created index on role column\n";
        
        $pdo->exec("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
        echo "✅ Updated existing users with default role\n";
    }
    
    // Migration 003: Create topics table
    echo "\nRunning migration 003: Create topics table...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'topics'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Topics table already exists, skipping...\n";
    } else {
        $sql = "CREATE TABLE topics (
            id VARCHAR(36) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
            is_active BOOLEAN DEFAULT TRUE NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )";
        $pdo->exec($sql);
        echo "✅ Created topics table\n";
        
        // Add indexes
        $pdo->exec("CREATE INDEX idx_topics_level ON topics(level)");
        $pdo->exec("CREATE INDEX idx_topics_active ON topics(is_active)");
        $pdo->exec("CREATE INDEX idx_topics_created_at ON topics(created_at)");
        $pdo->exec("CREATE INDEX idx_topics_deleted_at ON topics(deleted_at)");
        echo "✅ Created indexes for topics table\n";
    }
    
    // Migration 004: Create exams table
    echo "\nRunning migration 004: Create exams table...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'exams'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Exams table already exists, skipping...\n";
    } else {
        $sql = "CREATE TABLE exams (
            id VARCHAR(36) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            duration_minutes INT NOT NULL,
            passing_score_percentage INT NOT NULL,
            topic_id VARCHAR(36) NOT NULL,
            is_active BOOLEAN DEFAULT TRUE NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        echo "✅ Created exams table\n";
        
        // Add indexes
        $pdo->exec("CREATE INDEX idx_exams_topic_id ON exams(topic_id)");
        $pdo->exec("CREATE INDEX idx_exams_active ON exams(is_active)");
        $pdo->exec("CREATE INDEX idx_exams_created_at ON exams(created_at)");
        $pdo->exec("CREATE INDEX idx_exams_deleted_at ON exams(deleted_at)");
        echo "✅ Created indexes for exams table\n";
    }
    
    // Migration 005: Create questions table
    echo "\nRunning migration 005: Create questions table...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'questions'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Questions table already exists, skipping...\n";
    } else {
        $sql = "CREATE TABLE questions (
            id VARCHAR(36) PRIMARY KEY,
            text TEXT NOT NULL,
            type ENUM('multiple_choice', 'true_false', 'single_choice') NOT NULL,
            exam_id VARCHAR(36) NOT NULL,
            options JSON NOT NULL,
            correct_option INT NOT NULL,
            points INT DEFAULT 1 NOT NULL,
            is_active BOOLEAN DEFAULT TRUE NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        echo "✅ Created questions table\n";
        
        // Add indexes
        $pdo->exec("CREATE INDEX idx_questions_exam_id ON questions(exam_id)");
        $pdo->exec("CREATE INDEX idx_questions_active ON questions(is_active)");
        $pdo->exec("CREATE INDEX idx_questions_type ON questions(type)");
        $pdo->exec("CREATE INDEX idx_questions_created_at ON questions(created_at)");
        $pdo->exec("CREATE INDEX idx_questions_deleted_at ON questions(deleted_at)");
        echo "✅ Created indexes for questions table\n";
    }
    
    // Migration 006: Create exam_attempts table
    echo "\nRunning migration 006: Create exam_attempts table...\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'exam_attempts'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Exam_attempts table already exists, skipping...\n";
    } else {
        $sql = "CREATE TABLE exam_attempts (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            exam_id VARCHAR(36) NOT NULL,
            score INT DEFAULT 0 NOT NULL,
            passed BOOLEAN DEFAULT FALSE NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            deleted_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        echo "✅ Created exam_attempts table\n";
        
        // Add indexes
        $pdo->exec("CREATE INDEX idx_exam_attempts_user_id ON exam_attempts(user_id)");
        $pdo->exec("CREATE INDEX idx_exam_attempts_exam_id ON exam_attempts(exam_id)");
        $pdo->exec("CREATE INDEX idx_exam_attempts_started_at ON exam_attempts(started_at)");
        $pdo->exec("CREATE INDEX idx_exam_attempts_completed_at ON exam_attempts(completed_at)");
        $pdo->exec("CREATE INDEX idx_exam_attempts_passed ON exam_attempts(passed)");
        $pdo->exec("CREATE INDEX idx_exam_attempts_deleted_at ON exam_attempts(deleted_at)");
        echo "✅ Created indexes for exam_attempts table\n";
    }
    
    echo "\n=== All Migrations Completed Successfully! ===\n";
    
    // Show final table structure
    echo "\nFinal database structure:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  ✅ " . $table . "\n";
    }
    
    echo "\nYou can now:\n";
    echo "  1. Create an admin user: php scripts/create-admin-user.php\n";
    echo "  2. Test the learning portal: php scripts/test-learning-portal.php\n";
    echo "  3. Seed with sample data: php scripts/seed-learning-data.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 