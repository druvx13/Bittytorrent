<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty, $lang;
$db = DB::getInstance();
 
if (!function_exists('domain_exists')) {
    function domain_exists($email, $record = 'MX'){
        list($user, $domain) = explode('@', $email);
        return checkdnsrr($domain, $record);
    }
}
 
$notAvaible = '<img src="'.($conf['baseurl'] ?? '').'/themes/asset/img/not-available.png" />';
$avaible = '<img src="'.($conf['baseurl'] ?? '').'/themes/asset/img/available.png" />';

if(isset($_POST["checkPass"])) {
	$pwd = $_POST['checkPass'];
    $error = '';

	if( strlen($pwd) < 6 ) {
		$error .= $notAvaible . ($lang['PtooShort'] ?? 'Too short') . " <br />";
	}

	if( strlen($pwd) > 20 ) {
		$error .= $notAvaible . ($lang['PtooLong'] ?? 'Too long') . " <br />";
	}

	if( !preg_match("#[0-9]+#", $pwd) ) {
		$error .= $notAvaible . ($lang['PleastOneNumber'] ?? 'Must contain number') . " <br />";
	}
	
	if( !preg_match("#[a-z]+#", $pwd) ) {
		$error .= $notAvaible . ($lang['PleastOneLetter'] ?? 'Must contain letter') . " <br />";
	}


	if($error){
		echo ' <br /> '.$error.'<br />';
	} else {
		echo $avaible . ($lang['Pstrong'] ?? 'Strong');
	}
	exit;
}

if(isset($_POST["checkRepass"])) {
	if($_POST["checkRepass"] === ($_POST["checkpassO"] ?? '')) {
	    echo $avaible . ($lang["PGood"] ?? 'Good');
	} else  
	    echo $notAvaible . ($lang['PdoesnotMatch'] ?? 'Does not match');
	exit;
}

if(isset($_POST["checkMail"])) {
	if(filter_var($_POST["checkMail"], FILTER_VALIDATE_EMAIL)){
		if(domain_exists($_POST["checkMail"])) {
		     echo $avaible . ($lang["PGood"] ?? 'Good');
		} else  
		    echo $notAvaible . ($lang["PDomainNotExist"] ?? 'Domain not exist');
	} else
		echo $notAvaible . ($lang["PBadSyntax"] ?? 'Bad syntax');
	exit;
}
//check we have username post var
if(isset($_POST["checkUsername"])) {
    //check if its an ajax request, exit if not
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        die();
    }  

	if(!empty($_POST["checkUsername"])) {
		//trim and lowercase username
		$username =  strtolower(trim($_POST["checkUsername"]));
	   
		//sanitize username
        // FILTER_SANITIZE_STRING is deprecated in PHP 8.1
		// $username = filter_var($username, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW|FILTER_FLAG_STRIP_HIGH);
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
	   
		//check username in db
		$user = $db->get_row("SELECT id FROM users WHERE name='".$db->escape($username)."'"); 
	 
		//if value is more than 0, username is not available
		if($user) {
		    echo $notAvaible . ($lang["PuserAlreadyExist"] ?? 'Already exists');
		} else 
		    echo $avaible . ($lang["PGood"] ?? 'Good');
    } else
	echo $notAvaible . ($lang["PuserEmpty"] ?? 'Empty');
    exit;
}
if (isset($_POST['sendForm'])) {

	$smarty->assign('accountCreated',false);
	$smarty->assign('error',false);
	
	if (!empty($_POST['email'])) {
		if (!empty($_POST['username'])) {
			if (!empty($_POST['password'])) {
				if (!empty($_POST['password']) && ($_POST['repeatPassword'] ?? '') === $_POST['password']) {
					if (isset($_POST['terms']) && $_POST['terms'] === 'on') {
						if ($startUp->addUser($_POST['username'],$_POST['email'],$_POST['password'],'true','NULL'))
							$smarty->assign('accountCreated',true);
						else 
							$smarty->assign('error','These identifiers (<b>'.$startUp->Fuckxss($_POST['username']).'</b> or <b>'.$startUp->Fuckxss($_POST['email']).'</b>) are already used'); 
					} else
						$smarty->assign('error',($lang["PAgree"] ?? 'You must agree'));
				} else
					$smarty->assign('error',($lang["PdoesnotMatch"] ?? 'Passwords do not match'));
			} else
				$smarty->assign('error',($lang["PasswordNotEmpty"] ?? 'Password cannot be empty'));
		} else
			$smarty->assign('error',($lang["PuserEmpty"] ?? 'Username cannot be empty'));
	} else
		$smarty->assign('error',($lang["PmailNotEmpty"] ?? 'Email cannot be empty'));
}

$hook->add_side_block('defaultBlock_Categories','','', 3); 
