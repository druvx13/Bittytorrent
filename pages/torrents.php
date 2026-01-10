<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $hook, $smarty, $cat_id;

$hook->add_block('defaultTorrents', '', '',"740",10);
$hook->add_side_block('defaultBlock_Categories','','', 3); 


if (isset($hook->addblock['defaultTorrents'])){
 	
	$sortedBy = $_GET["sortedBy"] ?? '';
	$axis = $_GET["axis"] ?? '';
 
 		$sortedList = array('title','date','size','seeds','leechers');
        $array = [];
 
		foreach ($sortedList as $arr) {
	     		$array[$arr]['id'] = $arr;
	     		if ($cat_id!='NULL') 
			$array[$arr]['url'] = $startUp->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$cat_id,'sortedBy'=>$arr,'axis'=>($axis == 'asc' ? 'desc' : 'asc')));
		     	else
			$array[$arr]['url'] = $startUp->makeUrl(array('page'=>'torrents','sortedBy'=>$arr,'axis'=>($axis == 'asc' ? 'desc' : 'asc')));
		} 		 
 
 
	// required connect
    // Assuming SmartyPaginate is autoloaded or available via global
	SmartyPaginate::connect();
	// set items per page
	SmartyPaginate::setLimit(25);

	if (!isset($_GET['next']))
		SmartyPaginate::reset(); // reset/init the session data all time!
 
	// assign your db results to the template
	$smarty->assign('results',$startUp->getTorrents($cat_id, $sortedBy, $axis));
	SmartyPaginate::assign($smarty);	
	if (!empty($_GET["search"]))
		$startUp->paginatePage = 'torrents/search/'.urlencode($_GET["search"]);
	else
		$startUp->paginatePage = 'torrents';
	$smarty->assign('sortedList',$array);

}
