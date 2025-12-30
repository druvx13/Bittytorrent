<?php
use BittyTorrent\Torrent\Decoder;
use BittyTorrent\Torrent\Encoder;

if (!defined('IN_TORRENT')) die('Access denied!');

if ($userData->can_upload != 'true') {
    $startUp->setError('You do not have the required permissions to upload new torrents');
}

if ($hook->hook_exist('upload_page'))
    $hook->execute_hook('upload_page');

// Add legacy hooks/assets if needed
$hook->add_block('defaultUpload', '', '', 740, 10);

if (isset($_GET['files'])) {
    $files = [];
    $uploaddir = __DIR__ . '/../uploads/torrents/';
    if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);

    foreach ($_FILES as $file) {
        if (str_ends_with($file['name'], '.torrent')) {
            $decoder = new Decoder($file['tmp_name']);
            $result = $decoder->decode();

            if ($result && isset($result['info'])) {
                $hash = sha1(Encoder::encode($result['info']));

                // Validate Tracker URL
                $announce = $result['announce'] ?? '';
                // TODO: Strict tracker check if private

                if (move_uploaded_file($file['tmp_name'], $uploaddir . $hash . '.torrent')) {
                    $files[] = $uploaddir . $file['name'];
                    $data = ['files' => $files, 'info_hash' => $hash];
                } else {
                    $data = ['error' => 'Failed to move uploaded file'];
                }
            } else {
                $data = ['error' => 'Invalid torrent file'];
            }
        } else {
             $data = ['error' => 'Not a .torrent file'];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    die();
}

// Handle Form Submit
if (isset($_GET['act'])) {
     // Validate Hash
     $hash = $_POST['torrentHash'] ?? '';
     if (!preg_match('/^[a-f0-9]{40}$/', $hash)) {
         die('Invalid hash');
     }

     // Load torrent to get details
     $torrentPath = __DIR__ . '/../uploads/torrents/' . $hash . '.torrent';
     if (!file_exists($torrentPath)) {
         die('Torrent file not found');
     }

     $decoder = new Decoder($torrentPath);
     $result = $decoder->decode();

     // Handle Image Upload
     $imgExt = '';
     if (!empty($_FILES['image']['name']) && $userData->can_upload === 'true') {
         $valid_ext = ['.jpg', '.jpeg', '.gif', '.png'];
         $ext = strrchr($_FILES['image']['name'], '.');
         if (in_array(strtolower($ext), $valid_ext)) {
             $imgPath = __DIR__ . '/../uploads/images/' . $hash . $ext;
             if (!move_uploaded_file($_FILES['image']['tmp_name'], $imgPath)) {
                 $smarty->assign('errorUploadimg', true);
             } else {
                 $imgExt = $ext;
             }
         }
     }

     // Insert into DB
     // Use $startUp->addTorrent wrapper or direct DB
     // Ideally direct DB for modernization but Startup might handle categories etc.
     // For now, let's look at what addTorrent does.
     // If it's legacy, it might use ezSQL.

     // Refactor: Logic from Startup::addTorrent should be moved here or updated.

     $title = $_POST['torrentTitle'] ?? 'Untitled';
     $cat = $_POST['categories'] ?? 0;
     $desc = $_POST['torrentDesc'] ?? '';
     $urlTitle = $_POST['torrentUrlTitle'] ?? '';
     // Calculate Size
     $size = 0;
     if (isset($result['info']['length'])) {
         $size = $result['info']['length'];
     } elseif (isset($result['info']['files'])) {
         foreach ($result['info']['files'] as $f) {
             $size += $f['length'];
         }
     }

     $announce = $result['announce'] ?? '';
     if (is_array($announce)) $announce = implode(',', $announce);

     $db->query("INSERT INTO torrents (userid, info_hash, title, url_title, categorie, torrent_desc, date, announce, size, imgExt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$userId, $hash, $title, $urlTitle, $cat, $desc, time(), $announce, $size, $imgExt]);

     $id = $db->insert_id();

     // Scrape initial stats?
     // $startUp->redirect...
     header('Location: ' . $conf['baseurl'] . '/index.php?page=torrent-detail&id=' . $id);
     die();
}

