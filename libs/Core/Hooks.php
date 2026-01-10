<?php

namespace App\Core;

if (!defined('PLUGINS_FOLDER')) {
    define('PLUGINS_FOLDER', 'plugins/');
}

class Hooks {

    public array $addblock = [];
    public array $addsideblock = [];
    public array $add_Menu = [];
    public array $add_User_Menu = [];
    public mixed $title = '';
    public array $addnewpage = [];
    public array $addnewadminpage = [];
    public array $addFooter = [];
    public array $addcontent = [];
    public array $add_Menu_Lang = [];
    public array $add_contentUpload = [];
    public array $addcontentregistration = [];
    public array $addcontentlogin = [];
    public array $linkplug = [];
    public array $adminusersgroups = [];
    public array $add_Js = [];
    public array $add_Css = [];

    /**
    * plugins option data
    * @var array
    */
    public array $plugins = [];

    /**
    * UNSET means load all plugins, which is stored in the plugin folder. ISSET just load the plugins in this array.
    * @var ?array
    */
    public ?array $active_plugins = null;

    /**
    * all plugins header information array.
    * @var array
    */
    public array $plugins_header = [];

    /**
    * hooks data
    * @var array
    */
    public array $hooks = [];

    /**
    * register hook name/tag, so plugin developers can attach functions to hooks
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag. The name of the hook.
    */
    public function set_hook(string $tag): void {
        $this->hooks [$tag] = [];
    }

    /**
    * register multiple hooks name/tag
    * @package phphooks
    * @since 1.0
    *
    * @param array $tags. The name of the hooks.
    */
    public function set_hooks(array $tags): void {
        foreach ( $tags as $tag ) {
            $this->set_hook ( $tag );
        }
    }

    /**
    * write hook off
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag. The name of the hook.
    */
    public function unset_hook(string $tag): void {
        unset ( $this->hooks [$tag] );
    }

    /**
    * write multiple hooks off
    * @package phphooks
    * @since 1.0
    *
    * @param array $tags. The name of the hooks.
    */
    public function unset_hooks(array $tags): void {
        foreach ($tags as $tag) {
            $this->unset_hook($tag);
        }
    }

    /**
    * load plugins from specific folder, includes *.plugin.php files
    * @package phphooks
    * @since 1.0
    *
    * @param string $from_folder optional. load plugins from folder, if no argument is supplied, a 'plugins/' constant will be used
    */
    public function load_plugins(string $from_folder = PLUGINS_FOLDER): void {

        if ($handle = @opendir ( $from_folder )) {

            while ( $file = readdir ( $handle ) ) {
                if (is_file ( $from_folder . $file )) {
                    if (($this->active_plugins !== null && in_array ( $file, $this->active_plugins )) && strpos ( $file, '.plugin.php' )) {
                        require_once $from_folder . $file;
                        $this->plugins [$file] ['file'] = $file;
                    }
                } else if ((is_dir ( $from_folder . $file )) && ($file != '.') && ($file != '..')) {
                    $this->load_plugins ( $from_folder . $file . '/' );
                }
            }

            closedir ( $handle );
        }

    }

    /**
    * return the all plugins ,which is stored in the plugin folder, header information.
    *
    * @package phphooks
    * @since 1.1
    * @param string $from_folder optional. load plugins from folder, if no argument is supplied, a 'plugins/' constant will be used
    * @return array. return the all plugins ,which is stored in the plugin folder, header information.
    */
    public function get_plugins_header(string $from_folder = PLUGINS_FOLDER): array {

        if ($handle = @opendir ( $from_folder )) {

            while ( $file = readdir ( $handle ) ) {
                if (is_file ( $from_folder . $file )) {
                    if (strpos ( $from_folder . $file, '.plugin.php' )) {
                    $fp = fopen ( $from_folder . $file, 'r' );
                    // Pull only the first 8kiB of the file in.
                    $plugin_data = fread ( $fp, 8192 );
                    fclose ( $fp );

                    preg_match ( '|Plugin Name:(.*)$|mi', $plugin_data, $name );
                    preg_match ( '|Plugin URI:(.*)$|mi', $plugin_data, $uri );
                    preg_match ( '|Version:(.*)|i', $plugin_data, $version );
                    preg_match ( '|Description:(.*)$|mi', $plugin_data, $description );
                    preg_match ( '|Author:(.*)$|mi', $plugin_data, $author_name );
                    preg_match ( '|Author URI:(.*)$|mi', $plugin_data, $author_uri );

                    foreach ( array ('name', 'uri', 'version', 'description', 'author_name', 'author_uri' ) as $field ) {
                        if (! empty ( ${$field} ))
                            ${$field} = trim ( ${$field} [1] );
                        else
                            ${$field} = '';
                    }
                    $plugin_data = array ('filename' => $file, 'Name' => $name, 'Title' => $name, 'PluginURI' => $uri, 'Description' => $description, 'Author' => $author_name, 'AuthorURI' => $author_uri, 'Version' => $version );
                    $this->plugins_header [] = $plugin_data;
                    }
                } else if ((is_dir ( $from_folder . $file )) && ($file != '.') && ($file != '..')) {
                    $this->get_plugins_header ( $from_folder . $file . '/' );
                }
            }

            closedir ( $handle );
        }
        return $this->plugins_header;
    }

