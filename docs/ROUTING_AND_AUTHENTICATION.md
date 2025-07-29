# Routing and Authentication System

This document explains how the routing and authentication system works in the DDD application, and how to add new routes that are automatically protected by authentication.

## Overview

The application uses a centralized routing system that automatically applies authentication middleware to all routes except those explicitly marked as public. This ensures that:

1. **All new routes are protected by default** - You don't need to remember to add authentication
2. **Public routes are explicitly defined** - Clear separation between public and protected endpoints
3. **Consistent authentication behavior** - All protected routes behave the same way
4. **Easy to maintain** - Centralized authentication logic

## How It Works

### 1. Request Flow

```
HTTP Request → Router → AuthMiddleware → Route Handler → Response
```

1. **Router** receives the HTTP request
2. **AuthMiddleware** checks if the route requires authentication
3. If authentication is required, it validates the JWT token
4. If valid, the request proceeds to the route handler
5. If invalid, the user is redirected to login

### 2. Authentication Middleware

The `AuthMiddleware` class:

- **Validates JWT tokens** from the `Authorization: Bearer <token>` header
- **Checks token expiration** and validity
- **Stores user information** in the session for easy access
- **Redirects unauthenticated users** to the login page
- **Handles both API and web requests** appropriately

### 3. Public vs Protected Routes

#### Public Routes (No Authentication Required)
- `/auth/login` - User login
- `/auth/register` - User registration
- `/login.html` - Login page
- `/health` - Health check
- `/status` - Status check
- `/favicon.ico` - Favicon
- Static assets (`/css/`, `/js/`, `/images/`, `/assets/`)

#### Protected Routes (Authentication Required)
- All other routes are protected by default
- Require a valid JWT token in the `Authorization` header
- Return 401 Unauthorized if no valid token is provided

## Adding New Routes

### Method 1: Direct Router Registration (Recommended)

Add routes in the `Router::registerDefaultRoutes()` method:

```php
private function registerDefaultRoutes(): void
{
    // Public routes (no authentication required)
    $this->addRoute('GET', '/health', [$this, 'handleHealth']);
    $this->addRoute('POST', '/auth/forgot-password', [$this, 'handleForgotPassword']);
    
    // Protected routes (authentication required by default)
    $this->addRoute('GET', '/user/profile', [$this, 'handleUserProfile']);
    $this->addRoute('PUT', '/user/profile', [$this, 'handleUpdateProfile']);
    $this->addRoute('GET', '/admin/dashboard', [$this, 'handleAdminDashboard']);
}
```

### Method 2: Using Route Registry (Advanced)

For more complex routing scenarios, use the `RouteRegistry`:

```php
$registry = new RouteRegistry($router);

$registry
    // Public routes
    ->get('/health', [self::class, 'healthCheck'], true)
    ->post('/auth/forgot-password', [self::class, 'forgotPassword'], true)
    
    // Protected routes (default)
    ->get('/user/profile', [self::class, 'userProfile'])
    ->put('/user/profile', [self::class, 'updateProfile'])
    ->get('/admin/dashboard', [self::class, 'adminDashboard'])
    
    // API routes (all protected)
    ->api('/api/v1', function(RouteRegistry $api) {
        $api
            ->get('/users', [self::class, 'apiListUsers'])
            ->post('/users', [self::class, 'apiCreateUser']);
    });
```

### Method 3: Adding Routes Programmatically

You can also add routes dynamically:

```php
// Get the router from the container
$router = $container->get(Router::class);

// Add a public route
$router->addPublicRoute('GET', '/custom/public', [$this, 'handleCustomPublic']);

// Add a protected route
$router->addProtectedRoute('POST', '/custom/protected', [$this, 'handleCustomProtected']);
```

## Route Handler Examples

### Basic Route Handler

```php
public function handleUserProfile(string $path, string $method): void
{
    // User is already authenticated by middleware
    $authMiddleware = $this->container->get(AuthMiddleware::class);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_id' => $authMiddleware->getCurrentUserId(),
            'email' => $authMiddleware->getCurrentUserEmail(),
            'name' => $authMiddleware->getCurrentUserName()
        ]
    ]);
}
```

### Route Handler with Input Validation

```php
public function handleUpdateProfile(string $path, string $method): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name is required'
        ]);
        return;
    }
    
    // TODO: Implement profile update logic
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
        ]);
}
```

