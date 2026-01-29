<?php

declare(strict_types=1);

/**
 * Bittytorrent - Modern BitTorrent Tracker
 * 
 * Front controller - Single entry point for all requests
 */

// Require Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Bittytorrent\Application;
use Bittytorrent\Router;
use Bittytorrent\Controller\HomeController;
use Bittytorrent\Controller\AuthController;
use Bittytorrent\Controller\TorrentController;
use Bittytorrent\Controller\TrackerController;
use Bittytorrent\Controller\AdminController;
use Bittytorrent\Controller\UserController;
use Bittytorrent\Controller\RSSController;

// Check if running from CLI
if (php_sapi_name() === 'cli') {
    die("This script must be run through a web server.\n");
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($exception) {
    http_response_code(500);
    
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG']) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Internal Server Error</h1>';
        echo '<p>An error occurred. Please try again later.</p>';
    }
    
    // Log error
    if (class_exists('Bittytorrent\Application')) {
        try {
            $app = Application::getInstance();
            $app->getLogger()->error('Uncaught exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        } catch (\Exception $e) {
            // Ignore logging errors
        }
    }
});

// Initialize application
try {
    $app = Application::getInstance();
    
    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // CSP header
    if (!$app->getConfig('app_debug')) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' cdn.jsdelivr.net;");
    }
    
    // Create router
    $router = new Router();
    
    // Define routes
    
    // Home
    $router->get('/', function () {
        $controller = new HomeController();
        $controller->index();
    });
    
    // Authentication
    $router->get('/login', function () {
        $controller = new AuthController();
        $controller->showLogin();
    });
    
    $router->post('/login', function () {
        $controller = new AuthController();
        $controller->login();
    });
    
    $router->get('/register', function () {
        $controller = new AuthController();
        $controller->showRegister();
    });
    
    $router->post('/register', function () {
        $controller = new AuthController();
        $controller->register();
    });
    
    $router->get('/logout', function () {
        $controller = new AuthController();
        $controller->logout();
    });
    
    // User Profile
    $router->get('/profile', function () {
        $controller = new AuthController();
        $controller->showProfile();
    });
    
    $router->post('/profile', function () {
        $controller = new AuthController();
        $controller->updateProfile();
    });
    
    // Admin Panel
    $router->get('/admin', function () {
        $controller = new AdminController();
        $controller->dashboard();
    });
    
    $router->get('/admin/users', function () {
        $controller = new AdminController();
        $controller->users();
    });
    
    $router->post('/admin/users/delete', function () {
        $controller = new AdminController();
        $controller->deleteUser();
    });
    
    $router->get('/admin/torrents', function () {
        $controller = new AdminController();
        $controller->torrents();
    });
    
    $router->post('/admin/torrents/delete', function () {
        $controller = new AdminController();
        $controller->deleteTorrent();
    });
    
    $router->get('/admin/settings', function () {
        $controller = new AdminController();
        $controller->settings();
    });
    
    $router->get('/admin/categories', function () {
        $controller = new AdminController();
        $controller->categories();
    });
    
    $router->post('/admin/categories/add', function () {
        $controller = new AdminController();
        $controller->addCategory();
    });
    
    $router->post('/admin/categories/edit', function () {
        $controller = new AdminController();
        $controller->editCategory();
    });
    
    $router->post('/admin/categories/delete', function () {
        $controller = new AdminController();
        $controller->deleteCategory();
    });
    
    // Torrents
    $router->get('/browse', function () {
        $controller = new TorrentController();
        $controller->browse();
    });
    
    $router->get('/browse/category/:id', function ($id) {
        $_GET['category'] = $id;
        $controller = new TorrentController();
        $controller->browse();
    });
    
    $router->get('/torrent/:id', function ($id) {
        $controller = new TorrentController();
        $controller->show($id);
    });
    
    $router->get('/upload', function () {
        $controller = new TorrentController();
        $controller->showUpload();
    });
    
    $router->post('/upload', function () {
        $controller = new TorrentController();
        $controller->upload();
    });
    
    $router->get('/download/:id', function ($id) {
        $controller = new TorrentController();
        $controller->download($id);
    });
    
    // Users
    $router->get('/user/:id', function ($id) {
        $controller = new UserController();
        $controller->show($id);
    });
    
    // RSS Feed
    $router->get('/rss', function () {
        $controller = new RSSController();
        $controller->feed();
    });
    
    // Tracker endpoints
    $router->get('/announce', function () {
        $controller = new TrackerController();
        $controller->announce();
    });
    
    $router->get('/scrape', function () {
        $controller = new TrackerController();
        $controller->scrape();
    });
    
    // Dispatch request
    $router->dispatch();
    
} catch (\Exception $e) {
    http_response_code(500);
    echo '<h1>Fatal Error</h1>';
    echo '<p>Application failed to initialize.</p>';
    
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG']) {
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
