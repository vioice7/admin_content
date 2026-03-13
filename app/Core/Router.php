<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $params = [];

    /**
     * Register GET route
     */
    public function get(string $uri, string $action): void
    {
        $this->routes['GET'][$uri] = $action;
    }

    /**
     * Register POST route
     */
    public function post(string $uri, string $action): void
    {
        $this->routes['POST'][$uri] = $action;
    }

    /**
     * Dispatch request to appropriate controller
     */
    public function dispatch(string $uri, string $method): void
    {
        // Remove query string from URI
        $uri = parse_url($uri, PHP_URL_PATH);

        $route = $this->matchRoute($uri, $method);

        if (!$route) {
            $this->notFound();
        }

        [$controller, $action] = explode('@', $route);
        $controllerClass = "App\\Controllers\\$controller";

        // Instantiate controller and call action with params
        (new $controllerClass)->$action($this->params);
    }

    /**
     * Match URI against registered routes
     * Supports dynamic routes like /posts/{id}
     */
    private function matchRoute(string $uri, string $method): ?string
    {
        // Exact match first
        if (isset($this->routes[$method][$uri])) {
            return $this->routes[$method][$uri];
        }

        // Try pattern matching
        foreach ($this->routes[$method] ?? [] as $route => $action) {
            if ($this->matchPattern($route, $uri)) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Match route pattern and extract parameters
     * Example: /posts/{id} matches /posts/123
     */
    private function matchPattern(string $pattern, string $uri): bool
    {
        // Convert pattern to regex
        $regexPattern = preg_quote($pattern, '/');
        $regexPattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $regexPattern);
        
        if (preg_match('/^' . $regexPattern . '$/', $uri, $matches)) {
            // Store only named parameters (not numeric keys)
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Get all parameters from matched route
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 404 handler
     */
    private function notFound(): void
    {
        http_response_code(404);
        die('404 - Page not found');
    }
}
