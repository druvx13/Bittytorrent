<?php

namespace App\Core;

use App\Database\DB;

class Categories
{


public array $HtmlTree = [];

public string $name_prefix  = "&nbsp;&nbsp;";	// this is the prefix which will be added to the category name depending on its position usually use space.
public string $table_name   = "categories";
public string $itemsTable   = "items";		// this is the name of the table which contain the items associated to the categories
public string $CID_FieldName= "category_id";	 // this is the field name in the items table which refere to the ID of the item's category.

// use the following keys into the $HtmlTree varialbe.
public array $fields = array(
// field		=> field name in database ( sql structure )
"id"  		=> "id",
"position" 	=> "position",
"name"		=> "c_name",
"desc"		=> "c_desc",
"icon"		=> "c_icon",
"group"		=> "c_group",
"strip"		=> "url_strip",
);
/**************************************************
	--- NO CHANGES TO BE DONE BELOW ---
**************************************************/

public array $c_list  = array();  // DON'T CHANGE THIS
public array $c_list_by_id = array(); // Undefined in original but used in get_children
public int $Group  = 0;		 // DON'T CHANGE THIS

public function __construct()
{
if(!isset($_COOKIE['tokenAdmin']))
      $_COOKIE['tokenAdmin'] = "";
$this->HtmlTree = array(
"header" 		 => '<table width=300px border=0 cellpadding=2 cellspacing=2>',
"BodyUnselected" => '<tr><td>[prefix]&raquo;<a href="?id=[id]&'.$_COOKIE['tokenAdmin'].'">[name]</a></td></tr>',
"BodySelected"	 => '<tr><td>[prefix]&raquo;<a href="?id=[id]&'.$_COOKIE['tokenAdmin'].'"><strong>[name]</strong></a></td></tr>',
"footer"		 => '</table>',
);

}

// ********************************************************
//		Add New Category
// ********************************************************


public function add_new(int|string $parent = 0 , string $name = '', string $desc = '', string $icon = '', string $strip = '' ): void {
	$db = DB::getInstance();
// lets get the position from the $parent value
$position  = $this->get_position((string)$parent);

// lets insert add the new category into the database.
$sql = "INSERT into ".$this->table_name."(position,c_name,c_desc,c_icon,c_group,url_strip)
		VALUES('','".$db->escape($name)."','".$db->escape($desc)."','".$db->escape($icon)."','".$this->Group."','".$db->escape($strip)."')";

$db->query($sql);
$insert_id = $db->insert_id;
$position .= $insert_id.">";


$sql = "UPDATE ".$this->table_name."
		SET position = '".$position."'
		WHERE id = '".$insert_id."'";

$db->query($sql);
}

// ********************************************************
//		Delete Category
// ********************************************************

public function deleteCat(string $from, string $to, string $action): string {
    global $startUp; // Assuming startUp global instance is available or pass it in
    // However, I can't rely on global startUp for prefix_db if I want cleaner code.
    // For now, I will assume table names are fixed or I access prefix via config/env if needed.
    // Legacy used $startUp->prefix_db.
    // I will try to use global $startUp for now to maintain behavior, assuming startup.php instantiates it.

    // Also $path is global in legacy.
    global $path;

	$db = DB::getInstance();

	$pathTorrents = $path.'/uploads/torrents/';
	$position = $this->get_position($from);

    $prefix_db = $startUp->prefix_db ?? ''; // Fallback if not set

	if ($action === 'move') {
			$sql = "UPDATE torrents
					SET categorie = '".$db->escape($to)."'
					WHERE categorie =  '".$db->escape($from)."'";
			$db->query($sql);

		// To finish, delete categorie
		$sqlDelCat = "DELETE FROM ".$this->table_name."
				WHERE position
				LIKE '".$db->escape($position)."%'";
		$db->query($sqlDelCat);

		$messageReturn = "All torrent are moved";

	} elseif ($action === 'delete') {
		// Delete cat + all torrent of this cat
		$sqlDelTor = "SELECT torrents.info_hash, torrents.categorie FROM ".$prefix_db."torrents ";
		$sqlDelTor .= "INNER JOIN ".$prefix_db."categories as c ON torrents.categorie=c.id ";
		$sqlDelTor .= "WHERE torrents.categorie = '".$db->escape($from)."' OR c.position RLIKE '^".$db->escape($from).">[0-9]+>$' ";

		$res = $db->get_results($sqlDelTor,'ARRAY_A');

        if ($res) {
            foreach ($res as $arr) {
                if (file_exists($pathTorrents . $arr['info_hash'] . '.torrent')) {
                    // var_dump($arr['info_hash']);
                    unlink($pathTorrents . $arr['info_hash'] . '.torrent');
                }
                $sqlDelsqlTor = "DELETE FROM torrents ";
                $sqlDelsqlTor .= "WHERE info_hash = '".$arr['info_hash']."' ";
                $db->query($sqlDelsqlTor);
            }
        }
		// To finish, delete categorie
		if ($position != NULL) {
			$sqlDelCat = "DELETE FROM ".$this->table_name."
					WHERE position
					LIKE '".$db->escape($position)."%'";
			$db->query($sqlDelCat);
			$messageReturn = "All torrent are delete!";
		} else
			$messageReturn = "Error, refresh your page!";

	} else {
        $messageReturn = "No action specified";
    }

	return $messageReturn;


}


// ********************************************************
//		Update Category
// ********************************************************

public function update(int|string $id , int|string $parent = 0 , string $name = '' , string $desc = '' , string $icon = '' , string $strip = ''): void
{
	$db = DB::getInstance();



// lets get the current position
$position     = $this->get_position((string)$id);
$new_position = $this->get_position((string)$parent).$id.">";

if($position != $new_position){
	// then we update all the sub_categories position to be still under the current category
	$sql1 = "SELECT id,position
			FROM ".$this->table_name."
			WHERE position	LIKE  '".$position."%'";
	$res = $db->get_results($sql1);

    if ($res) {
        foreach ($res as $sub) {
                $new_sub_position = str_replace($position,$new_position,$sub->position);
                $sql2 = "UPDATE ".$this->table_name."
                        SET position = '".$new_sub_position."'
                        WHERE id =  '".$sub->id."'";
                $db->query($sql2);
        }
    }

}
	// finally update the category position.
	$sql3 = "UPDATE ".$this->table_name."
			SET position = '".$new_position."',
			c_name = '".$db->escape($name)."',
			c_desc = '".$db->escape($desc)."',
			c_icon = '".$db->escape($icon)."',
			url_strip = '".$db->escape($strip)."'
			WHERE position	=  '".$db->escape($position)."'";

	$db->query($sql3);

}

// ********************************************************
//		Build Categories Array
// ********************************************************

public function build_list(int|string $id='NULL', string $collapsed=""): array //return an array with the categories ordered by position
{
global $startUp, $conf;
$db = DB::getInstance();

$RootPos = "";
$this->c_list = array();

if($id != 'NULL'){
$this_category  = $this->fetch($id);
if ($this_category) {
    $positions      = explode(">",$this_category['position']);
    $RootPos        = $positions[0];
}
}

// lets fetch the root categories
$sql = "SELECT *
		FROM ".$this->table_name."
		WHERE position	RLIKE '^([0-9]+>){1,1}$' AND c_group = '".$this->Group."'
		ORDER BY c_name";

$items = $db->get_results($sql,'ARRAY_A');

    if ($items) {
		foreach ($items as $root) {
				$root["prefix"] = $this->get_prefix($root['position']);
				$this->c_list[$root['id']] = $root;

                // Assuming startUp->makeUrl exists
                if (isset($startUp) && method_exists($startUp, 'makeUrl')) {
				    $this->c_list[$root['id']]['url'] = $conf['baseurl'].'/'.$startUp->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$root['url_strip']));
                } else {
                     $this->c_list[$root['id']]['url'] = '#';
                }

					if($RootPos == $root['id'] AND $id != 'NULL' AND $collapsed != ""){
					$this->list_by_id($id);
					continue;

					} else {

					// lets check if there is sub-categories
						if($collapsed == "" ){
						$has_children = $this->has_children($root['position']);
						if($has_children == TRUE) $this->get_children($root['position'],0);
					}
				}
	        }
    }

