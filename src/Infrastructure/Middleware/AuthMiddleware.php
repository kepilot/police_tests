<?php

declare(strict_types=1);

namespace App\Infrastructure\Middleware;

use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Container\Container;

final class AuthMiddleware
{
    private const PUBLIC_ROUTES = [
        '/auth/login',
        '/auth/register',
        '/login.html',
        '/favicon.ico',
        '/css/',
        '/js/',
        '/images/',
        '/assets/',
        '/health',
        '/status'
    ];

    public function __construct(
        private readonly Container $container
    ) {
    }

    public function handle(string $path, string $method): bool
    {
        // Allow public routes
        if ($this->isPublicRoute($path)) {
            return true;
        }

        // Check for Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader)) {
            $this->redirectToLogin();
            return false;
        }

        // Extract token from Authorization header
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->redirectToLogin();
            return false;
        }

        $token = $matches[1];
        
        try {
            $jwtService = $this->container->get(JwtService::class);
            $payload = $jwtService->validateToken($token);
            
            if (!$payload) {
                $this->redirectToLogin();
                return false;
            }

            // Check if token is expired
            if ($jwtService->isTokenExpired($token)) {
                $this->redirectToLogin();
                return false;
            }

            // Validate required payload fields
            if (!isset($payload['sub']) || !isset($payload['email']) || !isset($payload['name'])) {
                $this->redirectToLogin();
                return false;
            }

            // Store user info in session for later use
            $_SESSION['user_id'] = $payload['sub'];
            $_SESSION['user_email'] = $payload['email'];
            $_SESSION['user_name'] = $payload['name'];
            $_SESSION['user_role'] = $payload['role'] ?? 'user';
            $_SESSION['authenticated_at'] = time();

            return true;
        } catch (\Exception $e) {
            // Log the error for debugging (in production, you might want to use a proper logger)
            error_log("Authentication error: " . $e->getMessage());
            $this->redirectToLogin();
            return false;
        }
    }

    private function isPublicRoute(string $path): bool
    {
        foreach (self::PUBLIC_ROUTES as $publicRoute) {
            if (strpos($path, $publicRoute) === 0) {
                return true;
            }
        }
        return false;
    }

    private function redirectToLogin(): void
    {
        // Check if it's an API request (JSON content type expected)
        $contentType = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApiRequest = strpos($contentType, 'application/json') !== false || 
                       strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;

        if ($isApiRequest) {
            // Return JSON response for API requests
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
                'redirect' => '/login.html'
            ]);
        } else {
            // Redirect to login page for web requests
            header('Location: /login.html');
        }
        exit;
    }

    public function getCurrentUserId(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUserEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    public function getCurrentUserName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated_at']);
    }

    public function getAuthenticatedAt(): ?int
    {
        return $_SESSION['authenticated_at'] ?? null;
    }

    public function getCurrentUserRole(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public function isAdmin(): bool
    {
        $role = $this->getCurrentUserRole();
        return $role === 'admin' || $role === 'superadmin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->getCurrentUserRole() === 'superadmin';
    }

    public function clearSession(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['authenticated_at']);
    }
} 