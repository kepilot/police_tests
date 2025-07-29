<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Middleware;

use Tests\TestCase;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Services\JwtService;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;
    private JwtService $jwtService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = $this->container->get(AuthMiddleware::class);
        $this->jwtService = $this->container->get(JwtService::class);
        
        // Create a test user
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $this->user = new User('Test User', $email, $password->getHash());
    }

    public function testPublicRoutesAreAllowed(): void
    {
        $publicRoutes = [
            '/auth/login',
            '/auth/register',
            '/login.html',
            '/favicon.ico',
            '/css/style.css',
            '/js/app.js',
            '/images/logo.png',
        ];

        foreach ($publicRoutes as $route) {
            $result = $this->middleware->handle($route, 'GET');
            $this->assertTrue($result, "Route {$route} should be allowed");
        }
    }

    public function testProtectedRoutesRequireAuthentication(): void
    {
        $protectedRoutes = [
            '/users',
            '/dashboard.html',
            '/api/users',
            '/profile',
            '/settings',
        ];

        foreach ($protectedRoutes as $route) {
            // Mock empty Authorization header
            $_SERVER['HTTP_AUTHORIZATION'] = '';
            
            $result = $this->middleware->handle($route, 'GET');
            $this->assertFalse($result, "Route {$route} should require authentication");
        }
    }

    public function testValidTokenAllowsAccess(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertTrue($result);
    }

    public function testInvalidTokenDeniesAccess(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid.token.here';

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertFalse($result);
    }

    public function testExpiredTokenDeniesAccess(): void
    {
        // Create a token with very short expiration
        $originalExpiration = $this->jwtService->expirationTime;
        $this->jwtService->expirationTime = 1; // 1 second
        
        $token = $this->jwtService->generateToken($this->user);
        
        // Wait for token to expire
        sleep(2);
        
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertFalse($result);
        
        // Restore original expiration
        $this->jwtService->expirationTime = $originalExpiration;
    }

    public function testMissingAuthorizationHeaderDeniesAccess(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertFalse($result);
    }

    public function testInvalidAuthorizationFormatDeniesAccess(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'InvalidFormat token';

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertFalse($result);
    }

    public function testGetCurrentUserId(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        // Process the request to set session
        $this->middleware->handle('/users', 'GET');

        $userId = $this->middleware->getCurrentUserId();
        $this->assertEquals($this->user->getId()->toString(), $userId);
    }

    public function testGetCurrentUserEmail(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        // Process the request to set session
        $this->middleware->handle('/users', 'GET');

        $email = $this->middleware->getCurrentUserEmail();
        $this->assertEquals('test@example.com', $email);
    }

    public function testGetCurrentUserName(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        // Process the request to set session
        $this->middleware->handle('/users', 'GET');

        $name = $this->middleware->getCurrentUserName();
        $this->assertEquals('Test User', $name);
    }

    public function testIsAuthenticated(): void
    {
        // Not authenticated initially
        $this->assertFalse($this->middleware->isAuthenticated());

        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        // Process the request to set session
        $this->middleware->handle('/users', 'GET');

        $this->assertTrue($this->middleware->isAuthenticated());
    }

    public function testSessionDataIsSetAfterValidToken(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $this->middleware->handle('/users', 'GET');

        $this->assertEquals($this->user->getId()->toString(), $_SESSION['user_id']);
        $this->assertEquals('test@example.com', $_SESSION['user_email']);
        $this->assertEquals('Test User', $_SESSION['user_name']);
    }

    public function testDifferentHttpMethods(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";

        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $result = $this->middleware->handle('/users', $method);
            $this->assertTrue($result, "Method {$method} should be allowed with valid token");
        }
    }

    public function testCaseInsensitiveAuthorizationHeader(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "bearer {$token}"; // lowercase

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertTrue($result);
    }

    public function testMixedCaseAuthorizationHeader(): void
    {
        $token = $this->jwtService->generateToken($this->user);
        $_SERVER['HTTP_AUTHORIZATION'] = "BeArEr {$token}"; // mixed case

        $result = $this->middleware->handle('/users', 'GET');
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        // Clean up session and server variables
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
        
        parent::tearDown();
    }
} 