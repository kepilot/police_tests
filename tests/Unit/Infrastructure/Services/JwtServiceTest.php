<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services;

use Tests\TestCase;
use App\Infrastructure\Services\JwtService;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use Ramsey\Uuid\Uuid;

class JwtServiceTest extends TestCase
{
    private JwtService $jwtService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->jwtService = new JwtService();
        
        // Create a test user
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $this->user = new User('Test User', $email, $password->getHash());
    }

    public function testGenerateToken(): void
    {
        $token = $this->jwtService->generateToken($this->user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Token should be a valid JWT format (3 parts separated by dots)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testValidateToken(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $payload = $this->jwtService->validateToken($token);

        $this->assertIsArray($payload);
        $this->assertEquals('ddd-app', $payload['iss']);
        $this->assertEquals('ddd-app', $payload['aud']);
        $this->assertEquals($this->user->getId()->toString(), $payload['sub']);
        $this->assertEquals('test@example.com', $payload['email']);
        $this->assertEquals('Test User', $payload['name']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testValidateInvalidToken(): void
    {
        $invalidToken = 'invalid.token.here';
        $payload = $this->jwtService->validateToken($invalidToken);

        $this->assertNull($payload);
    }

    public function testValidateExpiredToken(): void
    {
        // Create a token with very short expiration
        $originalExpiration = $this->jwtService->expirationTime;
        $this->jwtService->expirationTime = 1; // 1 second
        
        $token = $this->jwtService->generateToken($this->user);
        
        // Wait for token to expire
        sleep(2);
        
        $payload = $this->jwtService->validateToken($token);
        $this->assertNull($payload);
        
        // Restore original expiration
        $this->jwtService->expirationTime = $originalExpiration;
    }

    public function testIsTokenExpired(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        
        // Token should not be expired immediately
        $this->assertFalse($this->jwtService->isTokenExpired($token));
    }

    public function testIsTokenExpiredWithExpiredToken(): void
    {
        // Create a token with very short expiration
        $originalExpiration = $this->jwtService->expirationTime;
        $this->jwtService->expirationTime = 1; // 1 second
        
        $token = $this->jwtService->generateToken($this->user);
        
        // Wait for token to expire
        sleep(2);
        
        $this->assertTrue($this->jwtService->isTokenExpired($token));
        
        // Restore original expiration
        $this->jwtService->expirationTime = $originalExpiration;
    }

    public function testGetUserIdFromToken(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $userId = $this->jwtService->getUserIdFromToken($token);

        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $userId);
        $this->assertEquals($this->user->getId(), $userId);
    }

    public function testGetUserIdFromInvalidToken(): void
    {
        $invalidToken = 'invalid.token.here';
        $userId = $this->jwtService->getUserIdFromToken($invalidToken);

        $this->assertNull($userId);
    }

    public function testTokenPayloadStructure(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $payload = $this->jwtService->validateToken($token);

        $expectedKeys = ['iss', 'aud', 'iat', 'exp', 'sub', 'email', 'name'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    public function testTokenExpirationTime(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $payload = $this->jwtService->validateToken($token);

        $currentTime = time();
        $expectedExpiration = $currentTime + 3600; // 1 hour

        // Allow 1 second tolerance for test execution time
        $this->assertGreaterThanOrEqual($expectedExpiration - 1, $payload['exp']);
        $this->assertLessThanOrEqual($expectedExpiration + 1, $payload['exp']);
    }

    public function testTokenIssuedAtTime(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $payload = $this->jwtService->validateToken($token);

        $currentTime = time();

        // Allow 1 second tolerance for test execution time
        $this->assertGreaterThanOrEqual($currentTime - 1, $payload['iat']);
        $this->assertLessThanOrEqual($currentTime + 1, $payload['iat']);
    }

    public function testMultipleTokensForSameUser(): void
    {
        $token1 = $this->jwtService->generateToken($this->user);
        $token2 = $this->jwtService->generateToken($this->user);

        // Tokens should be different (due to different timestamps)
        $this->assertNotEquals($token1, $token2);

        // Both tokens should be valid
        $payload1 = $this->jwtService->validateToken($token1);
        $payload2 = $this->jwtService->validateToken($token2);

        $this->assertNotNull($payload1);
        $this->assertNotNull($payload2);
        $this->assertEquals($payload1['sub'], $payload2['sub']);
    }

    public function testTokenWithDifferentUser(): void
    {
        $user2 = new User(
            'Another User',
            new Email('another@example.com'),
            (new Password('TestPass123!'))->getHash()
        );

        $token1 = $this->jwtService->generateToken($this->user);
        $token2 = $this->jwtService->generateToken($user2);

        $payload1 = $this->jwtService->validateToken($token1);
        $payload2 = $this->jwtService->validateToken($token2);

        $this->assertNotEquals($payload1['sub'], $payload2['sub']);
        $this->assertNotEquals($payload1['email'], $payload2['email']);
        $this->assertNotEquals($payload1['name'], $payload2['name']);
    }

    public function testTokenWithSpecialCharactersInName(): void
    {
        $userWithSpecialChars = new User(
            'José María O\'Connor',
            new Email('jose@example.com'),
            (new Password('TestPass123!'))->getHash()
        );

        $token = $this->jwtService->generateToken($userWithSpecialChars);
        $payload = $this->jwtService->validateToken($token);

        $this->assertEquals('José María O\'Connor', $payload['name']);
    }
} 