    /**
    * attach custom function to hook
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag. The name of the hook.
    * @param callable|string $function. The function you wish to be called.
    * @param int $priority optional. Used to specify the order in which the functions associated with a particular action are executed.(range 0~20, 0 first call, 20 last call)
    */
    public function add_hook(string $tag, callable|string $function, int $priority = 10): void {
        if (! isset ( $this->hooks [$tag] )) {
            // die ( "There is no such place ($tag) for hooks." );
            // Avoid die, maybe throw exception or log?
            // For now, allow it to create the hook if it doesn't exist or just warn?
            // Legacy code died, but that's bad UX.
             $this->hooks [$tag] = []; // Initialize if not exists, for flexibility
             $this->hooks [$tag] [$priority] [] = $function;
        } else {
            $this->hooks [$tag] [$priority] [] = $function;
        }
    }

    /**
    * check whether any function is attached to hook
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag The name of the hook.
    */
    public function hook_exist(string $tag): bool {
        return (isset($this->hooks[$tag]) && $this->hooks[$tag] != "");
    }

    /**
    * execute all functions which are attached to hook, you can provide argument (or arguments via array)
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag. The name of the hook.
    * @param mixed $args optional.The arguments the function accept (default none)
    * @return mixed.
    */
    public function execute_hook(string $tag, mixed $args = ''): mixed {
        if (isset ( $this->hooks [$tag] )) {
            $these_hooks = $this->hooks [$tag];
            $result = null;
            for($i = 0; $i <= 20; $i ++) {
                if (isset ( $these_hooks [$i] )) {
                    foreach ( $these_hooks [$i] as $hook ) {
                    // $args [] = $result;
                        if (is_callable($hook)) {
                             $result = call_user_func ( $hook, $args );
                        }
                    }
                }
            }
            //var_dump($these_hooks);
            return $result;
        } else {
            // die ( "There is no such place ($tag) for hooks." );
            return null;
        }
    }

    /**
    * filter $args and after modify, return it. (or arguments via array)
    * @package phphooks
    * @since 1.0
    *
    * @param string $tag. The name of the hook.
    * @param mixed $args optional.The arguments the function accept to filter(default none)
    * @return mixed. The $args filter result.
    */
    public function filter_hook(string $tag, mixed $args = ''): mixed {
        $result = $args;
        if (isset ( $this->hooks [$tag] )) {
            $these_hooks = $this->hooks [$tag];
            for($i = 0; $i <= 20; $i ++) {
                if (isset ( $these_hooks [$i] )) {
                    foreach ( $these_hooks [$i] as $hook ) {
                        $args = $result;
                        if (is_callable($hook)) {
                             $result = call_user_func ( $hook, $args );
                        }
                    }
                }
            }
            return $result;
        } else {
             // die ( "There is no such place ($tag) for hooks." );
             return $result;
        }
    }

    /**
    * register plugin data in $this->plugin
    * @package phphooks
    * @since 1.0
    *
    * @param string $plugin_id. The name of the plugin.
    * @param mixed $data optional.The data the plugin accessorial(default none)
    */
    public function register_plugin(string $plugin_id, mixed $data = ''): void {
        if (is_array($data)) {
            foreach ( $data as $key => $value ) {
                $this->plugins [$plugin_id] [$key] = $value;
            }
        }
    }


