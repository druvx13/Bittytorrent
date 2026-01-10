<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty, $userData;

if ($userData->view_users != 'true')
        $startUp->setError('You do not have the required permissions to view the user detail');

if ($hook->hook_exist('user_page'))  
		$hook->execute_hook('user_page');
		

$hook->add_side_block('defaultBlock_Categories','','', 3);
$hook->add_block('infoUser', '', '','450-left',10);  
      
if (!isset($_GET['act']))
	$startUp->redirect($conf['baseurl']);

$getUserdata = $startUp->getUserdata($_GET['act']);
 
if ($getUserdata)
{

$sortedBy = $_GET["sortedBy"] ?? '';
$axis = $_GET["axis"] ?? '';

// required connect
SmartyPaginate::connect();
// set items per page
SmartyPaginate::setLimit(10);

if (!isset($_GET['next']))
	SmartyPaginate::reset(); // reset/init the session data all time!
 
SmartyPaginate::setUrl('user/'.$getUserdata->name);
$startUp->paginatePage = 'user/'.$getUserdata->name;

	//$hook->set_title('title_user', $getUserdata->name .' detail'); 
	if ($getUserdata->seeMytorrents === 'true') {
		$hook->add_block('getMytorrents', '', '','270-right',12);
	}	
$smarty->assign('getUserdata',$getUserdata);
$smarty->assign("getMyTorrents",$startUp->getTorrentsByUser($_GET['act'], $sortedBy, $axis));
$smarty->assign("getGravatar",$startUp->get_gravatar($getUserdata->mail));

} else {

// $hook->set_title('title_user','User not found!'); 

}

$hook->set_title('title_user','User detail'); 
