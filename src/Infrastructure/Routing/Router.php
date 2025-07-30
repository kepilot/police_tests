<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Container\Container;
use App\Presentation\Controllers\UserController;

final class Router
{
    private array $routes = [];
    private array $publicRoutes = [
        '/',
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
        private readonly Container $container,
        private readonly AuthMiddleware $authMiddleware
    ) {
        $this->registerDefaultRoutes();
    }

    private function registerDefaultRoutes(): void
    {
        // Root route - redirect to login page
        $this->addRoute('GET', '/', [$this, 'handleRoot'], false);

        // Static HTML files (public)
        $this->addRoute('GET', '/login.html', [$this, 'handleLoginPage'], false);
        $this->addRoute('GET', '/dashboard.html', [$this, 'handleDashboardPage'], false);

        // Authentication routes (public)
        $this->addRoute('POST', '/auth/register', [$this, 'handleRegister']);
        $this->addRoute('POST', '/auth/login', [$this, 'handleLogin']);

        // Health and status endpoints (public)
        $this->addRoute('GET', '/health', [$this, 'handleHealth']);
        $this->addRoute('GET', '/status', [$this, 'handleStatus']);

        // Protected routes (require authentication)
        $this->addRoute('POST', '/users', [$this, 'handleCreateUser']);
        $this->addRoute('GET', '/users', [$this, 'handleListUsers']);
        $this->addRoute('GET', '/profile', [$this, 'handleProfile']);
        $this->addRoute('PUT', '/profile', [$this, 'handleUpdateProfile']);
        $this->addRoute('POST', '/logout', [$this, 'handleLogout']);
        
        // Additional protected routes
        $this->addRoute('GET', '/user/settings', [$this, 'handleUserSettings']);
        $this->addRoute('PUT', '/user/settings', [$this, 'handleUpdateUserSettings']);
        $this->addRoute('POST', '/user/change-password', [$this, 'handleChangePassword']);
        
        // Admin routes (require admin role)
        $this->addRoute('GET', '/admin/dashboard', [$this, 'handleAdminDashboard']);
        $this->addRoute('GET', '/admin/users', [$this, 'handleAdminUsers']);
        
        // Learning portal routes
        $this->addRoute('GET', '/topics', [$this, 'handleListTopics']);
        $this->addRoute('POST', '/topics', [$this, 'handleCreateTopic']);
        $this->addRoute('GET', '/exams', [$this, 'handleListExams']);
        $this->addRoute('POST', '/exams', [$this, 'handleCreateExam']);
        $this->addRoute('GET', '/learning/stats', [$this, 'handleLearningStats']);
    }

