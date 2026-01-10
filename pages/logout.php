<?php

use App\Core\Bittytorrent;

if (!defined("IN_TORRENT")) die("Access denied!");
	
$Bittytorrent = new Bittytorrent();
$Bittytorrent->logout();