return $this->c_list;
}


// ********************************************************
//		Check if Category has childrens
// ********************************************************

public function has_children(string $position): bool {
	$db = DB::getInstance();

	$check_sql = "SELECT id FROM ".$this->table_name." WHERE position RLIKE  '^".$db->escape($position)."[0-9]+>$'";
	$items = $db->query($check_sql); // query returns number of rows for SELECT in my DB class implementation or rows?
    // Wait, my DB::query returns int for SELECT (num rows) or true/false?
    // Let's check DB.php
    /*
        if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
                $this->lastResult = $stmt->fetchAll();
                $this->numRows = count($this->lastResult);
                return $this->numRows;
            }
    */
    // So it returns int (count).
    // Legacy code: $items = $db->query($check_sql,ARRAY_A);
    // ez_sql query returns true/false or result? usually ez_sql query returns number of rows affected or selected.
    // The legacy code check: if($items != "")

	if($items)
		return TRUE;
	else
		return FALSE;
}

// ********************************************************
//		Get Childrens
// ********************************************************

public function get_children(string $position , int $id = 0): void{
		$db = DB::getInstance();
		$sql = "SELECT *
				FROM ".$this->table_name."
				WHERE position	RLIKE '^".$db->escape($position)."[0-9]+>$'
				ORDER BY c_name";

		$res = $db->get_results($sql,'ARRAY_A');

        if ($res) {
		    foreach ($res as $child) {

		    $child["prefix"] = $this->get_prefix($child['position']);

			 if($id != 0) {
				$this->c_list_by_id[$child['id']] = $child;
				$has_children = $this->has_children($child['position']);
				if($has_children == TRUE){
					$this->get_children($child['position']);
				}
				continue;

			} else {

				$this->c_list[$child['id']] = $child;
				$has_children = $this->has_children($child['position']);
				if($has_children == TRUE)$this->get_children($child['position']);
			}
		    }
        }


}


