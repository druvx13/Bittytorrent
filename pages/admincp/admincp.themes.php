<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT"))
      die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $smarty;
$db = DB::getInstance();

$getThemes = $startUp->getThemes('themes');

if (isset($_GET['theme'])) {
	if (in_array($_GET['theme'], $getThemes)) { 
			$db->query("UPDATE settings SET `value` = '".$db->escape($_GET['theme'])."' WHERE `settings`.`key` = 'theme'");		
			$startUp->redirect($conf['baseurl'].'/admincp/themes/?tokenAdmin='.($_COOKIE['tokenAdmin'] ?? ''));
	}
}

$smarty->assign("getThemes",$getThemes);
