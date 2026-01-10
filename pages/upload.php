<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty, $userData, $lang, $path, $userId;

if ($userData->can_upload != 'true')
        $startUp->setError('You do not have the required permissions to upload new torrents');

if ($hook->hook_exist('upload_page'))
	$hook->execute_hook('upload_page');

$hook->add_block('defaultUpload', '', '',"740",10);
$hook->add_side_block('defaultBlock_Upload','','', 3); 
$hook->addJs('scriptUpload','themes/asset/js/script.upload.js','','2');
$hook->addJs('scriptsummernote','themes/asset/js/summernote.js','','5');
$hook->addJs('filestyle','themes/asset/js/bootstrap-filestyle.js','','6');
$hook->addJs('jsjasny','themes/asset/js/jasny-bootstrap.min.js','','7');
$hook->addCss('csssummernote','themes/asset/css/summernote.css','','5');
$hook->addCss('cssjasny','themes/asset/css/jasny-bootstrap.min.css','','6');
 

if (isset($hook->addblock['defaultUpload'])){
 
// require_once $path.'/libs/class.bdecode.php';
// require_once $path.'/libs/class.bencode.php'; // To create info hash of torrent

use App\Core\BDecode;
use App\Core\BEncode;

$data = array();

if(isset($_GET['files'])) {	
 
	$files = array();

	$uploaddir = $path.'/uploads/torrents/';
    // Ensure dir exists
    if (!is_dir($uploaddir)) {
        mkdir($uploaddir, 0755, true);
    }

	foreach($_FILES as $file) {

    // Safer file extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if($ext === 'torrent') {
	
	$torrent = new BDecode($file['tmp_name']);
	$resultTorrent = $torrent->result;

    if (isset($resultTorrent['info'])) {
	    $hash = sha1(BEncode($resultTorrent['info']));
    } else {
        $data = array('error' => $lang["notTorrent"] ?? 'Invalid Torrent');
        echo json_encode($data);
        exit;
    }
	
	if ($conf['open_tracker'] === 'false') {
		   if ($resultTorrent["announce"] === $conf['baseurl'].'/announce') {
			if (is_uploaded_file($file['tmp_name'])) {	
				if(move_uploaded_file($file['tmp_name'], $uploaddir .$hash.'.torrent')) {
					$files[] = $uploaddir.$file['name'];
					$data = array('files' => $files, 'info_hash' => $hash);
				} else
			 		$data = array('error' => $lang["errorMove"]);
		 	} else
		 		$data = array('error' => $lang["errorIsuploaded"]);
		    } else
		   	    $data = array('error' => $lang["errorPrivate"].' => <strong>'.$conf['baseurl'].'/announce</strong>');
		
	} else {

			if (is_uploaded_file($file['tmp_name'])) {	
				if(move_uploaded_file($file['tmp_name'], $uploaddir .$hash.'.torrent')) {
					$files[] = $uploaddir.$file['name'];
					$data = array('files' => $files, 'info_hash' => $hash);
				} else
			 		$data = array('error' => $lang["errorMove"]);
		 	} else
		 		$data = array('error' => $lang["errorIsuploaded"]);
 
		
	}

	} else
		 $data = array('error' => $lang["notTorrent"]);
	
	

	} 
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
 
}
if (isset($_GET['form']))
{
    // Sanitize POST data completely
    $sanitizedPost = [];
    foreach ($_POST as $k => $v) {
        $sanitizedPost[$k] = $startUp->Fuckxss($v);
    }
	$data = array('success' => $lang["welldone"], 'formData' => $sanitizedPost);
    header('Content-Type: application/json');
	echo json_encode($data);
	exit;
 
}

if (isset($_GET['act'])) {

    $torrentHash = $_POST['torrentHash'] ?? '';
    if (empty($torrentHash) || !preg_match('/^[a-fA-F0-9]{40}$/', $torrentHash)) {
        $startUp->setError('Invalid Torrent Hash');
        return;
    }

    $torrentPath = $path.'/uploads/torrents/'.$torrentHash.'.torrent';
    if (!file_exists($torrentPath)) {
         $startUp->setError('Torrent file not found');
         return;
    }

$torrent = new BDecode($torrentPath);
$resultTorrent = $torrent->result;
 	
 	// Check announce(s) url 
	if (isset($torrent->result['announce-list']))
		$announce = $torrent->result['announce-list'];
	else
		$announce = $torrent->result['announce'];
 
	// Check if image existe

    $ext = '';
	if (!empty($_FILES['image']['name']) && $userData->can_upload === 'true') {
	
		$valid_ext = array( 'jpg' , 'jpeg' , 'gif' , 'png' );
		$fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $ext = '.'.$fileExt;
 
		if (in_array($fileExt,$valid_ext)) {
            $uploadImgDir = $path.'/uploads/images/';
            if (!is_dir($uploadImgDir)) mkdir($uploadImgDir, 0755, true);

			if(!move_uploaded_file($_FILES['image']['tmp_name'], $uploadImgDir.$torrentHash.$ext))
				$smarty->assign('errorUploadimg',true);
			else
				$smarty->assign('errorUploadimg',false);		
		} else
			 $smarty->assign('imgNotAutorised',true);
	} else
		$ext = '';		
 		
 		// Insert torrent in database and assign id of torrent

            $title = $_POST['torrentTitle'] ?? '';
            $urlTitle = $_POST['torrentUrlTitle'] ?? '';
            $categories = (int)($_POST['categories'] ?? 0);
            $desc = $_POST['torrentDesc'] ?? '';
            $size = (int)($_POST['torrentSize'] ?? 0);
 
			$returnIdtorrent = $startUp->addTorrent(
					(int)$userId,
					$title,
					$urlTitle,
					$categories,
					$desc,
					$torrentHash,
					$announce,
					$size,
					$ext
				);
				
		// First, we clear torrent stats scrape
		$startUp->clearScrape($torrentHash);
		$smarty->assign('errorScrape',false);
		$smarty->assign('torrentUrl',$startUp->makeUrl(
			array('page'=>'torrent-detail',
			      'id'=>$returnIdtorrent,
			      'urlTitle'=>$startUp->Fuckxss($urlTitle)
			      )));
			      
		if (is_array($announce)) {
			foreach ($announce as $key => $value) {
				// echo "Clé : $key; Valeur : $value[0]<br />\n";
				$startUp->torrentScrape($value[0],$torrentHash);
			}			
		} else
			$startUp->torrentScrape($announce,$torrentHash);
 
 		$startUp->redirect($conf['baseurl'].'/torrent-detail/'.$returnIdtorrent);
}

} // If hook
