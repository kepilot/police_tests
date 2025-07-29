<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Commands;

use Tests\TestCase;
use App\Application\Commands\LoginUserCommand;
use App\Application\Commands\LoginUserHandler;
use App\Application\Commands\RegisterUserCommand;
use App\Application\Commands\RegisterUserHandler;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use App\Infrastructure\Services\JwtService;
use InvalidArgumentException;

class LoginUserHandlerTest extends TestCase
{
    private LoginUserHandler $loginHandler;
    private RegisterUserHandler $registerHandler;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loginHandler = $this->container->get(LoginUserHandler::class);
        $this->registerHandler = $this->container->get(RegisterUserHandler::class);
        $this->jwtService = $this->container->get(JwtService::class);
    }

    public function testSuccessfulLogin(): void
    {
        // Register a user first
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $user = $this->registerHandler->__invoke($registerCommand);

        // Login with correct credentials
        $loginCommand = new LoginUserCommand(
            new Email('test@example.com'),
            'TestPass123!'
        );

        $result = $this->loginHandler->__invoke($loginCommand);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expiresIn', $result);

        // Verify user data
        $this->assertEquals($user->getId()->toString(), $result['user']['id']);
        $this->assertEquals('Test User', $result['user']['name']);
        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertNotNull($result['user']['lastLoginAt']);

        // Verify token
        $this->assertNotEmpty($result['token']);
        $this->assertEquals(3600, $result['expiresIn']);

        // Verify JWT token is valid
        $payload = $this->jwtService->validateToken($result['token']);
        $this->assertNotNull($payload);
        $this->assertEquals($user->getId()->toString(), $payload['sub']);
        $this->assertEquals('test@example.com', $payload['email']);
        $this->assertEquals('Test User', $payload['name']);
    }

    public function testLoginWithInvalidEmailThrowsException(): void
    {
        $loginCommand = new LoginUserCommand(
            new Email('nonexistent@example.com'),
            'TestPass123!'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email or password');

        $this->loginHandler->__invoke($loginCommand);
    }

    public function testLoginWithInvalidPasswordThrowsException(): void
    {
        // Register a user first
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $this->registerHandler->__invoke($registerCommand);

        // Try to login with wrong password
        $loginCommand = new LoginUserCommand(
            new Email('test@example.com'),
            'WrongPassword'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email or password');

        $this->loginHandler->__invoke($loginCommand);
    }

    public function testLoginWithDeactivatedUserThrowsException(): void
    {
        // Register a user first
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $user = $this->registerHandler->__invoke($registerCommand);

        // Deactivate the user
        $user->deactivate();
        $repository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
        $repository->save($user);

        // Try to login with deactivated user
        $loginCommand = new LoginUserCommand(
            new Email('test@example.com'),
            'TestPass123!'
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Account is deactivated');

        $this->loginHandler->__invoke($loginCommand);
    }

    public function testLoginUpdatesLastLoginAt(): void
    {
        // Register a user first
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $user = $this->registerHandler->__invoke($registerCommand);

        $originalLastLoginAt = $user->getLastLoginAt();
        $this->assertNull($originalLastLoginAt);

        // Login
        $loginCommand = new LoginUserCommand(
            new Email('test@example.com'),
            'TestPass123!'
        );

        $result = $this->loginHandler->__invoke($loginCommand);

        // Verify last login was updated
        $this->assertNotNull($result['user']['lastLoginAt']);
        
        // Verify in database
        $repository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
        $updatedUser = $repository->findById($user->getId());
        $this->assertNotNull($updatedUser->getLastLoginAt());
    }

    public function testLoginWithCaseInsensitiveEmail(): void
    {
        // Register a user with lowercase email
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $this->registerHandler->__invoke($registerCommand);

        // Login with uppercase email
        $loginCommand = new LoginUserCommand(
            new Email('TEST@EXAMPLE.COM'),
            'TestPass123!'
        );

        $result = $this->loginHandler->__invoke($loginCommand);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
    }

    public function testMultipleLoginsGenerateDifferentTokens(): void
    {
        // Register a user first
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('TestPass123!')
        );
        $this->registerHandler->__invoke($registerCommand);

        // First login
        $loginCommand1 = new LoginUserCommand(
            new Email('test@example.com'),
            'TestPass123!'
        );
        $result1 = $this->loginHandler->__invoke($loginCommand1);

        // Second login
        $loginCommand2 = new LoginUserCommand(
            new Email('test@example.com'),
            'TestPass123!'
        );
        $result2 = $this->loginHandler->__invoke($loginCommand2);

        // Tokens should be different (due to different timestamps)
        $this->assertNotEquals($result1['token'], $result2['token']);

        // Both tokens should be valid
        $payload1 = $this->jwtService->validateToken($result1['token']);
        $payload2 = $this->jwtService->validateToken($result2['token']);

        $this->assertNotNull($payload1);
        $this->assertNotNull($payload2);
    }

    public function testLoginWithComplexPassword(): void
    {
        // Register a user with complex password
        $registerCommand = new RegisterUserCommand(
            'Test User',
            new Email('test@example.com'),
            new Password('VeryComplexPass123!@#$%')
        );
        $this->registerHandler->__invoke($registerCommand);

        // Login with complex password
        $loginCommand = new LoginUserCommand(
            new Email('test@example.com'),
            'VeryComplexPass123!@#$%'
        );

        $result = $this->loginHandler->__invoke($loginCommand);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
    }
} 