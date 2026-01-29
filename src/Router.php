<?php

declare(strict_types=1);

namespace Bittytorrent;

/**
 * Simple Router
 * 
 * Routes HTTP requests to appropriate controllers
 */
class Router
{
    private array $routes = [];
    private string $basePath = '';
    
    /**
     * Add GET route
     */
    public function get(string $path, callable $callback): void
    {
        $this->addRoute('GET', $path, $callback);
    }
    
    /**
     * Add POST route
     */
    public function post(string $path, callable $callback): void
    {
        $this->addRoute('POST', $path, $callback);
    }
    
    /**
     * Add route for any method
     */
    public function any(string $path, callable $callback): void
    {
        $this->addRoute('GET|POST', $path, $callback);
    }
    
    /**
     * Add route
     */
    private function addRoute(string $method, string $path, callable $callback): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
        ];
    }
    
    /**
     * Dispatch request
     */
    public function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if set
        if ($this->basePath) {
            $requestUri = preg_replace('#^' . preg_quote($this->basePath) . '#', '', $requestUri);
        }
        
        // Remove trailing slash except for root
        if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
            $requestUri = rtrim($requestUri, '/');
        }
        
        foreach ($this->routes as $route) {
            // Check if method matches
            if (!preg_match('#^(' . $route['method'] . ')$#', $requestMethod)) {
                continue;
            }
            
            // Convert route pattern to regex
            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $requestUri, $matches)) {
                // Remove full match
                array_shift($matches);
                
                // Call callback with parameters
                call_user_func_array($route['callback'], $matches);
                return;
            }
        }
        
        // No route found - 404
        $this->notFound();
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $path);
        
        // Convert :id to numeric regex group, :slug to alphanumeric
        $pattern = preg_replace('/\:id/', '([0-9]+)', $pattern);
        $pattern = preg_replace('/\:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Handle 404
     */
    private function notFound(): void
    {
        http_response_code(404);
        
        $app = Application::getInstance();
        $twig = $app->getTwig();
        
        try {
            echo $twig->render('error/404.twig', [
                'title' => '404 Not Found',
                'csrf_token' => $app->generateCsrfToken(),
            ]);
        } catch (\Exception $e) {
            echo '<h1>404 Not Found</h1><p>The requested page was not found.</p>';
        }
    }
    
    /**
     * Set base path for routing
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }
}
