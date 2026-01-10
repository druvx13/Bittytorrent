<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming $startUp and $conf are available globally or we need to ensure they are typed
/** @var Bittytorrent $startUp */
global $startUp, $conf, $lang, $hook;

if ($startUp->isLogged())
        $startUp->redirect($conf['baseurl'].'/account');

if (isset($_POST['submit'])) {
		$redirect = '';
		$error = '';
        $user = $_POST['user'] ?? '';
        $pass = $_POST['pass'] ?? '';

        if (!empty($user) && !empty($pass)){
                if ($startUp->checkCredentials($user, $pass)){
                                        $startUp->setSession($user,$pass,'on');
                                        $redirect = $conf['baseurl'].'/account';
                                        $error = '<span style="color:green">'.($lang["goodCredential"] ?? 'Login Successful').'</span><br /> ';
                                } else
                                                $error = '<span style="color:red">'.($lang["badCredential"] ?? 'Invalid Credentials').'</span><br /> ';
        } else
                        $error = '<span style="color:red">'.($lang["errorLoginEmpty"] ?? 'Empty Fields').'</span><br /> ';


		$retour = array(
			'errorLogin'      => $error,
			'redirect'      => $redirect
		);
 
		 
		// Envoi du retour (on renvoi le tableau $retour encodé en JSON)
		header('Content-type: application/json');
		echo json_encode($retour);
		exit;
}  

$hook->add_side_block('defaultBlock_Login','','', 3); 