    public function addRoute(string $method, string $path, callable $handler, bool $requiresAuth = true): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'requiresAuth' => $requiresAuth
        ];
    }

    public function addPublicRoute(string $method, string $path, callable $handler): void
    {
        $this->addRoute($method, $path, $handler, false);
    }

    public function addProtectedRoute(string $method, string $path, callable $handler): void
    {
        $this->addRoute($method, $path, $handler, true);
    }

    public function handleRequest(string $method, string $path): void
    {
        // Check if it's a public static file
        if ($this->isPublicStaticFile($path)) {
            $this->serveStaticFile($path);
            return;
        }

        // Find matching route
        $route = $this->findRoute($method, $path);
        
        if (!$route) {
            $this->handleNotFound();
            return;
        }

        // Apply authentication middleware for protected routes
        if ($route['requiresAuth'] && !$this->isPublicRoute($path)) {
            if (!$this->authMiddleware->handle($path, $method)) {
                return; // Middleware has already handled the response
            }
        }

        // Execute the route handler
        try {
            call_user_func($route['handler'], $path, $method);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return $route;
            }
        }
        return null;
    }

    private function isPublicRoute(string $path): bool
    {
        foreach ($this->publicRoutes as $publicRoute) {
            if (strpos($path, $publicRoute) === 0) {
                return true;
            }
        }
        return false;
    }

    private function isPublicStaticFile(string $path): bool
    {
        $staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.ico', '.svg', '.woff', '.woff2', '.ttf', '.eot'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return in_array('.' . $extension, $staticExtensions) && $this->isPublicRoute($path);
    }

    private function serveStaticFile(string $path): void
    {
        $filePath = __DIR__ . '/../../../public' . $path;
        
        if (file_exists($filePath) && is_file($filePath)) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $contentType = $this->getContentType($extension);
            
            header('Content-Type: ' . $contentType);
            header('Cache-Control: public, max-age=31536000'); // 1 year cache
            readfile($filePath);
        } else {
            http_response_code(404);
            echo 'File not found';
        }
    }

    private function getContentType(string $extension): string
    {
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject'
        ];

        return $contentTypes[$extension] ?? 'application/octet-stream';
    }

    // Route handlers
    public function handleRegister(string $path, string $method): void
    {
        $controller = new UserController($this->container);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Name, email, and password are required'
            ]);
            return;
        }

        $result = $controller->registerUser($input['name'], $input['email'], $input['password']);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    public function handleLogin(string $path, string $method): void
    {
        $controller = new UserController($this->container);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]);
            return;
        }

        $result = $controller->loginUser($input['email'], $input['password']);
        
        if ($result['success']) {
            http_response_code(200);
        } else {
            http_response_code(401);
        }
        
        echo json_encode($result);
    }

    public function handleCreateUser(string $path, string $method): void
    {
        $controller = new UserController($this->container);
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['name']) || !isset($input['email'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Name and email are required'
            ]);
            return;
        }

        $result = $controller->createUser($input['name'], $input['email']);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    public function handleListUsers(string $path, string $method): void
    {
        $controller = new UserController($this->container);
        $result = $controller->listUsers();
        echo json_encode($result);
    }

    public function handleDashboard(string $path, string $method): void
    {
        $dashboardPath = __DIR__ . '/../../../public/dashboard.html';
        if (file_exists($dashboardPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($dashboardPath);
        } else {
            http_response_code(404);
            echo 'Dashboard not found';
        }
    }

    public function handleProfile(string $path, string $method): void
    {
        // Get current user profile
        $userId = $this->authMiddleware->getCurrentUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            return;
        }

        // TODO: Implement profile retrieval
        echo json_encode([
            'success' => true,
            'message' => 'Profile endpoint - implement profile retrieval',
            'data' => [
                'user_id' => $userId,
                'email' => $this->authMiddleware->getCurrentUserEmail(),
                'name' => $this->authMiddleware->getCurrentUserName()
            ]
        ]);
    }

    public function handleUpdateProfile(string $path, string $method): void
    {
        // Update current user profile
        $userId = $this->authMiddleware->getCurrentUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            return;
        }

        // TODO: Implement profile update
        echo json_encode([
            'success' => true,
            'message' => 'Profile update endpoint - implement profile update',
            'data' => [
                'user_id' => $userId
            ]
        ]);
    }

    public function handleLogout(string $path, string $method): void
    {
        // Clear session
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function handleHealth(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'message' => 'Service is healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]);
    }

    public function handleStatus(string $path, string $method): void
    {
        echo json_encode([
            'success' => true,
            'status' => 'operational',
            'uptime' => '99.9%',
            'last_check' => date('Y-m-d H:i:s')
        ]);
    }

    public function handleRoot(string $path, string $method): void
    {
        // Check if user is already authenticated
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            // User is logged in, redirect to dashboard
            header('Location: /dashboard.html');
        } else {
            // User is not logged in, redirect to login page
            header('Location: /login.html');
        }
        exit();
    }

    public function handleLoginPage(string $path, string $method): void
    {
        $loginPath = __DIR__ . '/../../../public/login.html';
        if (file_exists($loginPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($loginPath);
        } else {
            http_response_code(404);
            echo 'Login page not found';
        }
    }

    public function handleDashboardPage(string $path, string $method): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: /login.html');
            exit();
        }

        $dashboardPath = __DIR__ . '/../../../public/dashboard.html';
        if (file_exists($dashboardPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($dashboardPath);
        } else {
            http_response_code(404);
            echo 'Dashboard not found';
        }
    }

    public function handleUserSettings(string $path, string $method): void
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

    public function handleUpdateUserSettings(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // TODO: Implement settings update logic
        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    public function handleChangePassword(string $path, string $method): void
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

    public function handleAdminDashboard(string $path, string $method): void
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

    public function handleAdminUsers(string $path, string $method): void
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

    public function handleListTopics(string $path, string $method): void
    {
        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->listTopics();
        echo json_encode($result);
    }

    public function handleCreateTopic(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['title']) || !isset($input['description']) || !isset($input['level'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Title, description, and level are required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->createTopic($input['title'], $input['description'], $input['level']);
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    public function handleListExams(string $path, string $method): void
    {
        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->listExams();
        echo json_encode($result);
    }

    public function handleCreateExam(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['title']) || !isset($input['description']) || 
            !isset($input['duration_minutes']) || !isset($input['passing_score_percentage']) || !isset($input['topic_id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Title, description, duration_minutes, passing_score_percentage, and topic_id are required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->createExam(
            $input['title'], 
            $input['description'], 
            $input['duration_minutes'], 
            $input['passing_score_percentage'], 
            $input['topic_id']
        );
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    public function handleLearningStats(string $path, string $method): void
    {
        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getLearningStats();
        echo json_encode($result);
    }

    private function handleNotFound(): void
    {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found',
            'available_endpoints' => [
                // Public endpoints
                'GET /health' => 'Health check endpoint (public)',
                'GET /status' => 'Status check endpoint (public)',
                'POST /auth/register' => 'Register a new user (requires JSON with name, email, and password)',
                'POST /auth/login' => 'Login user (requires JSON with email and password)',
                
                // Protected endpoints (require authentication)
                'POST /users' => 'Create a new user (requires JSON with name and email)',
                'GET /users' => 'List users (requires authentication)',
                'GET /dashboard.html' => 'User dashboard (requires authentication)',
                'GET /profile' => 'Get user profile (requires authentication)',
                'PUT /profile' => 'Update user profile (requires authentication)',
                'GET /user/settings' => 'Get user settings (requires authentication)',
                'PUT /user/settings' => 'Update user settings (requires authentication)',
                'POST /user/change-password' => 'Change user password (requires authentication)',
                'GET /admin/dashboard' => 'Admin dashboard (requires authentication)',
                'GET /admin/users' => 'Admin users list (requires authentication)',
                'POST /logout' => 'Logout user (requires authentication)'
            ]
        ]);
    }

    private function handleError(\Exception $e): void
    {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error: ' . $e->getMessage()
        ]);
    }
} 