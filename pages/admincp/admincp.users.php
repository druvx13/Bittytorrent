<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT"))
      die("Access denied!");
      
// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $smarty, $hook, $conf;

// required connect
SmartyPaginate::connect();
// set items per page
SmartyPaginate::setLimit(10);

if (!isset($_GET['next']))
	SmartyPaginate::reset(); // reset/init the session data all time!
 
// If using friendly URL / routing
$startUp->paginatePage = 'admincp/users';

     
if(!empty($_GET['del']) && is_numeric($_GET['del'])) {
	if ($startUp->delUser((int)$_GET['del'])) {
		$smarty->assign('forbidden',false);		
	} else
		$smarty->assign('forbidden',true);	
} else
	$smarty->assign('forbidden',false);	

if(isset($_POST['submitadd'])) {
        if (!empty($_POST['name']) && !empty($_POST['mail']) && !empty($_POST['pass'])){    
		if (!empty($_POST['sendmail'])) {
        		$sendMail = $_POST['sendmail'];
        	} else
        		 $sendMail = 'NULL';   
        		        
            $startUp->addUser($_POST['name'],$_POST['mail'],$_POST['pass'],'NULL',$sendMail);
        }
}

if(!empty($_GET['edit'])) {
        if (!empty($_POST['editUser'])){
            // Pass empty string if not set, strict types handled by method signature
                $pass = $_POST['pass'] ?? '';
                $name = $_POST['name'] ?? '';
                $mail = $_POST['mail'] ?? '';
                $level = (int)($_POST['level'] ?? 0);
                $location = $_POST['location'] ?? '';
                $website = $_POST['website'] ?? '';
                $signature = $_POST['signature'] ?? '';

                $startUp->adminEditUser((int)$_GET['edit'], $pass, $name, $mail, $level, $location, $website, $signature);
        }
        $smarty->assign("getUserdata",$startUp->getUserdata($_GET['edit']));
        $smarty->assign("getStatuts",$startUp->getStatuts());
} else 
        $smarty->assign("getUsers",$startUp->getUsers());
 
$hook->addCss('adminUser','adminUser.css','themes/'.$conf['theme'].'/css/','1');
SmartyPaginate::assign($smarty); // paginate
