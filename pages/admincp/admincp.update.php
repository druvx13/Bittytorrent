<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT"))
      die("Access denied!");
      
// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $smarty, $path;
$db = DB::getInstance();

$smarty->assign('returnUpdate',false);
  
if (isset($_GET['updateNow'])) {

    // Assuming Update.class.php is legacy or needs refactor.
    // For now requiring as is if it exists, otherwise skipping logic.
    if (file_exists($path.'/libs/Update.class.php')) {
        require($path.'/libs/Update.class.php');

        // Check if class exists
        if (class_exists('AutoUpdate')) {
            $update = new \AutoUpdate(true);
            $update->currentVersion = 2; //Must be an integer - you can't compare strings
            $update->updateUrl = 'http://localhost/libs'; //Replace with your server update directory

            //Check for a new update
            $latest = $update->checkUpdate();

            if ($latest !== false) {
            // var_dump($update->currentVersion);
                if ($latest > $update->currentVersion) {
                    //Install new update
                    $rUpdate = "";
                    $rUpdate .=  "New Version: ".$update->latestVersionName."<br />";
                    $rUpdate .= "Installing Update...<br />";
                    if ($update->update())
                        $rUpdate .= "Update successful!";
                    else
                        $rUpdate .= "Update failed!";
                }
                else
                    $rUpdate .= "Current Version is up to date";
            }
            else {
                $rUpdate = $update->getLastError();
            }

            $smarty->assign('returnUpdate',$rUpdate);
        }
    }

}  else {

 
	$version = $db->get_row("SELECT value FROM `settings` WHERE `key` LIKE 'version'");
    $currentV = '';
    $currentC = '';

    if ($version) {
	    $currentVObj = unserialize($version->value);
        if (is_array($currentVObj)) {
	        $currentV = $currentVObj['version'] ?? '';
	        $currentC = $currentVObj['commit'] ?? '';
        }
    }

    $smarty->assign('currentV', $currentV);
    $smarty->assign('currentC', $currentC);
 	
	// Using modern CURL logic via StartUp
	$lasteV = $startUp->get_web_page('https://raw.githubusercontent.com/atmoner/Bittytorrent/master/version?access_token=00d6ca368c3f725bb1ba63663debd5dc212da0c2');

    // Check if content exists
    if (isset($lasteV['content']) && !empty($lasteV['content'])) {
	    $jsonObj = json_decode($lasteV['content']);
        if ($jsonObj) {
	        $smarty->assign('lasteV', $jsonObj->version ?? '');
	        $smarty->assign('lasteC', $jsonObj->commit ?? '');
	        $smarty->assign('updateValue', $jsonObj->updateValue ?? '');
	        $smarty->assign('updateMessage', $jsonObj->updateMessage ?? '');
        }
    } else {
        $smarty->assign('lasteV', 'Unknown');
        $smarty->assign('lasteC', 'Unknown');
        $smarty->assign('updateValue', '');
        $smarty->assign('updateMessage', '');
    }
}
