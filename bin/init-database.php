#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Database Initialization Script
 * 
 * Creates MySQL database and initializes schema
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'bittytorrent';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';
$schemaPath = __DIR__ . '/../config/schema.sql';

echo "Bittytorrent Database Initialization\n";
echo "=====================================\n\n";

echo "Database Configuration:\n";
echo "  Host: $dbHost:$dbPort\n";
echo "  Database: $dbName\n";
echo "  User: $dbUser\n\n";

try {
    // Connect to MySQL server (without selecting database)
    $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "Warning: Database '$dbName' already exists.\n";
        echo "Do you want to DROP and recreate it? This will DELETE all data! (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) === 'yes') {
            echo "Dropping existing database...\n";
            $pdo->exec("DROP DATABASE `$dbName`");
            echo "Database dropped.\n";
        } else {
            echo "Using existing database.\n";
            // Switch to the database
            $pdo->exec("USE `$dbName`");
        }
    }
    
    // Create database if it doesn't exist
    if (!$dbExists || strtolower($line ?? '') === 'yes') {
        echo "Creating database '$dbName'...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database created successfully.\n";
    }
    
    // Switch to the database
    $pdo->exec("USE `$dbName`");
    
    // Read and execute schema
    echo "Loading schema from: $schemaPath\n";
    $schema = file_get_contents($schemaPath);
    
    if ($schema === false) {
        throw new Exception("Failed to read schema file");
    }
    
    echo "Executing schema...\n";
    
    // Split schema into individual statements and execute them
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        fn($stmt) => !empty($stmt) && !preg_match('/^\s*--/', $stmt)
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "\n✓ Database initialized successfully!\n\n";
    
    // Display default credentials
    echo "Default Admin Credentials:\n";
    echo "==========================\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "\n⚠️  IMPORTANT: Change the default password immediately!\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
