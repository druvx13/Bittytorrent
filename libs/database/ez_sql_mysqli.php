<?php
// Adapter for legacy ezSQL to new BittyTorrent\Database\DB

class ezSQL_mysqli extends BittyTorrent\Database\DB {
    public function __construct($dbuser='', $dbpassword='', $dbname='', $dbhost='localhost') {
        parent::__construct($dbhost, $dbuser, $dbpassword, $dbname);
    }
}

