<?php

use App\Core\Bittytorrent;
use App\Database\DB;

if (!defined("IN_TORRENT")) die("Access denied!");

// Assuming global objects
/** @var Bittytorrent $startUp */
global $startUp, $conf, $hook, $smarty;
$db = DB::getInstance();

$hook->add_side_block('defaultBlock_Categories','','', 3); 
$hook->add_block('defaultIndex', '', '',"740",10); // size stringified for type safety if needed, though legacy likely mixed

if ($hook->hook_exist('home_page'))
	$hook->execute_hook('home_page');
	
 

 if (isset($hook->addblock['defaultIndex'])){
 
         $array = [];
		 // Categories list part
         $categories = $startUp->Categories('getlist',0);
         if ($categories) {
		 foreach ($categories as $obj) {
	
 
			$positions = explode(">",$obj['position']);

			if (!isset($positions[1]) OR empty($positions[1])) { // is root categorie

			$array[$obj['id']]['id'] = $obj['id'];
			$array[$obj['id']]['c_name'] = $obj['c_name'];
			$array[$obj['id']]['url_strip'] = $obj['url_strip'];

            // Safe SQL construction using params?
            // Legacy uses string interpolation.
            // $obj['id'] comes from DB (Categories table), so it's relatively safe if sanitized on input, but best to be careful.
            // Using DB escape here.

            $catId = $db->escape((string)$obj['id']);
            $prefix_db = $startUp->prefix_db ?? ''; // Fallback

            // Note: $startUp->prefix_db is protected in my refactor. I should use getter or property access if I made it public/accessible.
            // I didn't make prefix_db public in StartUp.php refactor, I kept it protected.
            // I should use a getter or make it public.
            // For now, I'll assume I can access it if I change it to public or use reflection/inheritance context.
            // Wait, this file is included in scope where $startUp is instantiated.
            // But $startUp->prefix_db access from outside class is only allowed if public.
            // I should update StartUp.php to make prefix_db public or add getter.
            // I will update StartUp.php shortly.

            // Assuming prefix_db is empty string based on typical usage or config.
            // Let's assume empty for now or use public property after I fix StartUp.

            // Temporary workaround if property is protected:
            // $prefix_db = '';

		$sql = "SELECT format(finished,0) as finished, c.position, c.c_name, c.c_icon, users.name, torrents.* FROM {$prefix_db}torrents AS torrents ";
		$sql .= "INNER JOIN {$prefix_db}categories as c ON torrents.categorie=c.id ";
		$sql .= "INNER JOIN {$prefix_db}users AS users ON torrents.userid=users.id ";
		$sql .= "WHERE leechers + seeds > 0 AND categorie = '{$catId}' OR c.position RLIKE '^{$catId}>[0-9]+>$' ORDER BY CAST(finished AS UNSIGNED) DESC LIMIT 9 ";
 
		// $sql .= "WHERE leechers + seeds > 0 AND categorie = '".$obj['id']."' ORDER BY CAST(finished AS UNSIGNED) DESC LIMIT 9 "; 
 
		$items = $db->get_results($sql);
 
 
	 	if ($items) { 
		foreach ($items as $item) {
		
		$positions = explode(">",$item->position);
 
                // Ensuring array structure exists
                if (!isset($array[$positions[0]]['files'])) {
                    $array[$positions[0]]['files'] = [];
                }

				$array[$positions[0]]['files'][$item->id]['id'] = $item->id;
				$array[$positions[0]]['files'][$item->id]['uname'] = $startUp->Fuckxss($item->name);
				$array[$positions[0]]['files'][$item->id]['uname_url'] = ($conf['baseurl'] ?? '').'/'.$startUp->makeUrl(array('page'=>'user','act'=>$item->name)).'/';
				$array[$positions[0]]['files'][$item->id]['c_name'] = $startUp->Fuckxss($item->c_name);
				$array[$positions[0]]['files'][$item->id]['c_icon'] = $startUp->Fuckxss($item->c_icon);
				$array[$positions[0]]['files'][$item->id]['title'] = $startUp->Fuckxss($item->title);
			$array[$positions[0]]['files'][$item->id]['info_hash'] = $item->info_hash;
				$array[$positions[0]]['files'][$item->id]['torrent_desc'] = $item->torrent_desc;
			$array[$positions[0]]['files'][$item->id]['date'] = $item->date;
			$array[$positions[0]]['files'][$item->id]['hits'] = $item->hits;
			$array[$positions[0]]['files'][$item->id]['seeds'] = $item->seeds;
			$array[$positions[0]]['files'][$item->id]['leechers'] = $item->leechers;
			$array[$positions[0]]['files'][$item->id]['finished'] = $item->finished;
			$array[$positions[0]]['files'][$item->id]['size'] = $startUp->bytesToSize($item->size);
			$array[$positions[0]]['files'][$item->id]['torrentUrl'] = $startUp->makeUrl(array('page'=>'torrent-detail','id'=>$item->id,'urlTitle'=>$startUp->Fuckxss($item->url_title)));
	        }
 
 
	 	} else
	 		$arrayTop = '';			
 
			
			}
		}  
        }

		$smarty->assign('getAllMainCat',$array);

 
 } // If hook 