// ********************************************************
//		Get childs of Specific Category only.
// ********************************************************

public function list_by_id(int|string $id): void {

	global $startUp, $conf;
	$this_category  = $this->fetch($id);

    if (!$this_category) return;

	$positions = explode(">",$this_category['position']);
	$pCount = count($positions);
	$i = 0;

	// lets fetch from top to center
	while($i < $pCount){
		$pos_id	   = $positions["$i"];
		if($pos_id == ""){$i++; continue;}
		$list = $this->browse_by_id($pos_id);

		if ($list != false) {
			foreach($list as $key=>$value){
				$this->c_list["$key"] = $value;
                if (isset($startUp) && method_exists($startUp, 'makeUrl')) {
				    $this->c_list["$key"]['url'] = $conf['baseurl'].'/'.$startUp->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$value['url_strip']));
                }
				$ni = $i + 1;
                if (isset($positions[$ni])) {
				    $nxt_id = $positions[$ni];
			        if($key == $nxt_id ) break;
                }
			}
		}
	 $i++;
	}

//center to end
$i = $pCount-1;

while($i >= 0){
$pos_id	 = $positions["$i"];
if($pos_id == ""){$i--; continue;}
$list = $this->browse_by_id($pos_id);

		if ($list != false) {
			foreach($list as $key=>$value){
				$ni = $i - 1;
				if($ni < 0) $ni =0;
				$nxt_id = $positions[$ni];
				if($key == $nxt_id ) break;
				$this->c_list["$key"] = $value;
                if (isset($startUp) && method_exists($startUp, 'makeUrl')) {
				    $this->c_list["$key"]['url'] = $conf['baseurl'].'/'.$startUp->makeUrl(array('page'=>'torrents','gost'=>'cat','catid'=>$value['url_strip']));
                }
			}
		}
	$i--;
}

}

