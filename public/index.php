<?php

declare(strict_types=1);

/**
 * Bittytorrent - Modern BitTorrent Tracker
 * 
 * Front controller - Single entry point for all requests
 */

// Check if Composer dependencies are installed
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    http_response_code(500);
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dependencies Not Installed - BitTorrent Tracker</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                   background: #f5f5f5; padding: 20px; color: #333; }
            .container { max-width: 800px; margin: 0 auto; background: white; 
                        padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #d32f2f; margin-top: 0; }
            h2 { color: #1976d2; margin-top: 30px; }
            code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; 
                   font-family: "Courier New", monospace; }
            pre { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; 
                  overflow-x: auto; }
            .error { background: #ffebee; border-left: 4px solid #d32f2f; padding: 15px; margin: 20px 0; }
            .info { background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; margin: 20px 0; }
            .step { margin: 15px 0; padding-left: 10px; }
            ul { line-height: 1.8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Dependencies Not Installed</h1>
            
            <div class="error">
                <strong>Error:</strong> The application dependencies are not installed. 
                The <code>vendor/autoload.php</code> file is missing.
            </div>
            
            <div class="info">
                This error typically occurs during deployment when Composer dependencies 
                have not been installed on the server.
            </div>
            
            <h2>🔧 How to Fix This Issue</h2>
            
            <div class="step">
                <h3>Option 1: Install Composer on Your Server (Recommended)</h3>
                <p>If you have SSH access to your server:</p>
                <ol>
                    <li>Connect to your server via SSH</li>
                    <li>Navigate to your application directory:<br>
                        <pre>cd /path/to/your/application</pre>
                    </li>
                    <li>Run Composer install (production mode):<br>
                        <pre>composer install --no-dev --optimize-autoloader</pre>
                    </li>
                    <li>Refresh this page</li>
                </ol>
            </div>
            
            <div class="step">
                <h3>Option 2: Upload Vendor Directory (Alternative)</h3>
                <p>If you cannot install Composer on your server:</p>
                <ol>
                    <li>On your local machine, run:<br>
                        <pre>composer install --no-dev --optimize-autoloader</pre>
                    </li>
                    <li>Upload the generated <code>vendor/</code> directory to your server</li>
                    <li>Make sure it\'s in the same directory as <code>composer.json</code></li>
                    <li>Refresh this page</li>
                </ol>
                <p><strong>Note:</strong> This is not recommended for regular deployments 
                   but can work as a temporary solution.</p>
            </div>
            
            <h2>📋 Additional Deployment Steps</h2>
            <ul>
                <li>Copy <code>.env.example</code> to <code>.env</code> and configure your database</li>
                <li>Run <code>php bin/init-database.php</code> to initialize the database</li>
                <li>Set proper file permissions (writable: <code>var/logs</code>, <code>var/cache</code>, <code>public/uploads</code>)</li>
                <li>Point your web server to the <code>public/</code> directory</li>
            </ul>
            
            <h2>📚 Need More Help?</h2>
            <p>Check the following documentation files in your application:</p>
            <ul>
                <li><code>DEPLOYMENT.md</code> - Complete deployment guide</li>
                <li><code>INSTALL.md</code> - Quick installation reference</li>
                <li><code>GETTING_STARTED.md</code> - Beginner\'s guide</li>
            </ul>
            
            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666;">
                <strong>Technical Details:</strong><br>
                Missing file: <code>' . $autoloadFile . '</code><br>
                Current directory: <code>' . __DIR__ . '</code>
            </p>
        </div>
    </body>
    </html>
    ');
}

// Require Composer autoloader
require_once $autoloadFile;

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
