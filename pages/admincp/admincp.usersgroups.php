<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT"))
      die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $smarty, $db;
$db = DB::getInstance();

$smarty->assign('getAllgroups',$startUp->getAllgroups());

if (isset($_POST['name'])) {
	$pk = (int)$_POST['pk']; // Primary Key usually
	$name = $db->escape($_POST['name']); // Column name
	$value = $db->escape($_POST['value']); // New value
	
    // Validate $name against allowed column names to prevent SQL injection via column name
    // Assuming we have a list of columns or get it from DB
    // For now, simple alphanumeric check
    if (preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
	    $db->query("UPDATE `users_group` SET `{$name}` = '{$value}' WHERE `id` ={$pk}");
    }
exit;
}
