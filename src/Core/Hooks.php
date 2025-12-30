<?php
namespace BittyTorrent\Core;

class Hooks {
    public $active_plugins = [];
    public $addnewpage = [];

    public function set_hooks($hooks) {}
    public function load_plugins() {}
    public function add_hook($tag, $func, $priority=10) {}
    public function register_plugin($id, $data) {}
    public function addMenuLang($a,$b,$c,$d,$e) {}
    public function addMenu($a,$b,$c,$d) {}
    public function addUserMenu($a,$b,$c,$d,$e) {}
    public function addJs($a,$b,$c,$d) {}
    public function addCss($a,$b,$c,$d) {}
    public function hook_exist($hook) { return false; }
    public function execute_hook($hook) {}
    public function add_page($name, $php, $html) {
        $this->addnewpage[] = ['name'=>$name, 'phpFile'=>$php, 'htmlFile'=>$html];
    }
    public function add_block($a,$b,$c,$d,$e) {}
    public function add_side_block($a,$b,$c,$d) {}
}
