<?php

namespace Tests\Tracker;

use App\Tracker\PeerTracker;
use App\Database\DB;
use Tests\TestCase;

class PeerTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!isset($_ENV['DB_HOST'])) {
            $this->markTestSkipped('Database configuration not found.');
        }

        // Mock GLOBALS for tracker conf
        $GLOBALS['_SERVER']['tracker'] = [
            'open_tracker' => 'true',
            'announce_interval' => 1800,
            'min_interval' => 300,
            'default_peers' => 50,
            'max_peers' => 100,
            'external_ip' => 'false',
            'force_compact' => 'false',
            'full_scrape' => 'false',
            'random_limit' => 50,
            'clean_idle_peers' => 1,
            'db_prefix' => '',
            'seeding' => 0
        ];

        $db = DB::getInstance();
        $db->query("CREATE TABLE IF NOT EXISTS peers (
            info_hash varchar(20) NOT NULL,
            peer_id varchar(20) NOT NULL,
            compact varchar(6) NOT NULL,
            ip varchar(45) NOT NULL,
            port smallint(5) unsigned NOT NULL,
            state tinyint(1) unsigned NOT NULL default '0',
            updated int(10) unsigned NOT NULL,
            PRIMARY KEY  (info_hash,peer_id),
            KEY updated (updated)
        )");

        // Mock GET
        $_GET['info_hash'] = str_repeat('1', 20);
        $_GET['peer_id'] = str_repeat('2', 20);
        $_GET['port'] = 6881;
        $_GET['ip'] = '127.0.0.1';
        $_GET['left'] = 100;
        $_GET['compact'] = 0;
        $_GET['no_peer_id'] = 0;
        $_GET['numwant'] = 50;
    }

    public function testNewPeerAndPeers()
    {
        ob_start();
        PeerTracker::new_peer();
        PeerTracker::peers();
        $output = ob_get_clean();

        $this->assertStringContainsString('d8:intervali1800e', $output);
        $this->assertStringContainsString('7:peer id20:', $output);
    }
}
