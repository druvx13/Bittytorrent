<?php
use PHPUnit\Framework\TestCase;
use BittyTorrent\Database\DB;

class DBTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        global $dbhost, $dbuser, $dbpass, $dbname;
        require 'libs/db.php';
        $this->db = new DB($dbhost, $dbuser, $dbpass, $dbname);
    }

    public function testConnection()
    {
        $this->assertInstanceOf(DB::class, $this->db);
    }

    public function testGetVar()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS test (id INT AUTO_INCREMENT PRIMARY KEY, val VARCHAR(255))");
        $this->db->query("TRUNCATE TABLE test");
        $this->db->query("INSERT INTO test (val) VALUES ('hello')");
        $val = $this->db->get_var("SELECT val FROM test WHERE id = 1");
        $this->assertEquals('hello', $val);
    }
}
