<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty, $userData, $path;

if ($userData->view_torrents != 'true')
        $startUp->setError('You do not have the required permissions to view the full torrents');

if ($hook->hook_exist('detail_page'))
	$hook->execute_hook('detail_page');

$hook->add_block('defaultUpload', '', '',"740",3);
$hook->add_block('torrentInfo', '', '',"740",4);
$hook->add_block('torrentDownload', '', '',"740",5);
$hook->add_block('torrentTools', '', '',"740",6);
$hook->add_content('defaultButton', '',1); 

$smarty->assign('torrentNotfound',true);
$smarty->assign('canViewtools',false);
 
if (isset($hook->addblock['defaultUpload'])) {

    $torrentId = (int)($_GET['id'] ?? 0);
	$getDetail = $startUp->getTorrent($torrentId);

	if ($getDetail != 'NULL') {
	
		$smarty->assign('getDetail',$getDetail);

        $scrapeUrl = $startUp->makeUrl(array('page'=>'scrape','hash'=>($getDetail['info_hash'] ?? '')));
		$smarty->assign('urlScrape', $scrapeUrl);
		$smarty->assign('torrentNotfound',false); 

        $currentUid = $startUp->isLogged(); // isLogged returns uid or false
		if (($getDetail['userid'] ?? '') == $currentUid || ($userData->admin_access ?? 'false') === 'true')
			$smarty->assign('canViewtools',true);
	
	} else {
        if (!headers_sent()) {
		    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); // Set 404, no reference to anything on the search engines!
        }
    }

    $imgExt = $getDetail['imgExt'] ?? '';
    $infoHash = $getDetail['info_hash'] ?? '';

	if (!empty($infoHash) && file_exists($path.'/uploads/images/'.$infoHash.$imgExt))
		$smarty->assign('imgExist',true);
	else
		$smarty->assign('imgExist',false);

}

$hook->add_side_block('defaultBlock_Categories','','', 3); 
