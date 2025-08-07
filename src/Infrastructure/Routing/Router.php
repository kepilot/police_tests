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
        $this->addRoute('GET', '/admin.html', [$this, 'handleAdminPage'], true, 'admin');
        $this->addRoute('GET', '/exam.html', [$this, 'handleExamPage'], false);

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
        $this->addRoute('GET', '/logout', [$this, 'handleLogout']);
        
        // Additional protected routes
        $this->addRoute('GET', '/user/settings', [$this, 'handleUserSettings']);
        $this->addRoute('PUT', '/user/settings', [$this, 'handleUpdateUserSettings']);
        $this->addRoute('POST', '/user/change-password', [$this, 'handleChangePassword']);
        
        // Admin routes (require admin role)
        $this->addRoute('GET', '/admin/dashboard', [$this, 'handleAdminDashboard'], true, 'admin');
        $this->addRoute('GET', '/admin/users', [$this, 'handleAdminUsers'], true, 'admin');
        
        // Learning portal routes (admin only)
        $this->addRoute('GET', '/topics', [$this, 'handleListTopics'], true, 'admin');
        $this->addRoute('GET', '/topics/{id}', [$this, 'handleGetTopic'], true, 'admin');
        $this->addRoute('POST', '/topics', [$this, 'handleCreateTopic'], true, 'admin');
        $this->addRoute('PUT', '/topics/{id}', [$this, 'handleUpdateTopic'], true, 'admin');
        $this->addRoute('DELETE', '/topics/{id}', [$this, 'handleDeleteTopic'], true, 'admin');
        
        $this->addRoute('GET', '/exams', [$this, 'handleListExams'], true, 'admin');
        $this->addRoute('GET', '/exams/{id}', [$this, 'handleGetExam'], true, 'admin');
        $this->addRoute('POST', '/exams', [$this, 'handleCreateExam'], true, 'admin');
        $this->addRoute('PUT', '/exams/{id}', [$this, 'handleUpdateExam'], true, 'admin');
        $this->addRoute('DELETE', '/exams/{id}', [$this, 'handleDeleteExam'], true, 'admin');
        
        // Question management routes (admin only)
        $this->addRoute('GET', '/questions', [$this, 'handleListQuestions'], true, 'admin');
        $this->addRoute('POST', '/questions', [$this, 'handleCreateQuestion'], true, 'admin');
        $this->addRoute('GET', '/questions/{id}', [$this, 'handleGetQuestion'], true, 'admin');
        $this->addRoute('PUT', '/questions/{id}', [$this, 'handleUpdateQuestion'], true, 'admin');
        $this->addRoute('DELETE', '/questions/{id}', [$this, 'handleDeleteQuestion'], true, 'admin');
        
        // Question-Topic association routes (admin only)
        $this->addRoute('POST', '/questions/{id}/topics', [$this, 'handleAssociateQuestionTopics'], true, 'admin');
        $this->addRoute('DELETE', '/questions/{id}/topics/{topicId}', [$this, 'handleDisassociateQuestionTopic'], true, 'admin');
        $this->addRoute('GET', '/questions/{id}/topics', [$this, 'handleGetQuestionTopics'], true, 'admin');
        $this->addRoute('GET', '/topics/{id}/questions', [$this, 'handleGetTopicQuestions'], true, 'admin');
        
        $this->addRoute('GET', '/learning/stats', [$this, 'handleLearningStats'], true, 'admin');
        
        // Exam assignment routes
        $this->addRoute('POST', '/exam-assignments', [$this, 'handleAssignExam'], true, 'admin'); // Admin only - assign exams
        $this->addRoute('GET', '/exam-assignments', [$this, 'handleGetAllAssignments'], true, 'admin'); // Admin only - view all assignments
        $this->addRoute('GET', '/exam-assignments/user/{userId}', [$this, 'handleGetUserAssignments'], true); // User can view their own assignments
        $this->addRoute('GET', '/exam-assignments/user/{userId}/pending', [$this, 'handleGetPendingAssignments'], true, 'admin'); // Admin only
        $this->addRoute('GET', '/exam-assignments/user/{userId}/overdue', [$this, 'handleGetOverdueAssignments'], true, 'admin'); // Admin only
        $this->addRoute('PUT', '/exam-assignments/{id}/complete', [$this, 'handleMarkAssignmentComplete'], true); // User can mark their own assignments complete
        $this->addRoute('GET', '/exam-assignments/user/{userId}/stats', [$this, 'handleGetAssignmentStats'], true, 'admin'); // Admin only
        
        // Exam attempt routes
        $this->addRoute('POST', '/api/learning/start-exam-attempt', [$this, 'handleStartExamAttempt'], true);
        $this->addRoute('POST', '/api/learning/submit-exam-attempt', [$this, 'handleSubmitExamAttempt'], true);
        
        // PDF upload routes (admin only)
        $this->addRoute('POST', '/pdf/upload', [$this, 'handlePdfUpload'], true, 'admin');
        $this->addRoute('POST', '/pdf/import', [$this, 'handlePdfImport'], true, 'admin');
    }

    public function addRoute(string $method, string $path, callable $handler, bool $requiresAuth = true, ?string $requiredRole = null): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'requiresAuth' => $requiresAuth,
            'requiredRole' => $requiredRole
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

        // Check role requirements
        if ($route['requiredRole'] && !$this->checkRoleAccess($route['requiredRole'])) {
            $this->handleUnauthorized();
            return;
        }

        // Extract path parameters
        $params = $this->extractPathParameters($route['path'], $path);
        
        // Execute the route handler
        try {
            call_user_func($route['handler'], $path, $method, $params);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                return $route;
            }
        }
        return null;
    }

    private function matchPath(string $routePath, string $requestPath): bool
    {
        // Convert route path to regex pattern
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $requestPath) === 1;
    }

    private function extractPathParameters(string $routePath, string $requestPath): array
    {
        $params = [];
        
        // Extract parameter names from route path
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        
        // Convert route path to regex pattern
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        // Extract parameter values from request path
        if (preg_match($pattern, $requestPath, $matches)) {
            array_shift($matches); // Remove the full match
            foreach ($paramNames[1] as $index => $paramName) {
                $params[$paramName] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }

    private function isPublicRoute(string $path): bool
    {
        foreach ($this->publicRoutes as $publicRoute) {
            if ($publicRoute === '/') {
                // For root route, use exact match
                if ($path === '/') {
                    return true;
                }
            } else {
                // For other routes, use prefix match
                if (strpos($path, $publicRoute) === 0) {
                    return true;
                }
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
        
        // Redirect to login page
        header('Location: /login.html');
        exit();
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

    public function handleAdminPage(string $path, string $method): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: /login.html');
            exit();
        }

        // TODO: Add admin role check here
        $adminPath = __DIR__ . '/../../../public/admin.html';
        if (file_exists($adminPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($adminPath);
        } else {
            http_response_code(404);
            echo 'Admin page not found';
        }
    }

    public function handleExamPage(string $path, string $method): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            header('Location: /login.html');
            exit();
        }

        $examPath = __DIR__ . '/../../../public/exam.html';
        if (file_exists($examPath)) {
            header('Content-Type: text/html');
            echo file_get_contents($examPath);
        } else {
            http_response_code(404);
            echo 'Exam page not found';
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

    public function handleGetTopic(string $path, string $method, array $params): void
    {
        $topicId = $params['id'] ?? null;
        if (!$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getTopic($topicId);
        
        if (!$result['success']) {
            http_response_code(404);
        }
        
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

    public function handleGetExam(string $path, string $method, array $params): void
    {
        $examId = $params['id'] ?? null;
        if (!$examId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exam ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getExam($examId);
        
        if (!$result['success']) {
            http_response_code(404);
        }
        
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

    // Question management handlers
    public function handleListQuestions(string $path, string $method): void
    {
        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->listQuestions();
        echo json_encode($result);
    }

    public function handleCreateQuestion(string $path, string $method): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['text']) || !isset($input['type']) || 
            !isset($input['exam_id']) || !isset($input['options']) || !isset($input['correct_option'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Text, type, exam_id, options, and correct_option are required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->createQuestion(
            $input['text'],
            $input['type'],
            $input['exam_id'],
            $input['options'],
            $input['correct_option'],
            $input['points'] ?? 1,
            $input['topic_ids'] ?? []
        );
        
        if ($result['success']) {
            http_response_code(201);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    public function handleGetQuestion(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getQuestion($questionId);
        
        if (!$result['success']) {
            http_response_code(404);
        }
        
        echo json_encode($result);
    }

    public function handleUpdateQuestion(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID is required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['text']) || !isset($input['options']) || !isset($input['correct_option'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Text, options, and correct_option are required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->updateQuestion(
            $questionId,
            $input['text'],
            $input['options'],
            $input['correct_option'],
            $input['points'] ?? 1,
            $input['topic_ids'] ?? []
        );
        
        echo json_encode($result);
    }

    public function handleDeleteQuestion(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->deleteQuestion($questionId);
        
        if ($result['success']) {
            http_response_code(204);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    // Question-Topic association handlers
    public function handleAssociateQuestionTopics(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID is required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['topic_ids']) || !is_array($input['topic_ids'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'topic_ids array is required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->associateQuestionTopics($questionId, $input['topic_ids']);
        
        echo json_encode($result);
    }

    public function handleDisassociateQuestionTopic(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        $topicId = $params['topicId'] ?? null;
        
        if (!$questionId || !$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID and Topic ID are required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->disassociateQuestionTopic($questionId, $topicId);
        
        echo json_encode($result);
    }

    public function handleGetQuestionTopics(string $path, string $method, array $params): void
    {
        $questionId = $params['id'] ?? null;
        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Question ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getQuestionTopics($questionId);
        
        echo json_encode($result);
    }

    public function handleGetTopicQuestions(string $path, string $method, array $params): void
    {
        $topicId = $params['id'] ?? null;
        if (!$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->getTopicQuestions($topicId);
        
        echo json_encode($result);
    }

    // Topic management handlers
    public function handleUpdateTopic(string $path, string $method, array $params): void
    {
        $topicId = $params['id'] ?? null;
        if (!$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            return;
        }

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
        $result = $controller->updateTopic($topicId, $input['title'], $input['description'], $input['level']);
        
        echo json_encode($result);
    }

    public function handleDeleteTopic(string $path, string $method, array $params): void
    {
        $topicId = $params['id'] ?? null;
        if (!$topicId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->deleteTopic($topicId);
        
        if ($result['success']) {
            http_response_code(204);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    // Exam management handlers
    public function handleUpdateExam(string $path, string $method, array $params): void
    {
        $examId = $params['id'] ?? null;
        if (!$examId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exam ID is required']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['title']) || !isset($input['description']) || 
            !isset($input['duration_minutes']) || !isset($input['passing_score_percentage'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Title, description, duration_minutes, and passing_score_percentage are required'
            ]);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->updateExam($examId, $input['title'], $input['description'], 
            $input['duration_minutes'], $input['passing_score_percentage']);
        
        echo json_encode($result);
    }

    public function handleDeleteExam(string $path, string $method, array $params): void
    {
        $examId = $params['id'] ?? null;
        if (!$examId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Exam ID is required']);
            return;
        }

        $controller = new \App\Presentation\Controllers\LearningController($this->container);
        $result = $controller->deleteExam($examId);
        
        if ($result['success']) {
            http_response_code(204);
        } else {
            http_response_code(400);
        }
        
        echo json_encode($result);
    }

    // Exam Assignment Handlers
    public function handleGetAllAssignments(string $path, string $method): void
    {
        try {
            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->getAllAssignments();

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleAssignExam(string $path, string $method): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['userId']) || !isset($input['examId']) || !isset($input['assignedBy'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->assignExamToUser(
                $input['userId'],
                $input['examId'],
                $input['assignedBy'],
                $input['dueDate'] ?? null
            );

            http_response_code($result['success'] ? 201 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleGetUserAssignments(string $path, string $method, array $params): void
    {
        try {
            $userId = $params['userId'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->getUserAssignments($userId);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleGetPendingAssignments(string $path, string $method, array $params): void
    {
        try {
            $userId = $params['userId'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->getPendingAssignments($userId);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleGetOverdueAssignments(string $path, string $method, array $params): void
    {
        try {
            $userId = $params['userId'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->getOverdueAssignments($userId);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleMarkAssignmentComplete(string $path, string $method, array $params): void
    {
        try {
            $assignmentId = $params['id'] ?? null;
            if (!$assignmentId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->markAssignmentAsCompleted($assignmentId);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleGetAssignmentStats(string $path, string $method, array $params): void
    {
        try {
            $userId = $params['userId'] ?? null;
            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAssignmentController::class);
            $result = $controller->getAssignmentStatistics($userId);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    // Exam Attempt Handlers
    public function handleStartExamAttempt(string $path, string $method): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['userId']) || !isset($input['examId'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID and Exam ID are required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAttemptController::class);
            $result = $controller->startExamAttempt($input['userId'], $input['examId']);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handleSubmitExamAttempt(string $path, string $method): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['attemptId']) || !isset($input['answers'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Attempt ID and answers are required']);
                return;
            }

            $controller = $this->container->get(\App\Presentation\Controllers\ExamAttemptController::class);
            $result = $controller->submitExamAttempt($input['attemptId'], $input['answers']);

            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handlePdfUpload(string $path, string $method): void
    {
        try {
            $controller = $this->container->get(\App\Presentation\Controllers\PdfUploadController::class);
            $controller->uploadPdf();
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    public function handlePdfImport(string $path, string $method): void
    {
        try {
            $controller = $this->container->get(\App\Presentation\Controllers\PdfUploadController::class);
            $controller->importQuestions();
        } catch (\Exception $e) {
            $this->handleError($e);
        }
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
                'POST /logout' => 'Logout user (requires authentication)',
                
                // Exam assignment endpoints
                'POST /exam-assignments' => 'Assign exam to user (requires JSON with userId, examId, assignedBy, optional dueDate)',
                'GET /exam-assignments/user/{userId}' => 'Get user assignments (requires authentication)',
                'GET /exam-assignments/user/{userId}/pending' => 'Get pending assignments (requires authentication)',
                'GET /exam-assignments/user/{userId}/overdue' => 'Get overdue assignments (requires authentication)',
                'PUT /exam-assignments/{id}/complete' => 'Mark assignment as complete (requires authentication)',
                'GET /exam-assignments/user/{userId}/stats' => 'Get assignment statistics (requires authentication)'
            ]
        ]);
    }

    private function checkRoleAccess(string $requiredRole): bool
    {
        if ($requiredRole === 'admin') {
            return $this->authMiddleware->isAdmin();
        }
        
        if ($requiredRole === 'superadmin') {
            return $this->authMiddleware->isSuperAdmin();
        }
        
        return true; // No role requirement
    }

    private function handleUnauthorized(): void
    {
        // Check if it's an API request
        $contentType = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApiRequest = strpos($contentType, 'application/json') !== false || 
                       strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;

        if ($isApiRequest) {
            // Return JSON response for API requests
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Access denied. Insufficient permissions.',
                'redirect' => '/dashboard.html'
            ]);
        } else {
            // Redirect to dashboard for web requests
            header('Location: /dashboard.html');
        }
        exit;
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