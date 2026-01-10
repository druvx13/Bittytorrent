<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT"))
      die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $smarty, $hook;
$db = DB::getInstance();
      
if (!empty($_POST["submit"])) {

        $prefix_db = $startUp->prefix_db ?? '';

        foreach($_POST as $key=>$value) {
            // Exclude keys not in settings? Or trust the form?
            // Safer to check if key exists or just update.
            // Using prepared statement logic via escaped values.

            // $value could be array if input is array? Cast to string if needed or handle arrays.
            if (is_array($value)) $value = serialize($value);

			$db->query("UPDATE ".$prefix_db."settings SET
			`value` = '".$db->escape($value)."' WHERE 
			`key` = '".$db->escape($key)."'");
        }
}
$smarty->assign("getConfigs",$startUp->getConfigs()); 
$smarty->assign("getThemes",$startUp->getThemes('themes')); 
 

if ($hook->hook_exist('admin_settings_page'))  
	$hook->execute_hook('admin_settings_page');
