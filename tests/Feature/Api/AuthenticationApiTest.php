<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

class AuthenticationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testUserRegistration(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];

        $response = $this->makeRequest('POST', '/auth/register', $data);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['success']);
        $this->assertEquals('User registered successfully', $response['data']['message']);
        $this->assertArrayHasKey('user', $response['data']['data']);
        $this->assertEquals('Test User', $response['data']['data']['user']['name']);
        $this->assertEquals('test@example.com', $response['data']['data']['user']['email']);
    }

    public function testUserRegistrationWithInvalidData(): void
    {
        $data = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'weak'
        ];

        $response = $this->makeRequest('POST', '/auth/register', $data);

        $this->assertEquals(400, $response['status']);
        $this->assertFalse($response['data']['success']);
    }

    public function testUserRegistrationWithDuplicateEmail(): void
    {
        // Register first user
        $data1 = [
            'name' => 'First User',
            'email' => 'duplicate@example.com',
            'password' => 'TestPass123!'
        ];
        $this->makeRequest('POST', '/auth/register', $data1);

        // Try to register second user with same email
        $data2 = [
            'name' => 'Second User',
            'email' => 'duplicate@example.com',
            'password' => 'TestPass123!'
        ];

        $response = $this->makeRequest('POST', '/auth/register', $data2);

        $this->assertEquals(400, $response['status']);
        $this->assertFalse($response['data']['success']);
        $this->assertStringContainsString('already exists', $response['data']['message']);
    }

    public function testUserLogin(): void
    {
        // Register a user first
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $this->makeRequest('POST', '/auth/register', $registerData);

        // Login with correct credentials
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['success']);
        $this->assertEquals('Login successful', $response['data']['message']);
        $this->assertArrayHasKey('user', $response['data']['data']);
        $this->assertArrayHasKey('token', $response['data']['data']);
        $this->assertArrayHasKey('expiresIn', $response['data']['data']);
        $this->assertEquals('Test User', $response['data']['data']['user']['name']);
        $this->assertEquals('test@example.com', $response['data']['data']['user']['email']);
        $this->assertNotEmpty($response['data']['data']['token']);
        $this->assertEquals(3600, $response['data']['data']['expiresIn']);
    }

    public function testUserLoginWithInvalidCredentials(): void
    {
        $data = [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongPassword'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $data);

        $this->assertEquals(400, $response['status']);
        $this->assertFalse($response['data']['success']);
        $this->assertStringContainsString('Invalid email or password', $response['data']['message']);
    }

    public function testProtectedEndpointWithoutAuthentication(): void
    {
        $response = $this->makeRequest('GET', '/users', [], ['Accept' => 'application/json']);

        $this->assertEquals(401, $response['status']);
        $this->assertFalse($response['data']['success']);
        $this->assertEquals('Authentication required', $response['data']['message']);
        $this->assertEquals('/login.html', $response['data']['redirect']);
    }

    public function testProtectedEndpointWithValidToken(): void
    {
        // Register and login to get a token
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $this->makeRequest('POST', '/auth/register', $registerData);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $loginResponse = $this->makeRequest('POST', '/auth/login', $loginData);
        $token = $loginResponse['data']['data']['token'];

        // Access protected endpoint with token
        $response = $this->makeRequest('GET', '/users', [], [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}"
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['success']);
    }

    public function testProtectedEndpointWithInvalidToken(): void
    {
        $response = $this->makeRequest('GET', '/users', [], [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer invalid.token.here'
        ]);

        $this->assertEquals(401, $response['status']);
        $this->assertFalse($response['data']['success']);
        $this->assertEquals('Authentication required', $response['data']['message']);
    }

    public function testDashboardAccessWithoutAuthentication(): void
    {
        $response = $this->makeRequest('GET', '/dashboard.html');

        // Should redirect to login page
        $this->assertEquals(302, $response['status']);
        $this->assertStringContainsString('/login.html', $response['headers']['Location'] ?? '');
    }

    public function testDashboardAccessWithValidToken(): void
    {
        // Register and login to get a token
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $this->makeRequest('POST', '/auth/register', $registerData);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $loginResponse = $this->makeRequest('POST', '/auth/login', $loginData);
        $token = $loginResponse['data']['data']['token'];

        // Access dashboard with token
        $response = $this->makeRequest('GET', '/dashboard.html', [], [
            'Authorization' => "Bearer {$token}"
        ]);

        $this->assertEquals(200, $response['status']);
        $this->assertStringContainsString('DDD User Dashboard', $response['data']);
    }

    public function testLoginWithCaseInsensitiveEmail(): void
    {
        // Register with lowercase email
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        $this->makeRequest('POST', '/auth/register', $registerData);

        // Login with uppercase email
        $loginData = [
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'TestPass123!'
        ];

        $response = $this->makeRequest('POST', '/auth/login', $loginData);

        $this->assertEquals(200, $response['status']);
        $this->assertTrue($response['data']['success']);
    }

    public function testPasswordValidation(): void
    {
        $invalidPasswords = [
            'short',           // Too short
            'nouppercase123!', // No uppercase
            'NOLOWERCASE123!', // No lowercase
            'NoNumbers!',      // No numbers
            'NoSpecialChar123' // No special characters
        ];

        foreach ($invalidPasswords as $password) {
            $data = [
                'name' => 'Test User',
                'email' => 'test' . uniqid() . '@example.com',
                'password' => $password
            ];

            $response = $this->makeRequest('POST', '/auth/register', $data);

            $this->assertEquals(400, $response['status']);
            $this->assertFalse($response['data']['success']);
        }
    }

    private function makeRequest(string $method, string $path, array $data = [], array $headers = []): array
    {
        // Simulate HTTP request by calling the application directly
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;
        $_SERVER['HTTP_ACCEPT'] = $headers['Accept'] ?? 'application/json';
        
        if (isset($headers['Authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $headers['Authorization'];
        }

        // Capture output
        ob_start();
        
        try {
            // Include the index.php file
            include __DIR__ . '/../../../../public/index.php';
        } catch (Exception $e) {
            // Handle exceptions
        }
        
        $output = ob_get_clean();
        $httpResponseCode = http_response_code();

        // Parse JSON response
        $responseData = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $responseData = $output; // Raw HTML for dashboard
        }

        return [
            'status' => $httpResponseCode,
            'data' => $responseData,
            'headers' => $this->getResponseHeaders()
        ];
    }

    private function getResponseHeaders(): array
    {
        $headers = [];
        foreach (headers_list() as $header) {
            if (strpos($header, ':') !== false) {
                [$name, $value] = explode(':', $header, 2);
                $headers[trim($name)] = trim($value);
            }
        }
        return $headers;
    }

    protected function tearDown(): void
    {
        // Clean up
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_AUTHORIZATION']);
        
        parent::tearDown();
    }
} 