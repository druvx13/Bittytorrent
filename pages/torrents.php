<?php
// Modernized Torrents Listing
use BittyTorrent\Database\DB;

if (!defined('IN_TORRENT')) die('Access denied!');

$smarty->assign('title', 'Torrents');

// Pagination logic would go here, simplified for now
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$rows = $db->get_results("SELECT * FROM torrents ORDER BY date DESC LIMIT $limit OFFSET $offset", 'ARRAY_A');
$smarty->assign('torrents', $rows);

$smarty->display('torrents.html');

