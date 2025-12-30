<?php
// Main Entry Point
define('IN_TORRENT', true);
require 'libs/startup.php';

$page = $_GET['page'] ?? 'main';

// Basic Router
$allowed_pages = [
    'main' => 'pages/main.php',
    'upload' => 'pages/upload.php',
    'torrents' => 'pages/torrents.php',
    'torrent-detail' => 'pages/detail.php',
    'login' => 'pages/login.php',
    'registration' => 'pages/registration.php'
];

if (array_key_exists($page, $allowed_pages)) {
    require $allowed_pages[$page];
} else {
    require 'pages/main.php';
}

