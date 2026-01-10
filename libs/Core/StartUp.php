<?php

namespace App\Core;

use App\Database\DB;
use Smarty;
use SmartyPaginate;

class StartUp {

	public string $prefix_db = ''; // Prefix db (for security)
	protected string $version = '3'; // Version of php-pastebin
	protected string $rev = '0'; // Revision of php-pastebin
	protected string $charset = 'utf-8'; // Chraset
	protected mixed $get = '';
	protected mixed $block = '';
	protected mixed $sqlvalue = '';
	protected mixed $paginatePage = '';

	###
	public function __construct() {
		if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
			if (session_status() == PHP_SESSION_NONE) session_start();
		} else
			session_start();
		// header("Content-type:text/html; charset=".$this->charset."");
        // Don't send headers in constructor, it might break tests or other logic.
        // If needed, it should be in a separate method or controlled.
        // For now, keeping it but checking for headers sent
        if (!headers_sent()) {
             header("Content-type:text/html; charset=".$this->charset."");
        }

		$this->checkInstallFile();
	}
	###
	public function checkInstallFile(): void {
		global $smarty,$path;
        if (!isset($smarty)) return; // In case smarty is not global yet (testing)

		if (file_exists($path."/install.php")) {
			if (filesize($path."/libs/db.php") != 0) {
				$smarty->assign('errorInstallFile',true);
			}
		} else
			$smarty->assign('errorInstallFile',false);
	}
	###
	public function setError(string $message): void {
		global $smarty;
		$smarty->assign('setError',$message);
	}
	###
	public function cGet(mixed $get): void {
	    $this->get = $get;
	    if(is_numeric($this->get)) {
	      $get=(int)$this->get;
	    } else {
	      $get=htmlspecialchars($this->get);
	    }
	    // return $get;
	}
	###
	public function getConfigs(): array {
		global $smarty;
        $db = DB::getInstance();

        $sql = "SELECT `key`,`value` FROM ".$this->prefix_db."settings";
        $array = $db->get_results($sql, 'ARRAY_A');

        $configs = [];
        if ($array) {
            foreach ($array as $value) {
                $configs[$value['key']] = $this->Fuckxss($value['value']);
            }
        }

        if (isset($smarty)) {
            $smarty->assign('getConfigs',$configs);
        }
	return $configs;
	}
	###
	public function redirect(string $location='index.php'): void {
		header("location:".$location);
		exit;
	}
	###
	public function makeUrl(array $array): string {
		global $conf;
		if (isset($conf['rewrite_url']) && $conf['rewrite_url'] === 'true') {
			$post_url = '';
			foreach ($array as $key=>$value)
				$post_url .= $value.'/';
			$post_url = rtrim($post_url, '/');
		} else {
			$post_url = '';
			foreach ($array as $key=>$value)
				$post_url .= $key.'='.$value.'&';
			$post_url = rtrim('?'.$post_url, '&');
			$post_url = str_replace('&','&amp;', $post_url); // Replace & by &amp; for url not rewrite
		}
		return $post_url;
	}
	###
	public function addTorrent(int $userid, string $title, string $urlTitle, int $cat, string $desc, string $hash, mixed $announce, int $size, string $imgExt): int|string {
		global $conf;
        $db = DB::getInstance();
		$date = time();

        // Serialize announce if it's an array, otherwise assume string or handle appropriately.
        // The legacy code used serialize($announce).
        $announceStr = serialize($announce);

		$query = "INSERT INTO ".$this->prefix_db."torrents (userid,info_hash,title,url_title,categorie,torrent_desc,date,announce,size,imgExt)
	          VALUES (
			  '".$db->escape($userid)."',
			  '".$db->escape($hash)."',
			  '".$db->escape($title)."',
			  '".$db->escape($urlTitle)."',
			  '".$db->escape($cat)."',
			  '".$db->escape($desc)."',
			  '$date',
			  '".$db->escape($announceStr)."',
			  '".$db->escape((string)$size)."',
			  '".$db->escape($imgExt)."'
			  )";
		$db->query($query);
		// $db->debug();
		return $db->insert_id;
	}
	###
	public function torrentScrape(string $url, string $infohash=''): void
	{
	    global $path,$smarty;
        $db = DB::getInstance();

		if (isset($url))
		{
		    $url_c = parse_url($url);

		    if(!isset($url_c["port"]) || empty($url_c["port"]))
		        $url_c["port"]=80;

            // TODO: Refactor scrape folder classes as well. For now keeping require usage but pointing to modern path if possible?
            // Assuming legacy path structure still exists or mapped.
		    require_once($path."/libs/scrape/".$url_c["scheme"]."tscraper.php");
		    try
		    {
		        $timeout = 5;

		        if($url_c["scheme"]=="udp")
		            $scraper = new \udptscraper($timeout); // Assuming these classes are global or properly namespaced later
		        else
		            $scraper = new \httptscraper($timeout);

		        $ret = $scraper->scrape($url_c["scheme"]."://".$url_c["host"].":".$url_c["port"].(($url_c["scheme"]=="udp")?"":"/announce"),array($infohash));
		        //var_dump($ret);
		        $query = "UPDATE torrents SET `seeds`=seeds+".$ret[$infohash]["seeders"].", `leechers`=leechers+".$ret[$infohash]["leechers"].", `finished`=finished+".$ret[$infohash]["completed"].", `last_scrape`='".time()."' WHERE `info_hash` = '".$db->escape($infohash)."'";
		        $db->query($query);
		        if (isset($smarty)) {
                    $smarty->assign('returnScrape',$ret);
                }
		    }
		    catch(\Exception $e) {
                // ScraperException might not be available yet, using Exception as fallback or specific if I find it.
				if ($e->getMessage())
					 echo "<br />".$e->getMessage();
		    }
		    return;
		}
		return;
	}
	###
	public function cachingTorrent(string $website, string $torrent): string {
		$files = array(
			array(
			    'name' => 'torrent',			// Don't change
			    'type' => 'application/x-bittorrent',
			    'file' => $torrent			// Full path for file to upload
			)
		);

        if (function_exists('http_post_fields')) {
		    $http_resp = http_post_fields( $website, array(), $files );
            $tmp = explode( "\r\n", $http_resp );
            $infoHash = substr( $tmp[count( $tmp ) - 1], 0, 40 );
            unset( $tmp, $http_resp, $files );
            return $infoHash;
        } else {
            // Polyfill or alternative for pecl_http's http_post_fields if missing?
            // For now, assuming it exists or not critical for basic refactor unless it fails.
            // Using curl as alternative
            $ch = curl_init();
            $cfile = new \CURLFile($torrent, 'application/x-bittorrent', 'torrent');
            $data = ['torrent' => $cfile];
            curl_setopt($ch, CURLOPT_URL, $website);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $tmp = explode( "\r\n", $response );
		    $infoHash = substr( $tmp[count( $tmp ) - 1], 0, 40 );
            return $infoHash;
        }
	}
	###
	public function clearScrape(string $infohash): void {
		global $db;
        $db = DB::getInstance();
		$query = "UPDATE torrents SET `seeds`='0', `leechers`='0', `finished`='0' WHERE `info_hash` = '".$db->escape($infohash)."'";
		$db->query($query);
	}
	###
	public function write_log(string $text, string $reason='add'): void {
	    global $db; // Wait, $db is not global here, need to get instance
        $db = DB::getInstance();

        // $this->uid is not defined in StartUp, but in Bittytorrent which extends it.
        // However, this method uses $this->uid.
        // It should probably be protected property in StartUp or this method belongs in Bittytorrent.
        // Based on inheritance, if this is called on Bittytorrent instance, it works.
        // But to make it cleaner, I should check if property exists or declare it.
        // I'll declare it in StartUp as well or ensure visibility.
        // Declared $uid in Bittytorrent.

		$id = $this->uid ?? 0;
		$query = "INSERT INTO ".$this->prefix_db."logs (date,txt,type,user) VALUES(UNIX_TIMESTAMP(), '".$db->escape($text)."', '".$db->escape($reason)."',".$id.")";
		$db->query($query);
	}
	###
	public function editTorrent(int $id, string $title, string $url_title, string $desc, int $cat, string $image): bool {
		$db = DB::getInstance();
		if (!empty($image))
			$imageUpdate = ", imgExt='".$db->escape($image)."'";
		else
			$imageUpdate = "";

		$query = "UPDATE ".$this->prefix_db."torrents SET
						title='".$db->escape($title)."',
						url_title='".$db->escape($url_title)."',
						torrent_desc='".$db->escape($desc)."',
						categorie='".$db->escape((string)$cat)."'
						$imageUpdate
			 WHERE id = '".$db->escape((string)$id)."'";
		if ($db->query($query))
		return true;
	else
		return false;
	}

	###
	public function getTorrents(string $cat='', string $orderBy='', string $axis=''): array|bool{
		global $smarty;
        $db = DB::getInstance();
		$query_select = "";

		if (!empty($cat) && $cat != 'NULL') {
			$cat_sql = "SELECT * FROM categories WHERE url_strip='".$db->escape($cat)."'";
			$cat_sql_res = $db->get_row($cat_sql);
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS					 t.id,t.info_hash,t.title,t.url_title,t.categorie,t.torrent_desc,t.date,t.hits,t.seeds,t.leechers,t.finished,t.size,t.announce,users.name,categories.c_name, categories.url_strip,categories.c_icon FROM ".$this->prefix_db."torrents AS t ";
		$sql .= "INNER JOIN ".$this->prefix_db."users ON t.userid=users.id ";
		$sql .= "INNER JOIN ".$this->prefix_db."categories ON t.categorie=categories.id ";
		if (!empty($cat) && $cat != 'NULL' && isset($cat_sql_res)) {
		    $sql .= "WHERE categories.url_strip='$cat' OR categories.position RLIKE '^".$cat_sql_res->id.">[0-9]+>$'";
		}
		  if (isset($_GET["search"])) {
		   $testocercato = trim($_GET["search"]);
		   $testocercato = explode(" ",$testocercato);
		   if ($_GET["search"]!="")
		      $search = "search=" . implode("+",$testocercato);
		    for ($k=0; $k < count($testocercato); $k++) {
			// $query_select .= " t.title LIKE '%" . mysql_real_escape_string($testocercato[$k]) . "%'";
			$query_select .= sprintf(" t.title LIKE '%%%s%%'", "%" . $db->escape($testocercato[$k]) . "%");


			if ($k<count($testocercato)-1)
			   $query_select .= " AND ";
		    }

            if (strpos($sql, 'WHERE') !== false) {
                 $sql .= " AND (" . $query_select . ")";
            } else {
                 $sql .= " WHERE " . $query_select;
            }
		}
		if (!empty($orderBy))
			$sql .= " ORDER BY t.".$db->escape($orderBy)." ";
		  else
			$sql .= " ORDER BY t.date ";

		if (!empty($orderBy))
			$sql .= "".strtoupper($db->escape($axis))." ";
		  else
			$sql .= " DESC ";

		$sql .= " LIMIT %d,%d";

        // SmartyPaginate static methods usage
        $_query = sprintf($sql, SmartyPaginate::getCurrentIndex(), SmartyPaginate::getLimit());
		$items = $db->get_results($_query);

		$_row = $db->get_row("SELECT FOUND_ROWS() as total");
        SmartyPaginate::setTotal($_row->total);

        $array = [];
		if ($items) {
		foreach ($items as $obj) {
			$array[$obj->id]['id'] = $obj->id;
			$array[$obj->id]['info_hash'] = $obj->info_hash;
		$array[$obj->id]['title'] = $this->Fuckxss($obj->title);
				$array[$obj->id]['torrent_desc'] = $this->Fuckxss($obj->torrent_desc);
			$array[$obj->id]['date'] = $this->ago($obj->date);
			$array[$obj->id]['hits'] = $obj->hits;
			$array[$obj->id]['seeds'] = $obj->seeds;
			$array[$obj->id]['leechers'] = $obj->leechers;
			$array[$obj->id]['finished'] = $obj->finished;
			$array[$obj->id]['size'] = $this->bytesToSize($obj->size);
			$array[$obj->id]['announce'] = unserialize($obj->announce);
			$array[$obj->id]['name'] = $obj->name;
			$array[$obj->id]['c_icon'] = $obj->c_icon;
			$array[$obj->id]['c_name'] = $this->Fuckxss($obj->c_name);
			$array[$obj->id]['c_url'] = $this->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$this->Fuckxss($obj->url_strip)));
			$array[$obj->id]['torrentUrl'] = $this->makeUrl(array('page'=>'torrent-detail','id'=>$obj->id,'urlTitle'=>$this->Fuckxss($obj->url_title)));
	        }
			// $smarty->assign('getTorrents',$array);
			return $array;
		} else
			return false;
	}
	###
	// this returns all the categories with subs into a select
	public function Categories(string $act='getlist', mixed $val='', mixed $adminPanel='NULL'): mixed {
	  global $smarty,$conf;
        $db = DB::getInstance();

		$categories = new Categories(); // Assuming class categories exists in global namespace

        $output = '';

		switch ($act) {

			case 'getlist':
				$output = $categories->build_list($val);
			break;

			case 'getlistcollapsed':
				$output = $categories->build_list($val,"collapsed");
			break;

			case 'getsubcat':
				$output = $categories->browse_by_id($val);
			break;

			case 'getParent':
				$output = $categories->getParent($val);
			break;

			case 'add':
				$output = $categories->add_new($val['parent'] , $val["name"] , $val["desc"] , $val["icon"] , $val["strip"] );
			break;

			case 'update':
				$output = $categories->update($val['id'], $val['parent'] , $val["name"] , $val["desc"] , $val["icon"] , $val["strip"] );
			break;

			case 'delete':
				$output = $categories->deleteCat($val['catFrom'], $val['catTo'], $val["cat_action"]);
			break;

			case 'getOne':
				$output = $categories->getOne($val);
			break;

			case 'html':
				if ($adminPanel === true) {
					$categories->HtmlTree = array(
					"header" => "<ul class='unstyled'>",
					"BodyUnselected" => '<li> [prefix] <a href="'.$conf['baseurl'].'/admincp/categories/?catid=[id]&tokenAdmin='.$_COOKIE['tokenAdmin'].'"> [name] </a> </li>',
					"BodySelected" => '<li> [prefix] <a href="'.$conf['baseurl'].'/admincp/categories/?catid=[id]&tokenAdmin='.$_COOKIE['tokenAdmin'].'"><strong><font color="#000000">[name]</font></strong></a> </li>',
					"footer" => '</ul>',
					);

				} else {
					$categories->HtmlTree = array(
					"header" => "<ul class='nav nav-tabs nav-stacked'>",
					"BodyUnselected" => '<li> <a href="'.$conf['baseurl'].'/'.$this->makeUrl(array('page'=>'torrents','cat'=>'categorie','catid'=>'[id]')).'"> [name] </a> </li>',
					"BodySelected" => '<li>  <a href="'.$conf['baseurl'].'/'.$this->makeUrl(array('page'=>'torrents','cat'=>'categorie','catid'=>'[id]')).'"><strong><font color="#000000">[name]</font></strong></a> </li>',
					"footer" => '</ul>',
					);
				}

				$output = $categories->html_output($val);
			break;
		}

		return $output;
	}
	###
	public function getTorrent(int $id): array|string {
		global $conf;
        $db = DB::getInstance();
		$sql = "SELECT t.id,t.userid 	,t.info_hash,t.title,t.url_title,t.categorie,t.torrent_desc,t.date,t.hits,t.seeds,t.leechers,t.finished,t.size,t.announce,t.last_scrape,t.imgExt,users.name,categories.c_name FROM ".$this->prefix_db."torrents AS t ";
		$sql .= "INNER JOIN ".$this->prefix_db."categories ON t.categorie=categories.id ";
		$sql .= "INNER JOIN ".$this->prefix_db."users ON t.userid=users.id ";
		$sql .= "WHERE t.id = '".$db->escape((string)$id)."' ";

		$items = $db->get_results($sql);
        $array = [];
		if ($items)
		{

			foreach ($items as $obj) {
				$array['id'] = $obj->id;
				$array['userid'] = $obj->userid;
				$array['info_hash'] = $obj->info_hash;
				$array['title'] = $this->Fuckxss($obj->title);
				$array['url_title'] = $this->Fuckxss($obj->url_title);
				$array['categorie'] = $this->Fuckxss($obj->categorie);
				$array['c_name'] = $this->Fuckxss($obj->c_name);
				$array['torrent_desc'] = $this->Fuckxss($obj->torrent_desc);
				$array['date'] = $obj->date;
				$array['hits'] = $obj->hits;
				$array['seeds'] = $obj->seeds;
				$array['leechers'] = $obj->leechers;
				$array['finished'] = $obj->finished;
				$array['size'] = $this->bytesToSize($obj->size);
				$array['announce'] = unserialize($obj->announce);
				$array['name'] = $obj->name;
				$array['last_scrape'] = $obj->last_scrape;
				$array['imgExt'] = $obj->imgExt;
				$array['imgUrl'] = $obj->info_hash.$obj->imgExt;
				$array['uname'] = $this->Fuckxss($obj->name);
				$array['uname_url'] = $conf['baseurl'].'/'.$this->makeUrl(array('page'=>'user','act'=>$this->Fuckxss($obj->name))).'/';
				$array['cat_url'] = $conf['baseurl'].'/'.$this->makeUrl(array('page'=>'torrents','ghost'=>'cat','catid'=>$this->Fuckxss($obj->c_name)));
			    }
			return $array;
		} else
			return 'NULL';
	}
	###
	public function bytesToSize(int $bytes, int $precision = 2): string {
		$kilobyte = 1024;
		$megabyte = $kilobyte * 1024;
		$gigabyte = $megabyte * 1024;
		$terabyte = $gigabyte * 1024;

		if (($bytes >= 0) && ($bytes < $kilobyte)) {
		    return $bytes . ' B';

		} elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
		    return round($bytes / $kilobyte, $precision) . ' KB';

		} elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
		    return round($bytes / $megabyte, $precision) . ' MB';

		} elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
		    return round($bytes / $gigabyte, $precision) . ' GB';

		} elseif ($bytes >= $terabyte) {
		    return round($bytes / $terabyte, $precision) . ' TB';
		} else {
		    return $bytes . ' B';
		}
	}
	###
	public function updateHits(string $id): bool {
		$db = DB::getInstance();
			$sql = "UPDATE ".$this->prefix_db."torrents SET hits=(hits + 1) WHERE uniqueid='".$db->escape($id)."'";
			$db->query($sql);
		return true;
	}
	###
	public function makeId(int $car=8): string {
		$string = "";
		$chaine = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnpqrstuvwxy1234567890";
		// srand((double)microtime()*1000000); // srand is not needed in modern PHP, automatic seeding
		for($i=0; $i<$car; $i++) {
		$string .= $chaine[rand()%strlen($chaine)];
		}
	return $string;
	}
	###
	public function I18n(): void {

		global $conf;
		if(isset($_GET['strLangue'])) {

			$chaine = $_SERVER['REQUEST_URI'];
			$nbr = 13;
            // This URL manipulation looks brittle.
			$url = substr($chaine, 0, -$nbr);

            // Safer way: remove parameter from URL
            $url = preg_replace('/[?&]strLangue=[^&]+/', '', $_SERVER['REQUEST_URI']);
            $url = rtrim($url, '?&');

			$langAutorises = array('fr','en','ru');
			if (in_array($_GET['strLangue'],$langAutorises))
			$_SESSION['strLangue']=$_GET['strLangue'];
			$this->redirect($url);

		} else {
			if (empty($_SESSION['strLangue'])) {
			$_SESSION['strLangue'] = 'en';
			}
		}
	}
	###
	public function addUser(string $name, string $mail, string $pass, string $redirect='NULL', string $sendMail='NULL', string $isadmin="NULL"): bool {
		global $conf;
        $db = DB::getInstance();

		$db->query("SELECT id FROM users WHERE id != '".$db->escape('0')."' AND mail='".$db->escape($mail)."' OR name='".$db->escape($name)."' ");
		$user_details = $db->get_row();

		if (!$user_details) {

		$hash = $this->makeId(15);
		if ($isadmin!="NULL")
			$level = "6";
			else
			$level = "2";

		$query = "INSERT INTO ".$this->prefix_db."users (name,pass,mail,level,private_id)
	          VALUES (
			  '".$db->escape($name)."',
			  '".$this->obscure($pass)."',
			  '".$db->escape($mail)."',
			  '$level',
			  '".md5(uniqid((string)rand(), true))."'
			  )";
		$db->query($query);
		if ($sendMail!='NULL')
		$this->sendMail($name,$mail,$pass,$hash);
		if ($redirect!='NULL')
			$this->redirect($conf['baseurl'].'/zone-login');

		return true;

		} else
			return false;
	}
	###
	public function getAllgroups(): array|bool {
		global $hook; // Assuming hook global
        $db = DB::getInstance();

		$sql = "SELECT * FROM ".$this->prefix_db."users_group";
		$items = $db->get_results($sql,'ARRAY_A');

        $array = [];
		if ($items) {

			foreach ($items as $obj) {

				/*$array[$obj['id']]['id'] = $obj['id'];
				$array[$obj['id']]['group'] = $obj['group']; */

                // get_col_info is not implemented in my DB class yet correctly as ezSQL.
                // But looking at code, it iterates over columns.
                // $obj is already an associative array because of ARRAY_A

				foreach ( array_keys($obj) as $group ) {
					//var_dump($group.' : '.$obj[$group].'<br />');

				if ($group != 'id' && $group != 'group') {
					// $hook->admin_users_groups($group, $group, $obj[$group]);
					$array[$obj['id']]['auth'][$group] = $obj[$group];
				 } else
					$array[$obj['id']][$group] = $obj[$group];
				}
			}

			return $array;
		} else
			return false;
	}
	####
	public function addUserAuth(string $name, string $mail, string $sendMail='NULL', string $isadmin="NULL"): bool {
		global $conf;
        $db = DB::getInstance();

        // This function seems broken in original code: $user_profile is undefined.
        // I will keep it as is but fix syntax errors if any.
        // Or better, add FIXME comment.

		$db->query("SELECT id FROM users WHERE id != '".$db->escape('0')."' AND mail='".$db->escape($mail)."' OR name='".$db->escape($name)."' ");
		$user_details = $db->get_row();

		if (!$user_details) {

            // FIXME: $user_profile is undefined
            $user_profile = ['username' => $name, 'id' => ''];

			$query = "INSERT INTO users (id,name,pass,mail,level,auth_type,auth_id)
			      VALUES (
				  'NULL',
				  '".$db->escape($user_profile["username"])."',
				  'NULL',
				  'NULL',
				  '2',
				  'facebook',
				  '".$user_profile["id"]."'
				  )";
				$db->query($query);

            // FIXME: $pass, $hash undefined
            $pass = ''; $hash = '';
		if ($sendMail!='NULL')
		$this->sendMail($name,$mail,$pass,$hash);
            // FIXME: $redirect undefined
            $redirect = 'NULL';
		if ($redirect!='NULL')
			$this->redirect($conf['baseurl'].'/zone-login.html');
			return true;
		} else
			return false;
	}
	###
	public function EditUserInfo(string $pass='', string $mail, string $seemail, string $seeMytorrents, string $location, string $website, string $sign): bool {
		$db = DB::getInstance();
		if (empty($pass)) {
			$pass = '';
		} else {
			$pass = "pass='".$this->obscure($pass)."',";
		}
		$query = "UPDATE ".$this->prefix_db."users SET $pass
					mail='".$db->escape($mail)."',
					seemail='".$db->escape($seemail)."',
					seeMytorrents='".$db->escape($seeMytorrents)."',
					location='".$db->escape($location)."',
					website='".$db->escape($website)."',
					signature ='".$db->escape($sign)."'
					WHERE id = '".$db->escape($this->uid)."'";
		$db->query($query);
		$this->redirect($conf['baseurl'].'/account?token='.$_COOKIE['token']);
	return true;
	}
	###
	public function checkMail(string $mail): bool {
		# code...
		// Modern PHP filter
        return filter_var($mail, FILTER_VALIDATE_EMAIL) !== false;
	}
	###
	public function sendMail(string $user, string $mail, string $pass, string $hash): bool {
            // Check if class exists
            if (!class_exists('FormatMail')) {
                 require_once(dirname(__FILE__).'/../mailling/classes/class.formatmail.php');
            }

		// require_once('mailling/classes/class.formatmail.php');
		    $GLOBALS['NAME'] = $user;
		    $GLOBALS['USERNAME'] = $user;
		    $GLOBALS['PASSWORD'] = $pass;
		    //Importatnt: fill up all GLOBALS field before call this constructor
		    $FM = new \FormatMail(dirname(__FILE__).'/../mailling/templates/registration-'.$_SESSION['strLangue'].'.htm');
		    $FM->Mailer->FromName = $user;
		    // $FM->Mailer->From = $this->admin_mail;
		    $FM->Mailer->Subject = 'Registration';
		    $FM->Mailer->AddAddress($mail,$user);
		    //And now, send the mail...
		    if ($FM->Send())
	return true;
    return false;
	}
	###
	public function getThemes(string $dir, string $mode='folders'): array|bool{
	 $items = array();
	 if( !preg_match( "/^.*\/$/", $dir ) ) $dir .= '/';
         $handle = opendir( $dir );
	 if( $handle != false ){
	  while($item=readdir($handle))
	  {
	   if($item != '.' && $item != '..' && $item != 'asset')
	   {
	    // selon le mode choisi
	    switch($mode)
	    {
	     case 'folders' :
	      if(is_dir($dir.$item))
	       $items[] = $item;
	      break;

	     case 'files' :
	      if(!is_dir($dir.$item))
	       $items[] = $item;
	      break;

	     case  'all' :
	      $items[] = $item;
	    }
	   }
	  }
	  closedir($handle);
	  return $items;
	 }
	 else return false;
	}
	###
	public function makeTimestamp(string $date): int{
		$date = str_replace(array(' ', ':'), '-', $date);
		$c    = explode('-', $date);
		$c    = array_pad($c, 6, 0);
		array_walk($c, 'intval');
	return mktime($c[3], $c[4], $c[5], $c[1], $c[2], $c[0]);
	}
	###
	public function img_base64(string $image): string{
			// Read image path, convert to base64 encoding
			$imageData = base64_encode(file_get_contents($image));
			// Format the image SRC:  data:{mime};base64,{data};
			$src = 'data: '.mime_content_type($image).';base64,'.$imageData;
		return $src;
	}

	###
	public function Fuckxss(mixed $var): string {
            if ($var === null) return '';
			strip_tags((string)$var);
			$output = htmlspecialchars((string)$var, ENT_QUOTES);
		return $output;
	}
	###
	public function ago(int $time): string {

	   $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	   $lengths = array("60","60","24","7","4.35","12","10");
	   $now = time();

		   $difference     = $now - $time;
		   $tense         = "ago";

		   for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			   $difference /= $lengths[$j];
		   }
		   $difference = round($difference);
		   if($difference != 1) {
			   $periods[$j].= "s";
		   }
	   return "$difference $periods[$j] ago";
	}
	###
	public function get_web_page( string $url ): array {

		$ch = curl_init(); // open curl session
		// set curl options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13');

		$content = curl_exec( $ch );
		$err     = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
		curl_close($ch); // close curl session

		$header['errno']   = $err;
		$header['errmsg']  = $errmsg;
		$header['content'] = $content;

		return $header;
	}
}
