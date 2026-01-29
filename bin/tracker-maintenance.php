#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Tracker Maintenance Script
 * 
 * This script should be run periodically (via cron) to:
 * - Clean up old/inactive peers
 * - Update torrent statistics (seeders, leechers)
 * 
 * Recommended cron schedule: Every 15-30 minutes
 * Example crontab entry:
 * */15 * * * * /usr/bin/php /path/to/bittytorrent/bin/tracker-maintenance.php
 */

// Prevent running from web
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Bittytorrent\Application;
use Bittytorrent\Service\Tracker;

try {
    echo "==============================================\n";
    echo "BitTorrent Tracker Maintenance\n";
    echo "==============================================\n";
    echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Initialize application
    $app = Application::getInstance();
    $tracker = new Tracker();
    
    // Step 1: Clean old peers
    echo "1. Cleaning old/inactive peers...\n";
    $deletedPeers = $tracker->cleanOldPeers();
    echo "   ✓ Removed $deletedPeers inactive peer(s)\n\n";
    
    // Step 2: Update all torrent statistics
    echo "2. Updating torrent statistics...\n";
    $updatedTorrents = $tracker->updateAllTorrentStats();
    echo "   ✓ Updated stats for $updatedTorrents torrent(s)\n\n";
    
    echo "==============================================\n";
    echo "Maintenance completed successfully!\n";
    echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
