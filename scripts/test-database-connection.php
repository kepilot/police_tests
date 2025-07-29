<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\DatabaseConnection;

echo "=== Database Connection Test ===\n\n";

try {
    // Test database connection
    echo "Testing database connection...\n";
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection successful\n";
    
    // Test if users table exists
    echo "\nChecking if users table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Users table exists\n";
        
        // Check table structure
        echo "\nChecking users table structure...\n";
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current columns in users table:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Check if role column already exists
        $roleColumnExists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'role') {
                $roleColumnExists = true;
                break;
            }
        }
        
        if ($roleColumnExists) {
            echo "\n⚠️  Role column already exists in users table\n";
        } else {
            echo "\n✅ Role column does not exist - migration can be applied\n";
        }
        
        // Count existing users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\nCurrent users in database: " . $userCount . "\n";
        
    } else {
        echo "❌ Users table does not exist\n";
        echo "You need to run the first migration first:\n";
        echo "mysql -u your_user -p your_database < database/migrations/001_create_users_table.sql\n";
    }
    
    // Test if we can read migration files
    echo "\nChecking migration files...\n";
    $migrationFiles = [
        '001_create_users_table.sql',
        '002_add_role_to_users_table.sql',
        '003_create_topics_table.sql',
        '004_create_exams_table.sql',
        '005_create_questions_table.sql',
        '006_create_exam_attempts_table.sql'
    ];
    
    foreach ($migrationFiles as $file) {
        $filePath = __DIR__ . '/../database/migrations/' . $file;
        if (file_exists($filePath)) {
            echo "✅ " . $file . " exists\n";
        } else {
            echo "❌ " . $file . " missing\n";
        }
    }
    
    echo "\n=== Test Summary ===\n";
    if ($tableExists && !$roleColumnExists) {
        echo "✅ Ready to run migrations\n";
        echo "Next step: Run the role migration\n";
    } elseif ($tableExists && $roleColumnExists) {
        echo "✅ Role column already exists\n";
        echo "Next step: Run remaining migrations\n";
    } else {
        echo "❌ Need to create users table first\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Check your .env file configuration\n";
    echo "2. Ensure MySQL server is running\n";
    echo "3. Verify database credentials\n";
    echo "4. Check if database exists\n";
} 