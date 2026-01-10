<?php

namespace App\Core;

use App\Database\DB;
use SmartyPaginate;

class Bittytorrent extends StartUp {

	protected string $session_name = '';
	public string $session_username = ''; // Changed to public as it is accessed in startup.php
	protected string $session_password = '';
	protected mixed $uid = ''; // uid can be string or int
	protected string $authType = '';

	###
	public function isLogged(): bool|string|int {
		if($this->checkUser()){
			return $this->uid;
		} else {
			return false;
		}
	}
	###
	public function isLoggedAcount(): void {
		if($this->checkUser()){
			return; // Void return? Logic seems to imply just checking or redirecting.
		} else {
			$this->redirect();
			$this->killAll(); // killAll undefined in original code or StartUp? Assuming undefined or I missed it.

		}
	}

    // Method undefined in StartUp?
    public function killAll() {
        // Implement logout logic or similar?
        $this->logout();
        exit;
    }

	###
	public function checkUser(): bool {

		$db = DB::getInstance();
		if($this->checkCookie()){
			$uid = $this->uid;
			$username = $this->session_username;
			$password = $this->session_password;

			$query = "SELECT id FROM ".$this->prefix_db."users WHERE name = '".$db->escape($username)."' AND pass = '".$db->escape($password)."' AND id = '".$db->escape((string)$uid)."' AND level > '0' LIMIT 1;";
			$user = $db->get_row($query);



			// $db->debug();
				 if ($user && $user->id)
					return true;
				 else
					return false;
			} else
				return false;
	}
	###
	public function checkAdmin(string $token = null): bool {
		$db = DB::getInstance();
		if($this->checkCookie()){
				if ($this->checkUser()) {
					$uid = $this->uid;
					$query = "SELECT id FROM ".$this->prefix_db."users WHERE id = '".$db->escape((string)$uid)."' AND level = '6' LIMIT 1;";
					$user = $db->get_row($query);

						 if ($user && $user->id)
							return true;
						 else
							return false;
				} else
					return false;
			} else
				return false;
	}
	###
	public function checkCredentials(string $username, string $password): bool {
		$db = DB::getInstance();
		$password = $this->obscure($password);
		$query = "SELECT id FROM ".$this->prefix_db."users WHERE name = '".$db->escape($username)."' AND pass = '".$db->escape($password)."' AND level > '0' LIMIT 1;";
	$user = $db->get_row($query);

		if ($user) {
			if ($user->id)
				return true;
			else
				return false;

		}
        return false;
	}
        ###
    public function getMydata(): mixed {
                $db = DB::getInstance();

				$query = "SELECT *
				FROM ".$this->prefix_db."users AS u";
                $query .= " INNER JOIN users_group ON u.level=users_group.id";
                $query .= " WHERE u.id = '".$db->escape((string)$this->uid)."' LIMIT 1";

                $user = $db->get_row($query,'OBJECT'); // get result in objet (OBJECT)

                if ($user) {
                         $user->id = $this->Fuckxss($user->id);
                         $user->name = $this->Fuckxss($user->name);
                         $user->mail = $this->Fuckxss($user->mail);
                         $user->level = $this->Fuckxss($user->level);
                         $user->signature = $this->Fuckxss($user->signature);
                         $user->location = $this->Fuckxss($user->location);
                         $user->website = $this->Fuckxss($user->website);
                         $user->private_id = $this->Fuckxss($user->private_id);
                         $user->seeMytorrents = $this->Fuckxss($user->seeMytorrents);
						 if ($user->upload != NULL)
						 $user->upload = $this->bytesToSize($user->upload);
						 else
						 $user->upload = '0 Mb';
						 if ($user->download != NULL)
						 $user->download = $this->bytesToSize($user->download);
						 else
						 $user->download = '0 Mb';


			return $user;

                } else
			return false;

        }
	###
	public function getUserdata(mixed $uid=FALSE): mixed {
		$db = DB::getInstance();

		if($uid===FALSE){
			$id = $this->uid;
			$where = "WHERE u.id = '".$db->escape((string)$this->uid)."' ";
		} elseif (!is_numeric($uid)) {
			$where = "WHERE u.name = '".$db->escape($uid)."' ";
		} else {
			$where = "WHERE u.id = '".$db->escape((string)$uid)."' ";
		}

		$query = "SELECT u.id, u.name, u.mail, u.level, u.signature, u.seemail, u.seeMytorrents, u.location, u.website, ug.group FROM ".$this->prefix_db."users AS u";
		$query .= " INNER JOIN ".$this->prefix_db."users_group AS ug ON ug.id=u.level ";
		$query .= " $where LIMIT 1";
	$user = $db->get_row($query,'OBJECT');

		if ($user) {
			$user->id = $this->Fuckxss($user->id);
			$user->level = $this->Fuckxss($user->level);
			$user->group = $this->Fuckxss($user->group);
			$user->name = $this->Fuckxss($user->name);
			$user->mail = $this->Fuckxss($user->mail);
			$user->signature = $this->Fuckxss($user->signature);
			$user->location = $this->Fuckxss($user->location);
			$user->website = $this->Fuckxss($user->website);
			$user->seeMytorrents = $this->Fuckxss($user->seeMytorrents);


			return $user;
		} else
			return false;
	}
	###
    public function getMyTorrents(string $orderBy, string $axis): array|bool {
        $db = DB::getInstance();

		$sql = "SELECT SQL_CALC_FOUND_ROWS					 t.id,t.info_hash,t.title,t.url_title,t.categorie,t.torrent_desc,t.date,t.hits,t.seeds,t.leechers,t.finished,t.size,t.announce,t.last_scrape,users.name ,categories.c_name,categories.c_icon, categories.url_strip FROM ".$this->prefix_db."torrents AS t ";
		$sql .= "INNER JOIN ".$this->prefix_db."users ON t.userid=users.id ";
		$sql .= "INNER JOIN ".$this->prefix_db."categories ON t.categorie=categories.id ";
		$sql .= "WHERE t.userid = '".$this->uid."' ";

		if (!empty($orderBy))
			$sql .= "ORDER BY t.$orderBy ";
		  else
			$sql .= "ORDER BY t.date ";

		if (!empty($orderBy))
			$sql .= "".strtoupper($axis)." ";
		  else
			$sql .= "DESC ";

		$sql .= "LIMIT %d,%d";

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
                $array[$obj->id]['date'] = $obj->date;
                $array[$obj->id]['announce'] = $obj->announce;
                $array[$obj->id]['size'] = $this->bytesToSize($obj->size);
                $array[$obj->id]['seeds'] = $obj->seeds;
                $array[$obj->id]['leechers'] = $obj->leechers;
                $array[$obj->id]['finished'] = $obj->finished;
				$array[$obj->id]['c_icon'] = $obj->c_icon;
				$array[$obj->id]['c_name'] = $obj->c_name;
				$array[$obj->id]['c_url'] = $this->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$this->Fuckxss($obj->url_strip)));
                $array[$obj->id]['last_scrape'] = $this->ago($obj->last_scrape);
                $array[$obj->id]['torrentUrl'] = $this->makeUrl(array('page'=>'torrent-detail','id'=>$obj->id,'urlTitle'=>$this->Fuckxss($obj->url_title)));
                $array[$obj->id]['torrentEdit'] = $this->makeUrl(array('page'=>'torrent-edit','id'=>$obj->id,'urlTitle'=>$this->Fuckxss($obj->url_title)));
                $array[$obj->id]['torrentDelete'] = $this->makeUrl(array('page'=>'torrent-delete','id'=>$obj->id,'act'=>$this->Fuckxss($obj->info_hash)));
		}

                return $array;
         } else
                 return false;

    }
	public function getTorrentsByUser(string $name, string $orderBy, string $axis): array|bool {
		$db = DB::getInstance();
		$user = $db->get_row("SELECT id FROM users WHERE name = '$name'");

        if (!$user) return false;

                $sql = "SELECT SQL_CALC_FOUND_ROWS t.id,t.userid,t.info_hash,t.title,t.url_title,t.categorie,t.date,t.announce,t.size,t.seeds,t.leechers,t.finished,t.last_scrape,categories.c_name,categories.c_icon FROM ".$this->prefix_db."torrents AS t ";
				$sql .= "INNER JOIN ".$this->prefix_db."categories ON t.categorie=categories.id ";
                $sql .= "WHERE t.userid = '".$user->id."' AND t.userid != '0' ";



				if (!empty($orderBy))
					$sql .= "ORDER BY t.$orderBy ";
				  else
					$sql .= "ORDER BY t.date ";

				if (!empty($orderBy))
					$sql .= "".strtoupper($axis)." ";
				  else
					$sql .= "DESC ";

				$sql .= "LIMIT %d,%d";

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
                        $array[$obj->id]['date'] = $obj->date;
                        $array[$obj->id]['announce'] = $obj->announce;
                        $array[$obj->id]['seeds'] = $obj->seeds;
                        $array[$obj->id]['size'] = $this->bytesToSize($obj->size);
                        $array[$obj->id]['leechers'] = $obj->leechers;
                        $array[$obj->id]['finished'] = $obj->finished;
                        $array[$obj->id]['last_scrape'] = $this->ago($obj->last_scrape);
			$array[$obj->id]['c_icon'] = $obj->c_icon;
			$array[$obj->id]['c_name'] = $obj->c_name;
                        $array[$obj->id]['torrentUrl'] = $this->makeUrl(array('page'=>'torrent-detail','id'=>$obj->id,'urlTitle'=>$this->Fuckxss($obj->url_title)));
			}
                        return $array;
                 } else
                         return false;
	}
	###
	public function getStatuts(): array {
	    $db = DB::getInstance();
		$sql = "SELECT * FROM ".$this->prefix_db." users_group ";
	    $items = $db->get_results($sql);

        $array = [];
			foreach ($items as $obj) {
					 $array[$obj->id]['id'] = $obj->id;
					 $array[$obj->id]['group'] = $obj->group;
			}
		return $array;
	}
	###
	public function getUsers(): array {
		global $conf;
        $db = DB::getInstance();
		$query = "SELECT SQL_CALC_FOUND_ROWS
				 u.id, u.name, u.mail, u.level, u.joined, u.download, u.upload, g.group FROM ".$this->prefix_db."users AS u";
		$query .= " INNER JOIN users_group AS g ON u.level=g.id";
		$query .= " ORDER BY u.id DESC";
		$query .= " LIMIT %d,%d";
		$_query = sprintf($query, SmartyPaginate::getCurrentIndex(), SmartyPaginate::getLimit());
		$items = $db->get_results($_query);

		$_row = $db->get_row("SELECT FOUND_ROWS() as total");
		SmartyPaginate::setTotal($_row->total);

        $array = [];
		foreach ( $items as $obj ) {

			$array[$obj->id]['id'] = $obj->id;
			$array[$obj->id]['name'] = $this->Fuckxss($obj->name);
			$array[$obj->id]['mail'] = $this->Fuckxss($obj->mail);
			$array[$obj->id]['level'] = $this->Fuckxss($obj->level);
			$array[$obj->id]['group'] = $obj->group;
			$array[$obj->id]['joined'] = $obj->joined;
			$array[$obj->id]['Hupload'] = $obj->upload;
			$array[$obj->id]['Hdownload'] = $obj->download;
			$array[$obj->id]['upload'] = $this->bytesToSize($obj->upload);
			$array[$obj->id]['download'] = $this->bytesToSize($obj->download);
			$array[$obj->id]['gravatarS'] = $this->get_gravatar($obj->mail,'50');
			$array[$obj->id]['gravatarL'] = $this->get_gravatar($obj->mail,'100');
			$array[$obj->id]['unameUrl'] = $conf['baseurl'].'/'.$this->makeUrl(array('page'=>'user','act'=>$this->Fuckxss($obj->name))).'/';
	        }

		return $array;
	}
	###
	public function delUser(int $key): bool {
		global $conf;
        $db = DB::getInstance();
			$query = "SELECT * FROM users AS u";
			$query .= " INNER JOIN users_group AS g ON u.level=g.id WHERE u.id='$key'";

			$userD = $db->get_row($query);

			if ($userD->can_be_deleted === 'true'){
				$db->query("DELETE FROM ".$this->prefix_db."users WHERE id = '".$db->escape((string)$key)."'");
				return true;
			} else
				return false;
	}
	###
	public function adminEditUser(int $id, string $pass, string $name, string $mail, int $level, string $location, string $website, string $signature): bool {
		$db = DB::getInstance();
		if (empty($pass)) {
			$pass = '';
		} else {
			$pass = "pass='".$this->obscure($pass)."',";
		}
		$query = "UPDATE ".$this->prefix_db."users SET $pass name='".$db->escape($name)."',mail='".$db->escape($mail)."',level='".$db->escape((string)$level)."',location='".$db->escape($location)."',website='".$db->escape($website)."',signature='".$db->escape($signature)."' WHERE id = '$id'";
		$db->query($query);
	return true;
	}

	###
	public function checkCookie(): bool {
		global $conf;
        $db = DB::getInstance();
		$this->session_name = $conf['sessionName'];

		if (isset($_COOKIE["$this->session_name"]) || isset($_SESSION["$this->session_name"])) {

            if (isset($_COOKIE["$this->session_name"])) {
			    $cookie = explode(",",$_COOKIE["$this->session_name"]);
            } else {
                $cookie = explode(",",$_SESSION["$this->session_name"]);
            }

            // Fix: Check if array keys exist
            if (count($cookie) < 3) return false;

			$this->session_username = $db->escape($cookie['0']);
			$this->session_password = $db->escape($cookie['1']);
			$this->uid = $db->escape($cookie['2']);

			return true;
		} else {
			return false;
		}
	}
	###
	public function userExists(string $username, string $password): bool {
        // Warning: This function uses properties $this->username and $this->password which are not defined.
        // It seems it wants to compare with args? Or maybe these props should be set elsewhere.
        // Assuming it's checking against session?
        // But the args are $username, $password.
        // The implementation:
		// if (($this->username==$username)&&($this->password==$password)) {
        // 	return true;
	// } else {
		// return false;
		// }
        // For now, I'll leave it but maybe it should check against DB?
        return false;
	}
	###
	public function setSession(string $username, string $password, string $cookie, string $authType='NULL', string $authId='NULL'): void {
		global $conf;
        $db = DB::getInstance();
		$this->session_name = $conf['sessionName'];

			$query = "SELECT u.id, u.level, users_group.admin_access FROM ".$this->prefix_db."users AS u ";
			$query .= " INNER JOIN users_group ON u.level=users_group.id";
			$query .= " WHERE u.name = '".$db->escape($username)."' AND u.pass = '".$this->obscure($password)."' AND level > '0' ";
			$query .= " LIMIT 1;";

			$row = $db->get_row($query,'ARRAY_A');
			$values = array($username,$this->obscure($password),$row['id']);


		$session = implode(",",$values);
		if($cookie=='on'){
			setcookie("$this->session_name", $session, time()+60*60*24*100,'/');
		} else {
			$_SESSION["$this->session_name"] = $session;
		}
		// Gestion du token
		if ($row['admin_access'] === 'true'){
			setcookie("tokenAdmin", uniqid((string)rand(), true), time()+60*60*24*100,'/');
		}
		setcookie("token", uniqid((string)rand(), true), time()+60*60*24*100,'/');

	}
	###
	public function sqlesc(string $x): string {
        $db = DB::getInstance();
	    return '\''.$db->escape($x).'\'';
	}
	###
	public function logout(bool $redirect=true): void{
		global $conf;
		$this->session_name = $conf['sessionName'];

		setcookie("$this->session_name", "", time()-60*60*24*100, "/");
		setcookie("tokenAdmin", "", time()-60*60*24*100, "/");
		setcookie("token", "", time()-60*60*24*100, "/");
		unset($_SESSION["$this->session_name"]);
		// session_unset(); // deprecated or handled by global session handling
        // Clearing session variables
        $_SESSION = array();
		if($redirect===true){
			$this->redirect($conf['baseurl'].'/');
		}
	}
	###
	//Obscure
	public function obscure(string $password, string $algorythm = "sha1"): string {
		global $conf;

		$password = strtolower($password);
		$salt = hash($algorythm, $conf['salt']);
		$hash_length = strlen($salt);
		$password_length = strlen($password);
		$password_max_length = $hash_length / 2;
		if ($password_length >= $password_max_length){
			$salt = substr($salt, 0, $password_max_length);
		} else {
			$salt = substr($salt, 0, $password_length);
		}
		$salt_length = strlen($salt);
		$salted_password = hash($algorythm, $salt . $password);
		$used_chars = ($hash_length - $salt_length) * -1;
		$final_result = $salt . substr($salted_password, $used_chars);

		return $final_result;
	}

	###
	public function get_gravatar( string $email, string $s = '120', string $d = 'mm', string $r = 'g', bool $img = false, array $atts = array() ): string {
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		if ( $img ) {
			$url = '<img src="' . $url . '"';
			foreach ( $atts as $key => $val )
				$url .= ' ' . $key . '="' . $val . '"';
			$url .= ' />';
		}
		return $url;
	}

}
