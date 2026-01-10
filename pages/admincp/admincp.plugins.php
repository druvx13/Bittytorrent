<?php

use App\Core\Bittytorrent;
use App\Core\Hooks;
use App\Database\DB;

if (!defined("IN_TORRENT"))
      die("Access denied!"); 

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $smarty, $hook;
$db = DB::getInstance();
 
$sub = $_GET["sub"] ?? "";

if (!empty($sub)) {

	
} else {


	function getPlugins(string $where=NULL, string $value=NULL): array{
		$db = DB::getInstance();
		$sql = "SELECT filename, action FROM plugins";
		if($where === 'action')
		$sql .= " WHERE action = '".$db->escape($value)."' ";
		if($where === 'filename')
		$sql .= " WHERE filename = '".$db->escape($value)."' ";
		$items = $db->get_results($sql);
		
        $array = [];
        if ($items) {
		    foreach ( $items as $obj ){
			    $array[$obj->filename]['filename'] = $obj->filename;
			    $array[$obj->filename]['action'] = $obj->action;
	        }
        }
 
		return $array;
	}

$action = $_GET["action"] ?? "";
if (isset($_GET['filename'])) {
    $_GET['filename'] = $db->escape($_GET['filename']);
}

switch ($action) {

	case "deactivate" :
	    if (isset($_GET['filename'])) {
		$ext = explode('.',$_GET['filename']);
		    if(function_exists('uninstall_'.$ext[0])) {
			    $function = 'uninstall_'.$ext[0];
			    $function();
		    }

		    $db->query("UPDATE plugins SET action='0' WHERE filename= '".$_GET['filename']."'");
		    $startUp->redirect($conf['baseurl'].'/admincp/plugins/?tokenAdmin='.($_COOKIE['tokenAdmin'] ?? ''));
        }
		break;
		
	case "activate" :
        if (isset($_GET['filename'])) {
		    $count = count (getPlugins('filename',$_GET['filename']));
		    if ($count < 1) {
			    $db->query("INSERT INTO plugins (filename, action) VALUES ('".$_GET['filename']."',1)");
		    } else {
			    $db->query("UPDATE plugins SET action='1' WHERE filename= '".$_GET['filename']."'");
		    }
		    $startUp->redirect($conf['baseurl'].'/admincp/plugins/?action=installSql&filename='.$_GET['filename'].'&tokenAdmin='.($_COOKIE['tokenAdmin'] ?? ''));
        }
		break;
		
	case "installSql" :
        if (isset($_GET['filename'])) {
		$ext = explode('.',$_GET['filename']);
		    if(function_exists('install_'.$ext[0])) {
			    $function = 'install_'.$ext[0];
			    $function();
		    }
		    $startUp->redirect($conf['baseurl'].'/admincp/plugins/?tokenAdmin='.($_COOKIE['tokenAdmin'] ?? ''));
        }
		break;
}
 
 
$plugin_list = new Hooks();
$plugin_headers = $plugin_list->get_plugins_header();

$api=array();
$i=0;
 
  foreach ($plugin_headers as $tid=>$plugin_header) { 
			$action = false;
    $plugins = getPlugins();
	foreach ( $plugins as $result_row )
		if ($plugin_header['filename'] == $result_row['filename'] && $result_row['action'] == 1)
			$action = true;
			

		   
		if ($action)
			$api[$i]["active"]="class='active'";
			else
			$api[$i]["active"]="";
		// Name
		$api[$i]["Name"]=$plugin_header['Name'];
		$api[$i]["Version"]=$plugin_header['Version'];
		$api[$i]["Description"]=$plugin_header['Description'];
		$api[$i]["AuthorURI"]=$plugin_header['AuthorURI'];
		$api[$i]["Author"]=$plugin_header['Author'];
		if ($action) {
			$api[$i]["linkAdd"]='<i class="icon-minus-sign"></i> <a href="?action=deactivate&filename=' . $plugin_header['filename'] . '&tokenAdmin='.($_COOKIE['tokenAdmin'] ?? '').'" title="DESACTIVATE">Desactivate</a>';
			$api[$i]["Use"]='Use it !';
			} else {
			$api[$i]["linkAdd"]='<i class="icon-ok-sign"></i> <a href="?action=activate&filename=' . $plugin_header['filename'] . '&tokenAdmin='.($_COOKIE['tokenAdmin'] ?? '').'" title="ACTIVATE">Activate</a>';
			$api[$i]["Use"]='';			 
   		}
   
  $i++;   
  } 
$smarty->assign("api",$api);  
 }
 
$hook->addJs('dataTables','jquery.dataTables.js','themes/asset/js/','2');
$hook->addJs('datatablesjs','datatables.js','themes/asset/js/','3');
