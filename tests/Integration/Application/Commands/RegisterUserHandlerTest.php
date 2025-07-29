<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands;

use Tests\TestCase;
use App\Application\Commands\RegisterUserCommand;
use App\Application\Commands\RegisterUserHandler;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use App\Domain\Repositories\UserRepositoryInterface;
use InvalidArgumentException;

class RegisterUserHandlerTest extends TestCase
{
    private RegisterUserHandler $handler;
    private UserRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->handler = $this->container->get(RegisterUserHandler::class);
        $this->repository = $this->container->get(UserRepositoryInterface::class);
    }

    public function testRegisterNewUser(): void
    {
        $command = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );

        $user = $this->handler->__invoke($command);

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail()->value());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getLastLoginAt());

        // Verify user was saved to database
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    public function testRegisterUserWithDuplicateEmailThrowsException(): void
    {
        // Register first user
        $command1 = new RegisterUserCommand(
            'First User',
            new Email('duplicate@example.com'),
            new Password('TestPass123!')
        );
        $this->handler->__invoke($command1);

        // Try to register second user with same email
        $command2 = new RegisterUserCommand(
            'Second User',
            new Email('duplicate@example.com'),
            new Password('TestPass123!')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User with this email already exists');

        $this->handler->__invoke($command2);
    }

    public function testRegisterMultipleUsers(): void
    {
        $users = [
            ['name' => 'User 1', 'email' => 'user1@example.com'],
            ['name' => 'User 2', 'email' => 'user2@example.com'],
            ['name' => 'User 3', 'email' => 'user3@example.com'],
        ];

        foreach ($users as $userData) {
            $command = new RegisterUserCommand(
                $userData['name'],
                new Email($userData['email']),
                new Password('TestPass123!')
            );

            $user = $this->handler->__invoke($command);
            $this->assertEquals($userData['name'], $user->getName());
            $this->assertEquals($userData['email'], $user->getEmail()->value());
        }

        // Verify all users were saved
        $this->assertDatabaseCount('users', 3);
    }

    public function testRegisterUserWithComplexEmail(): void
    {
        $command = new RegisterUserCommand(
            'Complex User',
            new Email('user+tag@example-domain.co.uk'),
            new Password('TestPass123!')
        );

        $user = $this->handler->__invoke($command);

        $this->assertEquals('user+tag@example-domain.co.uk', $user->getEmail()->value());
        
        // Verify in database
        $this->assertDatabaseHas('users', [
            'email' => 'user+tag@example-domain.co.uk'
        ]);
    }

    public function testRegisterUserWithStrongPassword(): void
    {
        $command = new RegisterUserCommand(
            'Strong Password User',
            new Email('strong@example.com'),
            new Password('VeryStrongPass123!@#')
        );

        $user = $this->handler->__invoke($command);

        $this->assertTrue($user->verifyPassword('VeryStrongPass123!@#'));
        $this->assertFalse($user->verifyPassword('WrongPassword'));
    }

    public function testRegisterUserPersistsCorrectly(): void
    {
        $command = new RegisterUserCommand(
            'Persistent User',
            new Email('persistent@example.com'),
            new Password('TestPass123!')
        );

        $user = $this->handler->__invoke($command);
        $userId = $user->getId();

        // Retrieve user from repository to verify persistence
        $retrievedUser = $this->repository->findById($userId);

        $this->assertNotNull($retrievedUser);
        $this->assertEquals($user->getName(), $retrievedUser->getName());
        $this->assertEquals($user->getEmail()->value(), $retrievedUser->getEmail()->value());
        $this->assertEquals($user->getPasswordHash(), $retrievedUser->getPasswordHash());
        $this->assertEquals($user->isActive(), $retrievedUser->isActive());
    }

    public function testRegisterUserWithSpecialCharacters(): void
    {
        $command = new RegisterUserCommand(
            'José María O\'Connor',
            new Email('jose.maria@example.com'),
            new Password('TestPass123!')
        );

        $user = $this->handler->__invoke($command);

        $this->assertEquals('José María O\'Connor', $user->getName());
        
        // Verify in database
        $this->assertDatabaseHas('users', [
            'name' => 'José María O\'Connor',
            'email' => 'jose.maria@example.com'
        ]);
    }
} 