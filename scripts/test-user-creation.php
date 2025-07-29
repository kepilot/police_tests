<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\Commands\CreateUserCommand;
use App\Application\Commands\CreateUserHandler;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use App\Infrastructure\Container\Container;

echo "Testing user creation...\n";

try {
    $container = new Container();
    $handler = $container->get(CreateUserHandler::class);
    
    // Test data
    $testName = 'John Doe';
    $testEmail = 'john.doe@example.com';
    
    echo "Creating user: $testName ($testEmail)\n";
    
    $command = new CreateUserCommand($testName, new Email($testEmail));
    $handler($command);
    
    echo "✓ User created successfully!\n";
    
    // Verify user was saved by trying to find it
    $repository = $container->get(UserRepositoryInterface::class);
    $user = $repository->findByEmail($testEmail);
    
    if ($user) {
        echo "✓ User found in database:\n";
        echo "  - ID: " . $user->getId()->toString() . "\n";
        echo "  - Name: " . $user->getName() . "\n";
        echo "  - Email: " . $user->getEmail()->value() . "\n";
        echo "  - Created: " . $user->getCreatedAt()->format('Y-m-d H:i:s') . "\n";
    } else {
        echo "✗ User not found in database after creation\n";
        exit(1);
    }
    
    echo "\nTest completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 