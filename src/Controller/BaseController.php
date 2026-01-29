<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Application;
use Twig\Environment;
use Monolog\Logger;

/**
 * Base Controller
 * 
 * Provides common functionality for all controllers
 */
abstract class BaseController
{
    protected Application $app;
    protected Environment $twig;
    protected Logger $logger;
    
    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->twig = $this->app->getTwig();
        $this->logger = $this->app->getLogger();
    }
    
    /**
     * Render template
     */
    protected function render(string $template, array $data = []): void
    {
        // Add common data
        $data['csrf_token'] = $this->app->generateCsrfToken();
        $data['user'] = $this->getCurrentUser();
        $data['is_logged_in'] = isset($_SESSION['user_id']);
        $data['is_admin'] = $this->isAdmin();
        
        echo $this->twig->render($template, $data);
    }
    
    /**
     * Get current logged-in user
     */
    protected function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new \Bittytorrent\Model\User();
        return $userModel->findById((int)$_SESSION['user_id']);
    }
    
    /**
     * Check if current user is logged in
     */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if current user is admin
     */
    protected function isAdmin(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }
    }
    
    /**
     * Require admin role
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            http_response_code(403);
            $this->render('error/403.twig', ['title' => '403 Forbidden']);
            exit;
        }
    }
    
    /**
     * Verify CSRF token
     */
    protected function verifyCsrf(string $token): bool
    {
        return $this->app->verifyCsrfToken($token);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Return JSON response
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