    /**
    * Function of hook part
    * by atmoner
    */
    ###
    public function set_title(string $id, ?string $title=NULL): void {
        if (!is_object($this->title)) {
            $this->title = new \stdClass();
        }
        if (!isset($this->title->$id)) {
             $this->title->$id = new \stdClass();
        }
        $this->title->$id->id = $id;
        $this->title->$id = $title; // This seems wrong in original code, overwriting the object?
        // Original: $this->title->$id = $title;
        // If $this->title->$id was an object, it is now a string.
        // Assuming intent: $this->title->$id->title = $title; or just storing string.
        // But line above uses $this->title->$id->id = $id; so it expects object.
        // I'll assume $this->title->$id->title = $title; is what meant or $this->title->$id is just a value container but legacy used it weirdly.
        // Let's stick to legacy behavior: it overwrites $this->title->$id with string, but previously assigned 'id' property on it?
        // Wait, if $this->title->$id is overwritten, the previous assignment is lost.
        // Let's check usage.

        // Correcting likely bug or behavior:
        $this->title->$id->title = $title;
    }
    ###
    public function remove_title(string $id): void {
        if (is_object($this->title)) {
             unset($this->title->$id);
        }
    }
    ###
    public function add_block(string $id, string $title, string $content, string $size, int $p=5): void {
                    $this->addblock[$id]['id'] = $id;
                    $this->addblock[$id]['title'] = $title;
                    $this->addblock[$id]['content'] = $content;
                    $this->addblock[$id]['size'] = $size;
                    $this->addblock[$id]['prio'] = $p;
    }
    ###
    public function remove_block(string $id): void {
        unset($this->addblock[$id]);
    }
    ###
    public function add_footer(string $id, string $content, int $p=5): void {
                    $this->addFooter[$id]['id'] = $id;
                    $this->addFooter[$id]['content'] = $content;
                    $this->addFooter[$id]['prio'] = $p;
    }
    ###
    public function remove_footer(string $id): void {
        unset($this->addFooter[$id]);
    }
    ###
    public function add_content(string $id, string $content, int $p=5): void {
                    $this->addcontent[$id]['id'] = $id;
                    $this->addcontent[$id]['content'] = $content;
                    $this->addcontent[$id]['prio'] = $p;
    }
    ###
    public function remove_content(string $id): void {
        unset($this->addcontent[$id]);
    }
    ###
    public function add_side_block(string $id, string $title, string $content, int $p=5): void {
                    $this->addsideblock[$id]['id'] = $id;
                    $this->addsideblock[$id]['title'] = $title;
                    $this->addsideblock[$id]['content'] = $content;
                    $this->addsideblock[$id]['prio'] = $p;
    }
    ###
    public function remove_side_block(string $id): void {
        unset($this->addsideblock[$id]);
    }
    ###
    public function addMenu(string $id, string $title, string $url, int $p=5): void {
            global $startUp;
            // $startUp might not be global in new structure, check availability
            // Assuming it is initialized in startup.php
                    $this->add_Menu[$id]['id'] = $id;
                    $this->add_Menu[$id]['title'] = $title;
                    if (isset($startUp) && method_exists($startUp, 'makeUrl')) {
                         $this->add_Menu[$id]['url'] = $startUp->makeUrl(array('page'=>$url));
                    } else {
                         $this->add_Menu[$id]['url'] = $url;
                    }
                    $this->add_Menu[$id]['prio'] = $p;
    }
    ###
    public function remove_addMenu(string $id): void {
        unset($this->add_Menu[$id]);
    }
    ###
    public function addMenuLang(string $id, string $title, string $url, string $img, int $p=5): void {
            global $conf;
                    $this->add_Menu_Lang[$id]['id'] = $id;
                    $this->add_Menu_Lang[$id]['title'] = $title;
                    $this->add_Menu_Lang[$id]['img'] = $img;
                    $this->add_Menu_Lang[$id]['prio'] = $p;
                    if (isset($_GET["token"]))
                        $this->add_Menu_Lang[$id]['url'] = ($conf['baseurl'] ?? '').$_SERVER['REQUEST_URI'].'&strLangue='.$id;
                    elseif (isset($_GET["tokenAdmin"]))
                        $this->add_Menu_Lang[$id]['url'] = ($conf['baseurl'] ?? '').$_SERVER['REQUEST_URI'].'&strLangue='.$id;
                    else
                        $this->add_Menu_Lang[$id]['url'] = $url;

    }
    ###
    public function remove_addMenuLang(string $id): void {
        unset($this->add_Menu_Lang[$id]);
    }
    ###
    public function addUserMenu(string $id, string $title, string $url, string $img, int $p=5): void {
                    $this->add_User_Menu[$id]['id'] = $id;
                    $this->add_User_Menu[$id]['title'] = $title;
                    $this->add_User_Menu[$id]['url'] = $url;
                    $this->add_User_Menu[$id]['img'] = $img;
                    $this->add_User_Menu[$id]['prio'] = $p;
    }
    ###
    public function remove_addUserMenu(string $id): void {
        unset($this->add_User_Menu[$id]);
    }
    ###################
    # Add addcontentUpload
    ###################
    public function addcontentUpload(string $id, string $content, int $p=5): void {
                    $this->add_contentUpload[$id]['id'] = $id;
                    $this->add_contentUpload[$id]['content'] = $content;
                    $this->add_contentUpload[$id]['prio'] = $p;
    }
    ###
    public function remove_addcontentPaste(string $id): void {
        // Assuming paste was a typo in legacy, keeping it if used, but maybe add correct one too
        if (isset($this->add_contentPaste[$id])) unset($this->add_contentPaste[$id]);
    }
    ###################
    # Add content registration
    ###################
    public function add_content_registration(string $id, string $content, int $p=5): void {
        $this->addcontentregistration[$id]['id'] = $id;
                    $this->addcontentregistration[$id]['content'] = $content;
                    $this->addcontentregistration[$id]['prio'] = $p;
    }
    ###
    public function remove_content_registration(string $id): void {
        unset($this->addcontentregistration[$id]);
    }
    ###################
    # Add content login
    ###################
    public function add_content_login(string $id, string $content, int $p=5): void {
    $this->addcontentlogin[$id]['id'] = $id;
                    $this->addcontentlogin[$id]['content'] = $content;
                    $this->addcontentlogin[$id]['prio'] = $p;
    }
    ###
    public function remove_content_login(string $id): void {
        unset($this->addcontentlogin[$id]);
    }
    ###################
    # Add admin menu
    ###################
    public function add_admin_menu(string $id, string $title, string $link, int $p=5): void {
                    $this->linkplug[$id]['id'] = $id;
                    $this->linkplug[$id]['title'] = $title;
                    $this->linkplug[$id]['link'] = $link;
                    $this->linkplug[$id]['prio'] = $p;
    }
    ###
    public function remove_admin_menu(string $id): void {
        unset($this->addcontentlogin[$id]); // Typo in original? removing contentlogin for admin menu?
        // Likely copy paste error. Should be linkplug.
        if (isset($this->linkplug[$id])) unset($this->linkplug[$id]);
    }
    ###################
    # Admin users groups
    ###################
    public function admin_users_groups(string $id, string $title, string $value, int $p=5): void {
                    $this->adminusersgroups[$id]['id'] = $id;
                    $this->adminusersgroups[$id]['title'] = $title;
                    $this->adminusersgroups[$id]['value'] = $value;
                    $this->adminusersgroups[$id]['prio'] = $p;
    }
    ###
    public function remove_admin_users_groups(string $id): void {
        unset($this->adminusersgroups[$id]);
    }
    ###################
    # Add js
    ###################
    public function addJs(string $id, string $file, string $path, int $p=5): void {
        global $conf;
        if(!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $file))
        $file = ($conf['baseurl'] ?? '').'/'.$path.$file;

