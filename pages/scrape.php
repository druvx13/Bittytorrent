<?php
// Modernized announce/scrape entry point
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../libs/db.php";

use BittyTorrent\Database\DB;
use BittyTorrent\Tracker\Tracker;

// Load config
$db = new DB($dbhost, $dbuser, $dbpass, $dbname);

// Fetch settings from DB
$settings = [];
$rows = $db->get_results("SELECT `key`, `value` FROM settings");
if ($rows) {
    foreach ($rows as $row) {
        $settings[$row->key] = $row->value;
    }
}

$config = [
    "announce_interval" => (int)($settings["announce_interval"] ?? 1800),
    "min_interval" => (int)($settings["min_interval"] ?? 900),
    "default_peers" => (int)($settings["default_peers"] ?? 50),
    "max_peers" => (int)($settings["max_peers"] ?? 200),
    "full_scrape" => ($settings["full_scrape"] ?? "false") === "true",
];

$tracker = new Tracker($db, $config);
$tracker->handleRequest();