/***************************************
    Get array of categories under specific category.
 ****************************************/

public function browse_by_id(int|string $id): array|bool // return array of categories under specific category.
{
	$db = DB::getInstance();
	$children 		= array();
	$this_category  = $this->fetch($id);
    if (!$this_category) return false;

	$position       = $this_category['position'];

	$sql = "SELECT *
			FROM ".$this->table_name."
			WHERE position	RLIKE '^".$db->escape($position)."(([0-9])+\>){1}$'
			ORDER BY c_name";

	$res = $db->get_results($sql,'ARRAY_A');

	if ($res) {
		foreach ($res as $child) {
			$child["prefix"] = $this->get_prefix($child['position']);
			$children[$child['id']] = $child;
		}
	} else
		$children = false;


return $children;
}

// ********************************************************
//		Get Position
// ********************************************************

public function get_position(int|string $id): string {
	    $db = DB::getInstance();
		$sql = "SELECT position
				FROM ".$this->table_name."
				WHERE url_strip = '".$db->escape((string)$id)."' OR id = '".$db->escape((string)$id)."'";

		$res = $db->get_row($sql,'ARRAY_A');

        return $res['position'] ?? '';
}

// ********************************************************
//		Get Prefix
// ********************************************************

public function get_prefix(string $position): string
{
$prefix = "";
$position_slices = explode(">",$position);
$count = count($position_slices) - 1;
for($i=1 ; $i < $count ; $i++){
$prefix .= $this->name_prefix;
}
return $prefix;
}

// ********************************************************
//		Fetch Category Record
// ********************************************************

public function fetch (int|string $id): ?array {
	$db = DB::getInstance();

if (is_numeric($id))
	$where =  "id = '".$db->escape((string)$id)."'";
else
	$where =  "url_strip = '".$db->escape((string)$id)."'";

$sql = "SELECT *
		FROM ".$this->table_name."
		WHERE $where";


$record = $db->get_row($sql,'ARRAY_A');

if ($record) {
    $record["prefix"] = $this->get_prefix($record['position']);
    $position_slices  = explode(">",$record['position']);
    $key              = count($position_slices)-3;
    if($key < 0) $key = 0;
    $record["parent"] = $position_slices["$key"];
    return $record;
}
return null;
}

// ********************************************************
//		Build HTML output
// ********************************************************

public function html_output(int|string $id=0): string
{
	 $tree  = $this->build_list($id,"collapsed"); // we have selected to view category


$output = "";
$output .= $this->HtmlTree['header'];

			if(is_array($tree))
			{
				foreach($tree as $c)
				{

					if($c['id'] == $id) 	$body = $this->HtmlTree['BodySelected'];
					else   						  	 	$body = $this->HtmlTree['BodyUnselected'];

					foreach($this->fields as $name => $field_name)
					{
                        if (isset($c[$field_name])) {
						    $body = str_replace("[$name]" ,$c["$field_name"],$body);
                        }


					}
                    if (isset($c['prefix'])) {
						$body = str_replace("[prefix]",$c['prefix'],$body);
                    }

				$output .= $body;
				}
			}

$output .= $this->HtmlTree['footer'];
return $output;
}


public function getOne(int|string $id): mixed {
		global $startUp;
        $db = DB::getInstance();
        $prefix_db = $startUp->prefix_db ?? '';

		$sql = "SELECT id,position,c_name,c_desc,c_icon, url_strip FROM ".$prefix_db."categories ";
		$sql .= "WHERE id = '".$db->escape((string)$id)."' ";

		$items = $db->get_row($sql);

		return $items;
	}

public function getParent(string $id): string {

		$items = explode(">",$id);

        if (count($items) >= 3) {
		    return $items[count($items)-3];
        }
        return '';
	}
}
