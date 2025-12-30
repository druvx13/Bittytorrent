<?php
// Modernized Torrent Detail
if (!defined('IN_TORRENT')) die('Access denied!');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('Invalid ID');

$torrent = $db->get_row("SELECT * FROM torrents WHERE id = $id", 'ARRAY_A');
if (!$torrent) die('Torrent not found');

$smarty->assign('title', $torrent['title']);
$smarty->assign('torrent', $torrent);

$smarty->display('detail.html');