### Admin Route Handler (with Role Check)

```php
public function handleAdminDashboard(string $path, string $method): void
{
    $authMiddleware = $this->container->get(AuthMiddleware::class);
    $userId = $authMiddleware->getCurrentUserId();
    
    // TODO: Add admin role check
    // if (!$this->isAdmin($userId)) {
    //     http_response_code(403);
    //     echo json_encode(['success' => false, 'message' => 'Admin access required']);
    //     return;
    // }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_users' => 150,
            'active_users' => 89,
            'system_status' => 'healthy'
        ]
    ]);
}
```

## Authentication in Route Handlers

### Accessing Current User Information

```php
$authMiddleware = $this->container->get(AuthMiddleware::class);

// Get user information
$userId = $authMiddleware->getCurrentUserId();
$userEmail = $authMiddleware->getCurrentUserEmail();
$userName = $authMiddleware->getCurrentUserName();
$authenticatedAt = $authMiddleware->getAuthenticatedAt();

// Check if user is authenticated
if ($authMiddleware->isAuthenticated()) {
    // User is logged in
}
```

### Clearing Session (Logout)

```php
public function handleLogout(string $path, string $method): void
{
    $authMiddleware = $this->container->get(AuthMiddleware::class);
    $authMiddleware->clearSession();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}
```

## Testing Routes

### Testing Public Routes

```bash
# Health check (public)
curl http://localhost:8080/health

# Status check (public)
curl http://localhost:8080/status
```

### Testing Protected Routes

```bash
# First, login to get a token
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"TestPass123!"}'

# Use the token to access protected routes
curl http://localhost:8080/users \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"

# Access user profile
curl http://localhost:8080/profile \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

### Testing Without Authentication

```bash
# This will return 401 Unauthorized
curl http://localhost:8080/users

# This will redirect to login page for web requests
curl http://localhost:8080/dashboard.html
```

## Best Practices

### 1. Always Use the Router

Don't bypass the routing system. All requests should go through the `Router` class to ensure proper authentication.

### 2. Mark Public Routes Explicitly

When adding new routes, explicitly mark them as public if they don't require authentication:

```php
// Public route
$this->addPublicRoute('GET', '/health', [$this, 'handleHealth']);

// Protected route (default)
$this->addRoute('GET', '/user/profile', [$this, 'handleUserProfile']);
```

### 3. Validate Input in Route Handlers

Always validate input data in your route handlers:

```php
public function handleUpdateUser(string $path, string $method): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['name']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name and email are required'
        ]);
        return;
    }
    
    // Process the request...
}
```

### 4. Use Proper HTTP Status Codes

Return appropriate HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

### 5. Consistent Response Format

Use a consistent JSON response format:

```php
// Success response
echo json_encode([
    'success' => true,
    'message' => 'Operation completed successfully',
    'data' => $result
]);

// Error response
echo json_encode([
    'success' => false,
    'message' => 'Error description'
]);
```

## Troubleshooting

### Common Issues

1. **Route not found (404)**
   - Check if the route is registered in `registerDefaultRoutes()`
   - Verify the HTTP method matches

2. **Unauthorized (401)**
   - Check if the route requires authentication
   - Verify the JWT token is valid and not expired
   - Ensure the `Authorization` header is present

3. **Static files not loading**
   - Check if the file path is in the public routes list
   - Verify the file exists in the `public/` directory

### Debugging

Enable error logging to debug authentication issues:

```php
// In AuthMiddleware::handle()
error_log("Authentication error: " . $e->getMessage());
```

## Security Considerations

1. **JWT Token Security**
   - Tokens expire after 1 hour by default
   - Use HTTPS in production
   - Store JWT secret securely

2. **Session Security**
   - Sessions are cleared on logout
   - Session data is validated on each request

3. **Input Validation**
   - Always validate and sanitize input data
   - Use prepared statements for database queries

4. **Role-Based Access**
   - Implement role checks for admin routes
   - Consider using middleware for role validation

## Future Enhancements

1. **Role-Based Middleware**
   - Add role validation middleware
   - Support for multiple user roles

2. **Rate Limiting**
   - Add rate limiting middleware
   - Protect against brute force attacks

3. **API Versioning**
   - Support for multiple API versions
   - Backward compatibility

4. **Caching**
   - Add response caching
   - Cache user permissions and roles 