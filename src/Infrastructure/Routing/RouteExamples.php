<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

/**
 * Route Examples - Demonstrates how to add new routes with automatic authentication
 * 
 * This file shows various ways to register routes that will automatically be protected
 * by authentication middleware. Copy these examples and modify them for your needs.
 */
final class RouteExamples
{
    /**
     * Example: How to add new routes to your application
     * 
     * Add this to your Router constructor or create a separate route registration method
     */
    public static function registerExampleRoutes(Router $router): void
    {
        // Method 1: Direct route registration (protected by default)
        $router->addRoute('GET', '/admin/dashboard', [self::class, 'adminDashboard']);
        $router->addRoute('POST', '/admin/users', [self::class, 'createAdminUser']);
        
        // Method 2: Public routes (no authentication required)
        $router->addPublicRoute('GET', '/health', [self::class, 'healthCheck']);
        $router->addPublicRoute('GET', '/status', [self::class, 'statusCheck']);
        
        // Method 3: Protected routes (explicitly require authentication)
        $router->addProtectedRoute('GET', '/user/profile', [self::class, 'userProfile']);
        $router->addProtectedRoute('PUT', '/user/profile', [self::class, 'updateUserProfile']);
        
        // Method 4: Using RouteRegistry for cleaner syntax
        $registry = new RouteRegistry($router);
        
        $registry
            // Public routes
            ->get('/health', [self::class, 'healthCheck'], true)
            ->get('/status', [self::class, 'statusCheck'], true)
            ->post('/auth/forgot-password', [self::class, 'forgotPassword'], true)
            
            // Protected routes (default)
            ->get('/user/profile', [self::class, 'userProfile'])
            ->put('/user/profile', [self::class, 'updateUserProfile'])
            ->get('/user/settings', [self::class, 'userSettings'])
            ->put('/user/settings', [self::class, 'updateUserSettings'])
            ->post('/user/change-password', [self::class, 'changePassword'])
            
            // Admin routes
            ->get('/admin/dashboard', [self::class, 'adminDashboard'])
            ->get('/admin/users', [self::class, 'listUsers'])
            ->post('/admin/users', [self::class, 'createUser'])
            ->put('/admin/users/{id}', [self::class, 'updateUser'])
            ->delete('/admin/users/{id}', [self::class, 'deleteUser'])
            
            // API routes
            ->api('/api/v1', function(RouteRegistry $api) {
                $api
                    ->get('/users', [self::class, 'apiListUsers'])
                    ->post('/users', [self::class, 'apiCreateUser'])
                    ->get('/users/{id}', [self::class, 'apiGetUser'])
                    ->put('/users/{id}', [self::class, 'apiUpdateUser'])
                    ->delete('/users/{id}', [self::class, 'apiDeleteUser']);
            });
    }

    // Example route handlers

    /**
     * Health check endpoint (public)
     */
    public static function healthCheck(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'message' => 'Service is healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]);
    }

    /**
     * Status check endpoint (public)
     */
    public static function statusCheck(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'status' => 'operational',
            'uptime' => '99.9%',
            'last_check' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Forgot password endpoint (public)
     */
    public static function forgotPassword(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['email'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email is required'
            ]);
            return;
        }

        // TODO: Implement password reset logic
        echo json_encode([
            'success' => true,
            'message' => 'Password reset email sent (if email exists)'
        ]);
    }

    /**
     * User profile endpoint (protected)
     */
    public static function userProfile(string $path, string $method): void
    {
        // User is already authenticated by middleware
        $authMiddleware = new \App\Infrastructure\Middleware\AuthMiddleware(new \App\Infrastructure\Container\Container());
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user_id' => $authMiddleware->getCurrentUserId(),
                'email' => $authMiddleware->getCurrentUserEmail(),
                'name' => $authMiddleware->getCurrentUserName(),
                'authenticated_at' => $authMiddleware->getAuthenticatedAt()
            ]
        ]);
    }

    /**
     * Update user profile endpoint (protected)
     */
    public static function updateUserProfile(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid JSON data'
            ]);
            return;
        }

        // TODO: Implement profile update logic
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * User settings endpoint (protected)
     */
    public static function userSettings(string $path, string $method): void
    {
        // TODO: Implement user settings retrieval
        echo json_encode([
            'success' => true,
            'data' => [
                'theme' => 'dark',
                'notifications' => true,
                'language' => 'en'
            ]
        ]);
    }

    /**
     * Update user settings endpoint (protected)
     */
    public static function updateUserSettings(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // TODO: Implement settings update logic
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Change password endpoint (protected)
     */
    public static function changePassword(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['current_password']) || !isset($input['new_password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Current password and new password are required'
            ]);
            return;
        }

        // TODO: Implement password change logic
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Admin dashboard endpoint (protected)
     */
    public static function adminDashboard(string $path, string $method): void
    {
        // TODO: Add admin role check
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => 150,
                'active_users' => 89,
                'new_users_today' => 5,
                'system_status' => 'healthy'
            ]
        ]);
    }

    /**
     * List users endpoint (protected)
     */
    public static function listUsers(string $path, string $method): void
    {
        // TODO: Add admin role check
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => [
                    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
                ]
            ]
        ]);
    }

    /**
     * Create user endpoint (protected)
     */
    public static function createUser(string $path, string $method): void
    {
        // TODO: Add admin role check
        $input = json_decode(file_get_contents('php://input'), true);
        
        // TODO: Implement user creation logic
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully'
        ]);
    }

    /**
     * Update user endpoint (protected)
     */
    public static function updateUser(string $path, string $method): void
    {
        // TODO: Add admin role check
        $input = json_decode(file_get_contents('php://input'), true);
        
        // TODO: Implement user update logic
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * Delete user endpoint (protected)
     */
    public static function deleteUser(string $path, string $method): void
    {
        // TODO: Add admin role check
        
        // TODO: Implement user deletion logic
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // API route handlers (all protected by default)

    public static function apiListUsers(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => [
                    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
                ]
            ]
        ]);
    }

    public static function apiCreateUser(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        echo json_encode([
            'success' => true,
            'message' => 'User created via API'
        ]);
    }

    public static function apiGetUser(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]
        ]);
    }

    public static function apiUpdateUser(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        echo json_encode([
            'success' => true,
            'message' => 'User updated via API'
        ]);
    }

    public static function apiDeleteUser(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted via API'
        ]);
    }
} 