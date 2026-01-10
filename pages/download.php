<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $userData, $path;
$db = DB::getInstance();
 
if (($userData->can_download ?? 'false') != 'true')
		die('You do not have the required permissions to download this torrents');

if (isset($_GET['hash'])) {

    $hash = $db->escape($_GET['hash']);
	$torrent = $db->get_row("SELECT id, title, info_hash FROM torrents WHERE info_hash = '{$hash}'");
		
	if($torrent){		

		// require_once $path.'/libs/class.bdecode.php';
		// require_once $path.'/libs/class.bencode.php';

        $torrentFile = $path.'/uploads/torrents/'.$torrent->info_hash.'.torrent';

        if (!file_exists($torrentFile)) {
            if (!headers_sent()) {
                header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            }
            return;
        }

		$fd = fopen($torrentFile, "rb");
		$mainTorrent = fread($fd, filesize($torrentFile));
		
		$torrentDecode = new \App\Core\BDecode($torrentFile);
		$array = $torrentDecode->result; 
 
        // Only modify announce for private trackers if needed, or if configured.
        // Original logic seems to force it if open_tracker false?
        // Or blindly inject private_id.
        // The original code injects pid if userData->private_id exists.

        if (isset($userData->private_id)) {
		    $array["announce"] = ($conf['baseurl'] ?? '')."/announce?pid=".$userData->private_id;
        }
 
		if (isset($array["announce-list"]) && is_array($array["announce-list"]))
		   {
		   for ($i=0;$i<count($array["announce-list"]);$i++)
		       {
		       for ($j=0;$j<count($array["announce-list"][$i]);$j++)
		           {
                   $announceUrl = $conf['baseurl']."/announce";
		           if ($array["announce-list"][$i][$j] == $announceUrl) // Logic in original was in_array but arg 2 was string? 'in_array(needle, haystack)'. $conf['baseurl']."/announce" is string. Wait. in_array expects array as 2nd arg.
                   // Legacy code: if (in_array($array["announce-list"][$i][$j],$conf['baseurl']."/announce"))
                   // This was likely buggy or relying on PHP loose typing/behavior? No, in_array requires array.
                   // If haystack is string, it warns.
                   // Assuming it meant string comparison.

                   // Modernizing:
                   if ($array["announce-list"][$i][$j] === $announceUrl)
		              {
                          // This logic seems specific to adding PID to announce URL
                          if (isset($userData->private_id)) {
		                      if (strpos($array["announce-list"][$i][$j],"announce.php")===false)
		                         $array["announce-list"][$i][$j] = trim(str_replace("/announce", "/announce?pid=".$userData->private_id, $array["announce-list"][$i][$j]));
		                      else
		                         $array["announce-list"][$i][$j] = trim(str_replace("/announce.php", "/announce.php?pid=".$userData->private_id, $array["announce-list"][$i][$j]));
                          }
		            }
		         }
		     }
		 }
	$mainTorrent = BEncode($array);
		fclose($fd);
		header("Content-Type: application/x-bittorrent");
		header('Content-Disposition: attachment; filename="['.($conf['title'] ?? 'Bittytorrent').'] '.$torrent->title.'.torrent"');
		print($mainTorrent);  
 		
	} else {
        if (!headers_sent()) {
		    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); // Set 404, no reference to anything on the search engines!
        }
		// $smarty->assign('notFound',true);
	}
}