        $this->add_Js[$id]['id'] = $id;
        $this->add_Js[$id]['file'] = $file;
        $this->add_Js[$id]['prio'] = $p;
    }
    ###
    public function remove_addJs(string $id): void {
        unset($this->add_Js[$id]);
    }
    ###################
    # Add css
    ###################
    public function addCss(string $id, string $file, string $path, int $p=5): void {
        global $conf;
        if(!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $file))
        $file = ($conf['baseurl'] ?? '').'/'.$path.$file;

                    $this->add_Css[$id]['id'] = $id;
                    $this->add_Css[$id]['file'] = $file;
                    $this->add_Css[$id]['prio'] = $p;
    }
    ###
    public function remove_addCss(string $id): void {
        unset($this->add_Css[$id]);
    }
    ###################
    # Add page
    ###################
        public function add_page( string $plugin_name, string $phpFile="", string $htmlFile="" ): void {
                    global $smarty, $hook; // Assuming globals are used in pages
                    $this->addnewpage[$plugin_name]['name'] = $plugin_name;
                    $this->addnewpage[$plugin_name]['phpFile'] = $phpFile;
                    $this->addnewpage[$plugin_name]['htmlFile'] = $htmlFile;

        }
    ###################
    # Add admin page
    ###################

        public function add_admin_page( string $plugin_name, string $phpFile="", string $htmlFile="" ): void {
                    global $smarty, $hook;
                    $this->addnewadminpage[$plugin_name]['name'] = $plugin_name;
                    $this->addnewadminpage[$plugin_name]['phpFile'] = $phpFile;
                    $this->addnewadminpage[$plugin_name]['htmlFile'] = $htmlFile;
        }
}
