<?php

namespace LuxuryWatch\BI\API;

use Exception;

/**
 * Simple API Router
 * Handles routing for RESTful API endpoints
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    
    /**
     * Add GET route
     */
    public function get(string $path, callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Add POST route
     */
    public function post(string $path, callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Add PUT route
     */
    public function put(string $path, callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Add DELETE route
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Add route with method
     */
    private function addRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $this->middleware
        ];
        
        return $this;
    }
    
    /**
     * Add middleware
     */
    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Clear middleware
     */
    public function clearMiddleware(): self
    {
        $this->middleware = [];
        return $this;
    }
    
    /**
     * Handle incoming request
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle OPTIONS requests for CORS
        if ($method === 'OPTIONS') {
            $this->sendCorsHeaders();
            exit;
        }
        
        $route = $this->findRoute($method, $path);
        
        if (!$route) {
            $this->sendNotFound();
            return;
        }
        
        try {
            // Execute middleware
            foreach ($route['middleware'] as $middleware) {
                $result = $middleware();
                if ($result === false) {
                    return; // Middleware stopped execution
                }
            }
            
            // Execute route handler
            $params = $this->extractParams($route['path'], $path);
            $result = call_user_func($route['handler'], $params);
            
            if (is_array($result)) {
                $this->sendJson($result);
            }
            
        } catch (Exception $e) {
            $this->sendError($e);
        }
    }
    
    /**
     * Find matching route
     */
    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->pathMatches($route['path'], $path)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Check if path matches route pattern
     */
    private function pathMatches(string $pattern, string $path): bool
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $path);
    }
    
    /**
     * Extract parameters from path
     */
    private function extractParams(string $pattern, string $path): array
    {
        $params = [];
        
        // Extract parameter names from pattern
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        // Extract values from path
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // Remove full match
            
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }
    
    /**
     * Send JSON response
     */
    private function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        $this->sendCorsHeaders();
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Send error response
     */
    private function sendError(Exception $e): void
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        $this->sendJson([
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ], $statusCode);
    }
    
    /**
     * Send 404 response
     */
    private function sendNotFound(): void
    {
        $this->sendJson([
            'error' => true,
            'message' => 'Endpoint not found'
        ], 404);
    }
    
    /**
     * Send CORS headers
     */
    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }
}
