<?php

use App\Tracker\PeerTracker;

if (!defined("IN_TORRENT")) die("Access denied!");

ignore_user_abort(true);
 
// Tracker Configuration
// Assuming $conf is available here (from startup or included before)
// Actually announce.php might be called directly via index.php?page=announce
// index.php includes startup.php, so $conf should be available.

$_SERVER['tracker'] = array(
	// general tracker options
	'open_tracker'      => $conf['open_tracker'],          /* track anything announced to it */
	'announce_interval' => (int) $conf['announce_interval'],          /* how often client will send requests */
	'min_interval'      => (int) $conf['min_interval'],           /* how often client can force requests */
	'default_peers'     => (int) $conf['default_peers'],            /* default # of peers to announce */
	'max_peers'         => (int) $conf['max_peers'],           /* max # of peers to announce */

	// advanced tracker options
	'external_ip'       => $conf['external_ip'],          /* allow client to specify ip address */
	'force_compact'     => $conf['force_compact'],         /* force compact announces only */
	'full_scrape'       => $conf['full_scrape'],         /* allow scrapes without info_hash */
	'random_limit'      => 500,           /* if peers > #, use alternate SQL RAND() */
	'clean_idle_peers'  => 1,            /* tweaks % of time tracker attempts idle peer removal */
	'db_prefix'         => '',         /* name prefixes for the PeerTracker tables */
    'seeding'           => 0
);

// Verify Request //////////////////////////////////////////////////////////////////////////////////

// 20-bytes - info_hash
// sha-1 hash of torrent metainfo
if (!isset($_GET['info_hash']) || strlen($_GET['info_hash']) != 20) exit;

// 20-bytes - peer_id
// client generated unique peer identifier
if (!isset($_GET['peer_id']) || strlen($_GET['peer_id']) != 20) exit;

// integer - port
// port the client is accepting connections from
if (!(isset($_GET['port']) && is_numeric($_GET['port']))) exit('d14:failure reason28:client listening port invalide');

// integer - left
// number of bytes left for the peer to download
if (isset($_GET['left']) && is_numeric($_GET['left'])) $_SERVER['tracker']['seeding'] = ($_GET['left'] > 0 ? 0 : 1); else exit('d14:failure reason25:client data left invalid e');

// integer boolean - compact - optional
if (!isset($_GET['compact']) || $_SERVER['tracker']['force_compact'] === 'true') $_GET['compact'] = 1; else $_GET['compact'] += 0;
 
// integer boolean - no_peer_id - optional
if (!isset($_GET['no_peer_id'])) $_GET['no_peer_id'] = 0; else $_GET['no_peer_id'] += 0;

// string - ip - optional
if (isset($_GET['ip']) && $_SERVER['tracker']['external_ip'] === 'true')
{
	$_GET['ip'] = trim($_GET['ip'],'::ffff:');
	if (!ip2long($_GET['ip'])) exit('d14:failure reason10:invalid ipe');
}
// set ip to connected client
elseif (isset($_SERVER['REMOTE_ADDR'])) $_GET['ip'] = trim($_SERVER['REMOTE_ADDR'],'::ffff:');
// cannot locate suitable ip, must abort
else exit('d14:failure reason24:could not locate client ipe');

// integer - numwant - optional
if (!isset($_GET['numwant'])) $_GET['numwant'] = $_SERVER['tracker']['default_peers'];
elseif (($_GET['numwant']+0) > $_SERVER['tracker']['max_peers']) $_GET['numwant'] = $_SERVER['tracker']['max_peers'];
else $_GET['numwant'] += 0;


// Handle Request //////////////////////////////////////////////////////////////////////////////////

// open database
PeerTracker::open();

// Only for private tracking
PeerTracker::updateUser();

// announce peers
PeerTracker::peers();

// track client
PeerTracker::event();

// garbage collection
PeerTracker::clean();

// close database
PeerTracker::close();

?>
