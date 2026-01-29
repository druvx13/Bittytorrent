<?php

declare(strict_types=1);

namespace Bittytorrent;

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use PDO;
use PDOException;

/**
 * Main Application Class
 * 
 * Bootstraps and manages the application core services
 */
class Application
{
    private static ?Application $instance = null;
    private PDO $db;
    private Environment $twig;
    private Logger $logger;
    private array $config = [];
    
    private function __construct()
    {
        $this->loadEnvironment();
        $this->initializeLogger();
        $this->initializeDatabase();
        $this->initializeTwig();
        $this->startSession();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load environment variables
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
        
        $this->config = [
            'app_name' => $_ENV['APP_NAME'] ?? 'Bittytorrent',
            'app_env' => $_ENV['APP_ENV'] ?? 'production',
            'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
            'db_connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_port' => $_ENV['DB_PORT'] ?? '3306',
            'db_database' => $_ENV['DB_DATABASE'] ?? 'bittytorrent',
            'db_username' => $_ENV['DB_USERNAME'] ?? 'root',
            'db_password' => $_ENV['DB_PASSWORD'] ?? '',
            'log_file' => __DIR__ . '/../' . ($_ENV['LOG_FILE'] ?? 'var/logs/app.log'),
            'session_name' => $_ENV['SESSION_NAME'] ?? 'bittytorrent_session',
            'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
            'tracker_open' => filter_var($_ENV['TRACKER_OPEN'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'announce_interval' => (int)($_ENV['ANNOUNCE_INTERVAL'] ?? 1800),
            'min_interval' => (int)($_ENV['MIN_INTERVAL'] ?? 900),
            'default_peers' => (int)($_ENV['DEFAULT_PEERS'] ?? 50),
            'max_peers' => (int)($_ENV['MAX_PEERS'] ?? 50),
        ];
    }
    
    /**
     * Initialize logger
     */
    private function initializeLogger(): void
    {
        $this->logger = new Logger('bittytorrent');
        $logDir = dirname($this->config['log_file']);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logger->pushHandler(
            new StreamHandler($this->config['log_file'], Logger::DEBUG)
        );
    }
    
    /**
     * Initialize database connection
     */
    private function initializeDatabase(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $this->config['db_host'],
                $this->config['db_port'],
                $this->config['db_database']
            );
            
            $this->db = new PDO(
                $dsn,
                $this->config['db_username'],
                $this->config['db_password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            $this->logger->info('Database connection established');
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Initialize Twig templating engine
     */
    private function initializeTwig(): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $this->twig = new Environment($loader, [
            'cache' => $this->config['app_debug'] ? false : __DIR__ . '/../var/cache/twig',
            'debug' => $this->config['app_debug'],
            'auto_reload' => $this->config['app_debug'],
        ]);
        
        // Add global variables
        $this->twig->addGlobal('app_name', $this->config['app_name']);
        $this->twig->addGlobal('app_url', $this->config['app_url']);
    }
    
    /**
     * Start secure session
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            // Set cookie_secure based on HTTPS detection
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                       || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            ini_set('session.cookie_secure', $isHttps ? '1' : '0');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.gc_maxlifetime', (string)$this->config['session_lifetime']);
            
            session_name($this->config['session_name']);
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Get database connection
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
    
    /**
     * Get Twig environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }
    
    /**
     * Get logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    
    /**
     * Get configuration value
     */
    public function getConfig(string $key = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
