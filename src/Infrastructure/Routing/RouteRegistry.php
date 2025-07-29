<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

/**
 * Route Registry - Helper class for registering routes with automatic authentication
 * 
 * This class provides a clean interface for adding new routes to the application.
 * All routes are automatically protected by authentication unless explicitly marked as public.
 */
final class RouteRegistry
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register a public route (no authentication required)
     */
    public function public(string $method, string $path, callable $handler): self
    {
        $this->router->addPublicRoute($method, $path, $handler);
        return $this;
    }

    /**
     * Register a protected route (authentication required)
     */
    public function protected(string $method, string $path, callable $handler): self
    {
        $this->router->addProtectedRoute($method, $path, $handler);
        return $this;
    }

    /**
     * Register a GET route (protected by default)
     */
    public function get(string $path, callable $handler, bool $public = false): self
    {
        if ($public) {
            $this->router->addPublicRoute('GET', $path, $handler);
        } else {
            $this->router->addProtectedRoute('GET', $path, $handler);
        }
        return $this;
    }

    /**
     * Register a POST route (protected by default)
     */
    public function post(string $path, callable $handler, bool $public = false): self
    {
        if ($public) {
            $this->router->addPublicRoute('POST', $path, $handler);
        } else {
            $this->router->addProtectedRoute('POST', $path, $handler);
        }
        return $this;
    }

    /**
     * Register a PUT route (protected by default)
     */
    public function put(string $path, callable $handler, bool $public = false): self
    {
        if ($public) {
            $this->router->addPublicRoute('PUT', $path, $handler);
        } else {
            $this->router->addProtectedRoute('PUT', $path, $handler);
        }
        return $this;
    }

    /**
     * Register a DELETE route (protected by default)
     */
    public function delete(string $path, callable $handler, bool $public = false): self
    {
        if ($public) {
            $this->router->addPublicRoute('DELETE', $path, $handler);
        } else {
            $this->router->addProtectedRoute('DELETE', $path, $handler);
        }
        return $this;
    }

    /**
     * Register a PATCH route (protected by default)
     */
    public function patch(string $path, callable $handler, bool $public = false): self
    {
        if ($public) {
            $this->router->addPublicRoute('PATCH', $path, $handler);
        } else {
            $this->router->addProtectedRoute('PATCH', $path, $handler);
        }
        return $this;
    }

    /**
     * Register multiple routes for the same path with different methods
     */
    public function match(array $methods, string $path, callable $handler, bool $public = false): self
    {
        foreach ($methods as $method) {
            if ($public) {
                $this->router->addPublicRoute($method, $path, $handler);
            } else {
                $this->router->addProtectedRoute($method, $path, $handler);
            }
        }
        return $this;
    }

    /**
     * Register a resource route (GET, POST, PUT, DELETE for a resource)
     */
    public function resource(string $path, callable $handler, bool $public = false): self
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        return $this->match($methods, $path, $handler, $public);
    }

    /**
     * Register API routes (typically all protected)
     */
    public function api(string $prefix, callable $registerCallback): self
    {
        // Store current router and create a new one for API routes
        $originalRouter = $this->router;
        
        // Create a proxy router that adds the prefix to all routes
        $apiRouter = new class($originalRouter, $prefix) extends Router {
            private string $prefix;
            
            public function __construct(Router $originalRouter, string $prefix) {
                $this->prefix = $prefix;
                parent::__construct($originalRouter->container, $originalRouter->authMiddleware);
            }
            
            public function addRoute(string $method, string $path, callable $handler, bool $requiresAuth = true): void
            {
                parent::addRoute($method, $this->prefix . $path, $handler, $requiresAuth);
            }
        };
        
        $this->router = $apiRouter;
        $registerCallback(new RouteRegistry($apiRouter));
        $this->router = $originalRouter;
        
        return $this;
    }
} 