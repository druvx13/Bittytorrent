<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty;

if (!isset($_COOKIE['token']) 
	|| empty($_COOKIE['token']) 
	|| !isset($_GET['token']) 
	|| empty($_GET['token']) 
	|| $_GET['token'] != $_COOKIE['token']
) 
    $startUp->redirect($conf['baseurl']);


if ($hook->hook_exist('account_page'))  
		$hook->execute_hook('account_page');

$hook->add_side_block('defaultBlock_Account','','', 3); 
$hook->set_title('title_account', 'My account'); 
$hook->add_block('infoUser', '', '','450-left',10); 

$startUp->isLoggedAcount(); 
$getUserdata = $startUp->getMydata();


$hook->add_block('getMytorrent', '', '','270-right',12);
 

$sortedBy = $_GET["sortedBy"] ?? '';
$axis = $_GET["axis"] ?? '';

// required connect
SmartyPaginate::connect();
// set items per page
SmartyPaginate::setLimit(10);

if (!isset($_GET['next']))
	SmartyPaginate::reset(); // reset/init the session data all time!
 
SmartyPaginate::setUrl('account?token='.($_GET['token'] ?? ''));
$startUp->paginatePage = 'account';

$smarty->assign("getUserdata",$getUserdata);
$smarty->assign("getMyTorrents",$startUp->getMyTorrents($sortedBy, $axis));
$smarty->assign("getGravatar",$startUp->get_gravatar($getUserdata->mail));
SmartyPaginate::assign($smarty); // paginate
