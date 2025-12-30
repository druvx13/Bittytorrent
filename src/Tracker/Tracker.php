<?php
namespace BittyTorrent\Tracker;

use BittyTorrent\Database\DB;

class Tracker {
    private $db;
    private $config;

    public function __construct(DB $db, array $config) {
        $this->db = $db;
        $this->config = $config;
    }

    public function handleRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, 'scrape') !== false) {
             $this->scrape();
             return;
        }

        if (isset($_GET['stats'])) {
            $this->stats();
            return;
        }

        $this->announce();
    }

    private function error($msg) {
        exit('d14:failure reason' . strlen($msg) . ":{$msg}e");
    }

    private function announce() {
        if (!isset($_GET['info_hash'])) $this->error('Missing info_hash');
        if (!isset($_GET['peer_id'])) $this->error('Missing peer_id');
        if (!isset($_GET['port'])) $this->error('Missing port');

        $info_hash = $_GET['info_hash'];
        if (strlen($info_hash) != 20) {
            $info_hash = urldecode($info_hash);
             if (strlen($info_hash) != 20) {
                 if (strlen($_GET['info_hash']) == 40) {
                     $info_hash = hex2bin($_GET['info_hash']);
                 }
             }
        }

        if (strlen($info_hash) != 20) $this->error('Invalid info_hash');
        $hex_info_hash = bin2hex($info_hash);

        $peer_id = $_GET['peer_id'];
        $port = (int)$_GET['port'];
        $ip = $_GET['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        if ($ip == '::1') $ip = '127.0.0.1';
        $event = $_GET['event'] ?? '';
        $uploaded = $_GET['uploaded'] ?? 0;
        $downloaded = $_GET['downloaded'] ?? 0;
        $left = $_GET['left'] ?? 0;

        $compact = isset($_GET['compact']) && $_GET['compact'];
        $numwant = $_GET['numwant'] ?? $this->config['default_peers'];
        if ($numwant > $this->config['max_peers']) $numwant = $this->config['max_peers'];

        $seeding = ($left == 0) ? 1 : 0;
        if ($event === 'completed') $seeding = 1;

        // Check if peer exists
        $peer = $this->db->get_row("SELECT * FROM peers WHERE info_hash = UNHEX('$hex_info_hash') AND peer_id = '$peer_id'", 'ARRAY_A');

        if ($event === 'stopped') {
            if ($peer) {
                $this->db->query("DELETE FROM peers WHERE info_hash = UNHEX(?) AND peer_id = ?", [$hex_info_hash, $peer_id]);
            }
        } else {
            $compact_field = pack('Nn', ip2long($ip), $port);

            if ($peer) {
                $this->db->query("UPDATE peers SET ip = ?, port = ?, state = ?, updated = ?, compact = ? WHERE info_hash = UNHEX(?) AND peer_id = ?",
                    [$ip, $port, $seeding, time(), $compact_field, $hex_info_hash, $peer_id]);
            } else {
                $this->db->query("INSERT INTO peers (info_hash, peer_id, compact, ip, port, state, updated) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?)",
                    [$hex_info_hash, $peer_id, $compact_field, $ip, $port, $seeding, time()]);
            }
        }

        // DEBUGGING
        $db_peer = $this->db->get_row("SELECT * FROM peers WHERE info_hash = UNHEX('$hex_info_hash') AND peer_id = '$peer_id'");
        if (!$db_peer) {
            error_log("Failed to insert peer: Info Hash: $hex_info_hash, Peer ID: $peer_id, IP: $ip, Port: $port");
        }

        $limit = (int)$numwant;
        $peers = $this->db->get_results("SELECT ip, port, peer_id FROM peers WHERE info_hash = UNHEX('$hex_info_hash') AND peer_id != '$peer_id' ORDER BY RAND() LIMIT $limit");

        $response = 'd8:intervali' . $this->config['announce_interval'] . 'e12:min intervali' . $this->config['min_interval'] . 'e5:peers';

        if ($compact) {
            $peersStr = '';
            foreach ($peers as $p) {
                $peersStr .= pack('Nn', ip2long($p->ip), $p->port);
            }
            $response .= strlen($peersStr) . ':' . $peersStr;
        } else {
            $response .= 'l';
            foreach ($peers as $p) {
                $response .= 'd2:ip' . strlen($p->ip) . ":{$p->ip}7:peer id20:{$p->peer_id}4:porti{$p->port}ee";
            }
            $response .= 'e';
        }
        $response .= 'e';

        if (headers_sent()) {
        } else {
            header('Content-Type: text/plain');
        }
        echo $response;
    }

    private function scrape() {
        $info_hashes = [];
        if (isset($_GET['info_hash'])) {
            $raw_hash = $_GET['info_hash'];
            if (strlen($raw_hash) == 20) {
                 $info_hashes[] = bin2hex($raw_hash);
            } elseif (strlen($raw_hash) == 40) {
                 $info_hashes[] = $raw_hash;
            } else {
                $raw_hash = urldecode($raw_hash);
                 if (strlen($raw_hash) == 20) {
                     $info_hashes[] = bin2hex($raw_hash);
                 }
            }
        }

        $response = 'd5:filesd';

        if (empty($info_hashes)) {
            if (isset($this->config['full_scrape']) && $this->config['full_scrape']) {
                 $rows = $this->db->get_results("SELECT info_hash, SUM(state=1) as complete, SUM(state=0) as incomplete FROM peers GROUP BY info_hash");
                 foreach ($rows as $row) {
                     $hex = bin2hex($row->info_hash);
                     $response .= "20:{$row->info_hash}d8:completei{$row->complete}e10:downloadedi0e10:incompletei{$row->incomplete}ee";
                 }
            }
        } else {
            foreach ($info_hashes as $hex) {
                 $row = $this->db->get_row("SELECT SUM(state=1) as complete, SUM(state=0) as incomplete FROM peers WHERE info_hash = UNHEX('$hex')");
                 if ($row) {
                     $binHash = hex2bin($hex);
                     $complete = (int)$row->complete;
                     $incomplete = (int)$row->incomplete;
                     $response .= "20:{$binHash}d8:completei{$complete}e10:downloadedi0e10:incompletei{$incomplete}ee";
                 }
            }
        }

        $response .= 'ee';
        header('Content-Type: text/plain');
        echo $response;
    }

    private function stats() {
    }
}
