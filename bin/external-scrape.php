#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * External Scrape Script
 * 
 * Scrapes statistics from external BitTorrent trackers
 * Run via cron: 0 */2 * * * /usr/bin/php /path/to/bin/external-scrape.php
 */

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use Bittytorrent\Application;
use Bittytorrent\Service\ExternalScrape;

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

try {
    echo "==============================================\n";
    echo "External Scrape Script\n";
    echo "Started: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n\n";

    // Initialize application
    $app = new Application();
    $db = $app->getDatabase();
    $logger = $app->getLogger();

    // Create external scrape service
    $externalScrape = new ExternalScrape($db, $logger);

    echo "Scraping all torrents from external trackers...\n\n";

    // Run scrape
    $stats = $externalScrape->scrapeAllTorrents();

    // Display results
    echo "\n==============================================\n";
    echo "Scrape Complete\n";
    echo "==============================================\n";
    echo "Torrents scraped: " . $stats['torrents_scraped'] . "\n";
    echo "Successes: " . $stats['successes'] . "\n";
    echo "Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "==============================================\n";

    exit(0);

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
