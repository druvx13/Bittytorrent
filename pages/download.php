<?php
require_once __DIR__ . '/../libs/startup.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('Invalid ID');

$torrent = $db->get_row("SELECT * FROM torrents WHERE id = $id");
if (!$torrent) die('Torrent not found');

$file = __DIR__ . '/../uploads/torrents/' . $torrent->info_hash . '.torrent';
if (file_exists($file)) {
    header('Content-Type: application/x-bittorrent');
    header('Content-Disposition: attachment; filename="' . $torrent->title . '.torrent"');
    readfile($file);
} else {
    die('File missing');
}
