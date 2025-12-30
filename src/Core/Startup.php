<?php
namespace BittyTorrent\Core;

class Startup {
    public function getConfigs() {
        return [
            'theme' => 'boot3',
            'timecache' => 0,
            'title' => 'BittyTorrent',
            'baseurl' => 'http://localhost'
        ];
    }

    public function I18n() {}
    public function isLogged() { return false; }
    public function getMydata() { return (object)['upload'=>0, 'download'=>0, 'can_upload'=>'false']; }
    public function checkAdmin($token) { return false; }
    public function Fuckxss($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
    public function Categories($a, $b) {}
    public function redirect($url) { header('Location: ' . $url); die(); }
    public $session_username = 'Guest';
    public function setError($msg) {}
}
