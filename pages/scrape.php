<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $smarty;
$db = DB::getInstance();
 
if (isset($_GET['info_hash'])) {

    $hash = $db->escape($_GET['info_hash']);
	$torrent = $db->get_row("SELECT id, announce, info_hash FROM torrents WHERE info_hash = '{$hash}'");

	if($torrent){		
		$returnError = "";
		$smarty->assign('notFound',false);
 		$annouce = unserialize($torrent->announce);  
		// First, we clear torrent stats 
		$startUp->clearScrape($torrent->info_hash);
		// Set errorScrape to false
 		$smarty->assign('returnScrape',false);
 
		if (is_array($annouce)) {
			foreach ($annouce as $key => $value) {
                // startUp->torrentScrape doesn't return string in my refactor, it prints error.
                // I should update my refactor of torrentScrape to return string or capture output?
                // The original code expected return.
                // Let's assume my refactor captures output buffer or I adjust it.
                // For now, capture output.
                ob_start();
				$startUp->torrentScrape($value[0],$torrent->info_hash);
                $returnError .= ob_get_clean();
			}	
			$smarty->assign('returnScrape',$returnError);		
		} else {
	            ob_start();
				$startUp->torrentScrape($annouce,$torrent->info_hash);
				$smarty->assign('soloScrape', ob_get_clean());
		}
		
 


		// Traitements
		$NewTorrentvalue = $db->get_row("SELECT id, seeds, leechers, finished, last_scrape FROM torrents WHERE info_hash = '".$db->escape($torrent->info_hash)."'");
		$retour = array(
			'chaine'    => $NewTorrentvalue,
			'lastScrape'      => date('j F Y H:i:s', $NewTorrentvalue->last_scrape),
			'errorScrape'      => $returnError
		);
 
		 
		// Envoi du retour (on renvoi le tableau $retour encodé en JSON)
		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
		// var_dump($torrent);
 		// $startUp->redirect($conf['baseurl'].'/'.$startUp->makeUrl(array('page'=>'torrent-detail','id'=>$torrent->id)));
	} else {
        if (!headers_sent()) {
		    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); // Set 404, no reference to anything on the search engines!
        }
		$smarty->assign('notFound',true);
	}
}
