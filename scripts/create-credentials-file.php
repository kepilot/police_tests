<?php

/**
 * Create Local Credentials File
 * 
 * This script creates a local credentials file that will be ignored by git.
 * Run this script to generate your local credentials file.
 */

echo "=== Creating Local Credentials File ===\n\n";

$credentials = [
    "admin_users" => [
        [
            "name" => "Admin User",
            "email" => "admin@learningportal.com",
            "password" => "Admin123!",
            "role" => "superadmin",
            "user_id" => "e358b02e-30dd-4b3c-8b22-0538357c3739",
            "created_at" => "2025-07-29 10:19:40",
            "is_active" => true
        ]
    ],
    "database" => [
        "host" => "localhost",
        "port" => 3306,
        "database" => "ddd_db",
        "username" => "ddd_user",
        "password" => "secret"
    ],
    "urls" => [
        "login_page" => "http://localhost:8080/login.html",
        "dashboard" => "http://localhost:8080/dashboard.html",
        "api_base" => "http://localhost:8080",
        "phpmyadmin" => "http://localhost:8081"
    ],
    "security" => [
        "warning" => "This file contains sensitive information and should NEVER be committed to version control",
        "recommendations" => [
            "Change default passwords in production",
            "Use environment variables for production credentials",
            "Rotate credentials regularly",
            "Limit access to this file"
        ]
    ],
    "last_updated" => date('Y-m-d'),
    "environment" => "development"
];

$filename = __DIR__ . '/../credentials.local.json';
$content = json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (file_put_contents($filename, $content)) {
    echo "✅ Credentials file created successfully: credentials.local.json\n\n";
    echo "📁 File location: " . realpath($filename) . "\n";
    echo "🔒 This file is already in .gitignore and will NOT be committed\n\n";
    
    echo "📋 Credentials Summary:\n";
    echo "  Email: admin@learningportal.com\n";
    echo "  Password: Admin123!\n";
    echo "  Role: superadmin\n";
    echo "  Login URL: http://localhost:8080/login.html\n\n";
    
    echo "⚠️  Security Reminders:\n";
    echo "  - Never commit this file to version control\n";
    echo "  - Change passwords in production\n";
    echo "  - Use environment variables for production\n";
    echo "  - Keep this file secure and limit access\n";
} else {
    echo "❌ Error: Could not create credentials file\n";
    echo "Check file permissions and try again.\n";
} 