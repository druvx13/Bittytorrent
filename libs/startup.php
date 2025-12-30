<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/db.php';

use BittyTorrent\Database\DB;
use BittyTorrent\Core\Startup;
use BittyTorrent\Core\Hooks;
use Smarty\Smarty;

// Global objects
$db = new DB($dbhost, $dbuser, $dbpass, $dbname);
$smarty = new Smarty();
$hook = new Hooks();
$startUp = new Startup();

// Adapter for ezSQL
require_once __DIR__ . '/database/ez_sql_core.php';
require_once __DIR__ . '/database/ez_sql_mysqli.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['strLangue'])) {
    $_SESSION['strLangue'] = 'en';
}

$conf = $startUp->getConfigs();
$lang = [];

// Smarty config
$smarty->setTemplateDir(__DIR__ . '/../themes/' . $conf['theme'] . '/');
$smarty->setCompileDir(__DIR__ . '/cache/compile_tpl/');
$smarty->setCacheDir(__DIR__ . '/cache/');

if (!is_dir($smarty->getCompileDir())) mkdir($smarty->getCompileDir(), 0777, true);
if (!is_dir($smarty->getCacheDir())) mkdir($smarty->getCacheDir(), 0777, true);

$userId = $startUp->isLogged();
$userData = $startUp->getMydata();

$smarty->assign('name', $conf['title']);
$smarty->assign('baseurl', $conf['baseurl']);
$smarty->assign('userId', $userId);
$smarty->assign('userData', $userData);

