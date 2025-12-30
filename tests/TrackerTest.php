<?php
use PHPUnit\Framework\TestCase;
use BittyTorrent\Tracker\Tracker;
use BittyTorrent\Database\DB;

class TrackerTest extends TestCase {
    private $db;
    private $tracker;
    private $config;

    protected function setUp(): void {
        global $dbhost, $dbuser, $dbpass, $dbname;
        require 'libs/db.php';
        $this->db = new DB($dbhost, $dbuser, $dbpass, $dbname);

        // Clean peers table
        $this->db->query("TRUNCATE TABLE peers");

        $this->config = [
            'announce_interval' => 1800,
            'min_interval' => 900,
            'default_peers' => 50,
            'max_peers' => 200,
            'full_scrape' => true,
        ];

        $this->tracker = new Tracker($this->db, $this->config);
    }

    public function testAnnounceStarts() {
        // Mock GET params
        $_GET['info_hash'] = str_repeat('a', 20); // 20 bytes raw
        $_GET['peer_id'] = '-BT1234-123456789012';
        $_GET['port'] = 6881;
        $_GET['left'] = 100;
        $_GET['compact'] = 1;

        ob_start();
        $this->tracker->handleRequest();
        $output = ob_get_clean();

        $this->assertStringContainsString('d8:intervali1800e', $output);

        // Check DB
        $hex = bin2hex($_GET['info_hash']);
        $peer = $this->db->get_row("SELECT * FROM peers WHERE info_hash = UNHEX('$hex')");
        $this->assertNotNull($peer);
        $this->assertEquals(6881, $peer->port);
    }

    public function testScrape() {
        // Seed first
        $this->db->query("INSERT INTO peers (info_hash, peer_id, compact, ip, port, state, updated) VALUES (UNHEX('6161616161616161616161616161616161616161'), '-BT1234-123456789012', '', '127.0.0.1', 6881, 1, 1234567890)");

        $_GET = [];
        $_GET['info_hash'] = str_repeat('a', 20);
        $_SERVER['REQUEST_URI'] = '/scrape.php';

        ob_start();
        $this->tracker->handleRequest();
        $output = ob_get_clean();

        $this->assertStringContainsString('d5:filesd20:aaaaaaaaaaaaaaaaaaaad8:completei1e10:downloadedi0e10:incompletei0eeee', $output);
    }
}
