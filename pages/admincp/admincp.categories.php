<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT"))
      die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $smarty;

if(!isset($_GET["catid"])) $_GET["catid"] =0;

$ctg_id = $_GET["catid"];

if (isset($_POST['act'])) 	{
 	if ($_POST['act'] === "add") {
 		$startUp->Categories('add',$_POST);
    }
 	if ($_POST['act'] === "update") {
 		$startUp->Categories('update',$_POST); 
    }
}

if (isset($_GET['delCat'])) {

		if (isset($_GET['cat_action'])) {
				$dial = $startUp->Categories('delete',$_GET); 
		} else 
			$dial = 'Please select an action';
			
		// Envoi du retour (on renvoi le tableau $retour encodé en JSON)
		header('Content-type: application/json');
		$retour = array(
			'chaine'    => $dial
		);
		echo json_encode($retour);
		exit;	
}

if (isset($_GET['catid'])) {
 	$getOne = $startUp->Categories('getOne',$_GET['catid']);
    if ($getOne) {
	    $smarty->assign('getOne',$getOne);
        // $getOne is an object from DB::get_row in my DB class?
        // My DB::get_row by default returns object.
	    $smarty->assign('getParent',$startUp->Categories('getParent',$getOne->position));
    }
	
}
// $startUp->Categories('getlist',$_GET['id']) ;
 $categories = $startUp->Categories('getlist',0);
 $array = [];

 if ($categories) {
 foreach ($categories as $obj) {
 
	$array[$obj['id']]['id'] = $obj['id'];
	$array[$obj['id']]['prefix'] = $obj['prefix'];
	$array[$obj['id']]['c_name'] = $obj['c_name'];
	
	 if ($getSubCat = $startUp->Categories('getsubcat',$obj['id']))
	 {
	 	foreach ($getSubCat as $objgetSubCat) {
	 		// var_dump($objgetSubCat['c_name'] );
	 		$array[$objgetSubCat['id']]['subCat']['c_name'] = $objgetSubCat['c_name'];
	 		$array[$objgetSubCat['id']]['subCat']['is_child_of'] = $obj['id'];
	 	}
	 }
}  
 }

$smarty->assign('getAllCat',$array); 
$smarty->assign('outputHtmlCat',$startUp->Categories('html',$ctg_id,true));
