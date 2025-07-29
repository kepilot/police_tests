<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Application\Commands\RegisterUserCommand;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

function generateSecurePassword(): string
{
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $password = '';
    
    // Ensure at least one character from each category
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Fill the rest with random characters
    $allChars = $uppercase . $lowercase . $numbers . $special;
    for ($i = 4; $i < 16; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Shuffle the password to make it more random
    return str_shuffle($password);
}

try {
    echo "Creating user with email: kepilot1@gmail.com\n\n";
    
    $container = new Container();
    $registerHandler = $container->get(\App\Application\Commands\RegisterUserHandler::class);
    
    // Generate a secure random password
    $password = generateSecurePassword();
    
    echo "Generated password: {$password}\n\n";
    
    // Create the user
    $command = new RegisterUserCommand(
        'KePilot User',
        new Email('kepilot1@gmail.com'),
        new Password($password)
    );
    
    $user = $registerHandler($command);
    
    echo "✅ User created successfully!\n\n";
    echo "User Details:\n";
    echo "- ID: " . $user->getId()->toString() . "\n";
    echo "- Name: " . $user->getName() . "\n";
    echo "- Email: " . $user->getEmail()->value() . "\n";
    echo "- Active: " . ($user->isActive() ? 'Yes' : 'No') . "\n";
    echo "- Created: " . $user->getCreatedAt()->format('Y-m-d H:i:s') . "\n\n";
    
    echo "🔐 Login Credentials:\n";
    echo "- Email: kepilot1@gmail.com\n";
    echo "- Password: {$password}\n\n";
    
    echo "🌐 You can now login at: http://localhost:8080/login.html\n";
    echo "📊 Or access the dashboard at: http://localhost:8080/dashboard.html\n\n";
    
    echo "⚠️  IMPORTANT: Save this password securely! It won't be shown again.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating user: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "\n💡 The user already exists. You can try logging in with the existing password.\n";
        echo "🌐 Login at: http://localhost:8080/login.html\n";
    }
    
    exit(1);
} 