#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Database Initialization Script
 * 
 * Creates SQLite database and initializes schema
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$dbPath = __DIR__ . '/../' . ($_ENV['DB_DATABASE'] ?? 'var/database/bittytorrent.sqlite');
$schemaPath = __DIR__ . '/../config/schema.sql';

echo "Bittytorrent Database Initialization\n";
echo "=====================================\n\n";

// Create database directory if it doesn't exist
$dbDir = dirname($dbPath);
if (!is_dir($dbDir)) {
    echo "Creating database directory: $dbDir\n";
    mkdir($dbDir, 0755, true);
}

// Check if database exists
if (file_exists($dbPath)) {
    echo "Warning: Database already exists at: $dbPath\n";
    echo "Recreating database (this will DELETE all existing data)...\n";
    unlink($dbPath);
    echo "Deleted existing database.\n";
}

try {
    // Create database connection
    echo "Creating database at: $dbPath\n";
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema
    echo "Loading schema from: $schemaPath\n";
    $schema = file_get_contents($schemaPath);
    
    if ($schema === false) {
        throw new Exception("Failed to read schema file");
    }
    
    echo "Executing schema...\n";
    $pdo->exec($schema);
    
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
