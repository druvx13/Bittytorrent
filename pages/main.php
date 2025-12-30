<?php
// Modernized Main Page Logic
if (!defined('IN_TORRENT')) die('Access denied!');

$smarty->assign('title', 'Welcome to BittyTorrent');
$smarty->assign('content', 'This is the modernized homepage.');
$smarty->display('index.html');

