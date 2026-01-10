<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT"))
      die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $userData, $hook, $smarty, $db;

if (($userData->admin_access ?? 'false') != 'true')
		$startUp->redirect($conf['baseurl']);

if (!isset($_COOKIE['tokenAdmin']) 
	|| empty($_COOKIE['tokenAdmin']) 
	|| !isset($_GET['tokenAdmin']) 
	|| empty($_GET['tokenAdmin']) 
	|| $_GET['tokenAdmin'] != $_COOKIE['tokenAdmin']
) 
    $startUp->redirect($conf['baseurl']);
        
if ($startUp->checkAdmin() === false) 
        $startUp->redirect($conf['baseurl']);
 
if ($hook->hook_exist('admin_action'))  
		$hook->execute_hook('admin_action');
if ($hook->hook_exist('new_admin_page')) 
		$hook->execute_hook('new_admin_page');
			
$do = $_GET["act"] ?? "";

$hook->add_admin_page('','admincp.settings.php','admincp.settings.html');
$hook->add_admin_page('users','admincp.users.php','admincp.users.html');
$hook->add_admin_page('settings','admincp.settings.php','admincp.settings.html');
$hook->add_admin_page('usersgroups','admincp.usersgroups.php','admincp.usersgroups.html');
$hook->add_admin_page('categories','admincp.categories.php','admincp.categories.html');
$hook->add_admin_page('plugins','admincp.plugins.php','admincp.plugins.html');
$hook->add_admin_page('themes','admincp.themes.php','admincp.themes.html');
$hook->add_admin_page('update','admincp.update.php','admincp.update.html');
 
	foreach ($hook->addnewadminpage as $valueAdmin) {
				
		if ($valueAdmin['name'] === $do) { 
			if (!empty($valueAdmin['phpFile']))
				include __DIR__ . '/' . $valueAdmin['phpFile']; // Use explicit path relative to this file
			if (!empty($valueAdmin['htmlFile']))
				$smarty->display($valueAdmin['htmlFile']);		        	
		}  			
	}
