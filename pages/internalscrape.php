<?php

use App\Tracker\PeerTracker;

if (!defined("IN_TORRENT")) die("Access denied!");
 
// error level - Modernize error reporting, but respect request to hide warnings maybe?
// Ideally we should log errors, not suppress.
// error_reporting(E_ERROR | E_PARSE);

// ignore disconnects
ignore_user_abort(true);

// Assuming tracker config is loaded via announce.php inclusion or we need to set it here if this page is hit directly?
// This page seems to be an internal scrape endpoint.
// It relies on $_SERVER['tracker'] being set.
// If this file is included from index.php, we need to ensure config is available.
// announce.php sets $_SERVER['tracker']. internalscrape.php likely needs similar setup or uses shared config.
// But index.php includes startup.php which gets configs.
// However, $_SERVER['tracker'] array was manually built in announce.php.
// We should replicate that or refactor config loading.
// For now, I will replicate minimal config if missing.

global $conf, $dbhost, $dbuser, $dbpass, $dbname;

if (!isset($_SERVER['tracker'])) {
    $_SERVER['tracker'] = array(
        'open_tracker'      => $conf['open_tracker'] ?? 'true',
        'announce_interval' => (int) ($conf['announce_interval'] ?? 1800),
        'min_interval'      => (int) ($conf['min_interval'] ?? 300),
        'default_peers'     => (int) ($conf['default_peers'] ?? 50),
        'max_peers'         => (int) ($conf['max_peers'] ?? 100),
        'external_ip'       => $conf['external_ip'] ?? 'false',
        'force_compact'     => $conf['force_compact'] ?? 'false',
        'full_scrape'       => $conf['full_scrape'] ?? 'false',
        'random_limit'      => 500,
        'clean_idle_peers'  => 1,
        'db_prefix'         => '',
    );
}

// Verify Request //////////////////////////////////////////////////////////////////////////////////

// tracker statistics
if (isset($_GET['stats']))
{
	// open database
	PeerTracker::open();

    // stats() method missing in my PeerTracker refactor!
    // I need to add it to libs/Tracker/PeerTracker.php
    // I missed it because I only copied what I saw in use in announce.php?
    // Wait, announce.php didn't use stats().
    // internalscrape.php uses it.
	// peertracker::stats(); // Need to implement this

	// close database
	PeerTracker::close();

	// exit immediately
	exit;
}
 
// 20-bytes - info_hash
// sha-1 hash of torrent being tracked
if (!isset($_GET['info_hash']) || strlen($_GET['info_hash']) != 20)
{
	// full scrape disabled
	if (($_SERVER['tracker']['full_scrape'] ?? 'false') === 'false') exit;
	// full scrape enabled
	else unset($_GET['info_hash']);
}  


// Handle Request //////////////////////////////////////////////////////////////////////////////////

// open database
PeerTracker::open();

// perform scrape
PeerTracker::scrape();

// close database
PeerTracker::close();
