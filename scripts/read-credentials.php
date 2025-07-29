<?php

/**
 * Read Local Credentials File
 * 
 * This script reads and displays credentials from the local credentials file.
 */

echo "=== Reading Local Credentials ===\n\n";

$filename = __DIR__ . '/../credentials.local.json';

if (!file_exists($filename)) {
    echo "❌ Credentials file not found: credentials.local.json\n";
    echo "Run 'php scripts/create-credentials-file.php' to create it.\n";
    exit(1);
}

try {
    $content = file_get_contents($filename);
    $credentials = json_decode($content, true);
    
    if (!$credentials) {
        throw new Exception("Invalid JSON in credentials file");
    }
    
    echo "✅ Credentials file loaded successfully\n\n";
    
    // Display admin users
    if (isset($credentials['admin_users']) && !empty($credentials['admin_users'])) {
        echo "👤 Admin Users:\n";
        foreach ($credentials['admin_users'] as $user) {
            echo "  Name: " . $user['name'] . "\n";
            echo "  Email: " . $user['email'] . "\n";
            echo "  Password: " . $user['password'] . "\n";
            echo "  Role: " . $user['role'] . "\n";
            echo "  User ID: " . $user['user_id'] . "\n";
            echo "  Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
            echo "  Created: " . $user['created_at'] . "\n\n";
        }
    }
    
    // Display URLs
    if (isset($credentials['urls'])) {
        echo "🌐 Access URLs:\n";
        foreach ($credentials['urls'] as $name => $url) {
            echo "  " . ucfirst(str_replace('_', ' ', $name)) . ": " . $url . "\n";
        }
        echo "\n";
    }
    
    // Display database info
    if (isset($credentials['database'])) {
        echo "🗄️  Database Connection:\n";
        $db = $credentials['database'];
        echo "  Host: " . $db['host'] . "\n";
        echo "  Port: " . $db['port'] . "\n";
        echo "  Database: " . $db['database'] . "\n";
        echo "  Username: " . $db['username'] . "\n";
        echo "  Password: " . $db['password'] . "\n\n";
    }
    
    // Display security info
    if (isset($credentials['security'])) {
        echo "🔒 Security Information:\n";
        echo "  Warning: " . $credentials['security']['warning'] . "\n";
        echo "  Recommendations:\n";
        foreach ($credentials['security']['recommendations'] as $rec) {
            echo "    - " . $rec . "\n";
        }
        echo "\n";
    }
    
    echo "📅 Last Updated: " . ($credentials['last_updated'] ?? 'Unknown') . "\n";
    echo "🌍 Environment: " . ($credentials['environment'] ?? 'Unknown') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error reading credentials file: " . $e->getMessage() . "\n";
    exit(1);
} 