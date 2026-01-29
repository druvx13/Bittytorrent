<?php

declare(strict_types=1);

namespace Bittytorrent\Service;

use PDO;
use Bittytorrent\Application;

/**
 * BitTorrent Tracker Service
 * 
 * Handles announce and scrape requests from BitTorrent clients
 */
class Tracker
{
    private PDO $db;
    private Application $app;
    
    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->db = $this->app->getDb();
    }
    
    /**
     * Handle announce request
     */
    public function announce(array $params): string
    {
        // Validate required parameters
        $required = ['info_hash', 'peer_id', 'port', 'uploaded', 'downloaded', 'left'];
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                return $this->errorResponse('Missing required parameter: ' . $param);
            }
        }
        
        // Validate info_hash length (20 bytes in binary, 40 chars in hex)
        if (strlen($params['info_hash']) !== 20 && strlen($params['info_hash']) !== 40) {
            return $this->errorResponse('Invalid info_hash');
        }
        
        // Convert binary info_hash to hex
        $infoHash = bin2hex($params['info_hash']);
        
        // Validate peer_id length
        if (strlen($params['peer_id']) !== 20 && strlen($params['peer_id']) !== 40) {
            return $this->errorResponse('Invalid peer_id');
        }
        
        $peerId = bin2hex($params['peer_id']);
        
        // Get client IP
        $ip = $params['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
        
        if (empty($ip)) {
            return $this->errorResponse('Invalid IP address');
        }
        
        // Validate port
        $port = (int)$params['port'];
        if ($port < 1 || $port > 65535) {
            return $this->errorResponse('Invalid port');
        }
        
        $uploaded = (int)$params['uploaded'];
        $downloaded = (int)$params['downloaded'];
        $left = (int)$params['left'];
        $isSeeder = $left === 0;
        
        // Check if torrent exists
        $torrent = $this->findTorrent($infoHash);
        if (!$torrent && !$this->app->getConfig('tracker_open')) {
            return $this->errorResponse('Torrent not found');
        }
        
        // Handle events
        $event = $params['event'] ?? '';
        
        if ($event === 'stopped') {
            $this->removePeer($infoHash, $peerId);
        } elseif ($event === 'completed') {
            $this->updatePeer($infoHash, $peerId, $ip, $port, $uploaded, $downloaded, $left, $isSeeder, $params['user_agent'] ?? '');
            $this->incrementCompleted($infoHash);
        } else {
            $this->updatePeer($infoHash, $peerId, $ip, $port, $uploaded, $downloaded, $left, $isSeeder, $params['user_agent'] ?? '');
        }
        
        // Get peer list
        $numwant = isset($params['numwant']) ? min((int)$params['numwant'], $this->app->getConfig('max_peers')) : $this->app->getConfig('default_peers');
        $peers = $this->getPeers($infoHash, $peerId, $numwant);
        
        // Build response
        $compact = isset($params['compact']) && $params['compact'] == 1;
        $noPeerId = isset($params['no_peer_id']) && $params['no_peer_id'] == 1;
        
        return $this->successResponse($peers, $compact, $noPeerId);
    }
    
    /**
     * Handle scrape request
     */
    public function scrape(array $infoHashes = []): string
    {
        if (empty($infoHashes) && !$this->app->getConfig('full_scrape')) {
            return $this->errorResponse('Full scrape not allowed');
        }
        
        $stats = [];
        
        if (empty($infoHashes)) {
            // Full scrape
            $stmt = $this->db->query("SELECT info_hash FROM torrents");
            $torrents = $stmt->fetchAll();
            
            foreach ($torrents as $torrent) {
                $infoHashes[] = $torrent['info_hash'];
            }
        }
        
        foreach ($infoHashes as $infoHash) {
            $hexHash = is_string($infoHash) && strlen($infoHash) === 20 ? bin2hex($infoHash) : $infoHash;
            $torrentStats = $this->getTorrentStats($hexHash);
            
            if ($torrentStats) {
                // Bencode expects binary info_hash as key
                $binaryHash = strlen($hexHash) === 40 ? hex2bin($hexHash) : $hexHash;
                $stats[$binaryHash] = $torrentStats;
            }
        }
        
        return $this->scrapeResponse($stats);
    }
    
    /**
     * Find torrent by info hash
     */
    private function findTorrent(string $infoHash): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM torrents WHERE info_hash = ? LIMIT 1");
        $stmt->execute([$infoHash]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Update or insert peer
     */
    private function updatePeer(string $infoHash, string $peerId, string $ip, int $port, int $uploaded, int $downloaded, int $left, bool $isSeeder, string $userAgent): void
    {
        // MySQL-compatible INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $this->db->prepare("
            INSERT INTO peers 
            (info_hash, peer_id, ip, port, uploaded, downloaded, remaining, is_seeder, user_agent, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            ip = VALUES(ip),
            port = VALUES(port),
            uploaded = VALUES(uploaded),
            downloaded = VALUES(downloaded),
            remaining = VALUES(remaining),
            is_seeder = VALUES(is_seeder),
            user_agent = VALUES(user_agent),
            updated_at = VALUES(updated_at)
        ");
        
        $stmt->execute([
            $infoHash,
            $peerId,
            $ip,
            $port,
            $uploaded,
            $downloaded,
            $left,
            $isSeeder ? 1 : 0,
            $userAgent,
            time()
        ]);
        
        // Update torrent stats after peer update
        $this->updateTorrentStats($infoHash);
    }
    
    /**
     * Remove peer
     */
    private function removePeer(string $infoHash, string $peerId): void
    {
        $stmt = $this->db->prepare("DELETE FROM peers WHERE info_hash = ? AND peer_id = ?");
        $stmt->execute([$infoHash, $peerId]);
        
        // Update torrent stats after peer removal
        $this->updateTorrentStats($infoHash);
    }
    
    /**
     * Get peers for torrent
     */
    private function getPeers(string $infoHash, string $excludePeerId, int $numwant): array
    {
        $stmt = $this->db->prepare("
            SELECT ip, port, peer_id 
            FROM peers 
            WHERE info_hash = ? AND peer_id != ?
            ORDER BY RANDOM()
            LIMIT ?
        ");
        
        $stmt->execute([$infoHash, $excludePeerId, $numwant]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get torrent statistics
     */
    private function getTorrentStats(string $infoHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as peers,
                SUM(CASE WHEN is_seeder = 1 THEN 1 ELSE 0 END) as complete,
                SUM(CASE WHEN is_seeder = 0 THEN 1 ELSE 0 END) as incomplete,
                COALESCE((SELECT completed FROM torrents WHERE info_hash = ?), 0) as downloaded
            FROM peers
            WHERE info_hash = ?
        ");
        
        $stmt->execute([$infoHash, $infoHash]);
        $stats = $stmt->fetch();
        
        if ($stats && $stats['peers'] > 0) {
            return [
                'complete' => (int)$stats['complete'],
                'incomplete' => (int)$stats['incomplete'],
                'downloaded' => (int)$stats['downloaded']
            ];
        }
        
        return null;
    }
    
    /**
     * Increment completed count
     */
    private function incrementCompleted(string $infoHash): void
    {
        $stmt = $this->db->prepare("UPDATE torrents SET completed = completed + 1 WHERE info_hash = ?");
        $stmt->execute([$infoHash]);
    }
    
    /**
     * Update torrent statistics from peers table
     */
    private function updateTorrentStats(string $infoHash): void
    {
        $stmt = $this->db->prepare("
            UPDATE torrents SET
                seeders = (SELECT COUNT(*) FROM peers WHERE info_hash = ? AND is_seeder = 1),
                leechers = (SELECT COUNT(*) FROM peers WHERE info_hash = ? AND is_seeder = 0),
                last_scrape = ?
            WHERE info_hash = ?
        ");
        
        $now = time();
        $stmt->execute([$infoHash, $infoHash, $now, $infoHash]);
    }
    
    /**
     * Update all torrent statistics
     */
    public function updateAllTorrentStats(): int
    {
        // Get all torrents with peers
        $stmt = $this->db->query("SELECT DISTINCT info_hash FROM peers");
        $infoHashes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        foreach ($infoHashes as $infoHash) {
            $this->updateTorrentStats($infoHash);
            $count++;
        }
        
        // Set seeders/leechers to 0 for torrents with no peers
        $this->db->exec("
            UPDATE torrents SET seeders = 0, leechers = 0 
            WHERE info_hash NOT IN (SELECT DISTINCT info_hash FROM peers)
        ");
        
        return $count;
    }
    
    /**
     * Clean old peers
     */
    public function cleanOldPeers(): int
    {
        $timeout = time() - ($this->app->getConfig('announce_interval') * 2);
        
        // Get affected torrents before deletion
        $stmt = $this->db->prepare("SELECT DISTINCT info_hash FROM peers WHERE updated_at < ?");
        $stmt->execute([$timeout]);
        $affectedTorrents = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Delete old peers
        $stmt = $this->db->prepare("DELETE FROM peers WHERE updated_at < ?");
        $stmt->execute([$timeout]);
        $deletedCount = $stmt->rowCount();
        
        // Update stats for affected torrents
        foreach ($affectedTorrents as $infoHash) {
            $this->updateTorrentStats($infoHash);
        }
        
        return $deletedCount;
    }
    
    /**
     * Generate error response (bencode)
     */
    private function errorResponse(string $message): string
    {
        return $this->bencode(['failure reason' => $message]);
    }
    
    /**
     * Generate success response (bencode)
     */
    private function successResponse(array $peers, bool $compact, bool $noPeerId): string
    {
        $response = [
            'interval' => $this->app->getConfig('announce_interval'),
            'min interval' => $this->app->getConfig('min_interval'),
            'complete' => 0,
            'incomplete' => 0,
        ];
        
        if ($compact) {
            $compactPeers = '';
            foreach ($peers as $peer) {
                // Validate IPv4 address
                $ipLong = ip2long($peer['ip']);
                if ($ipLong === false) {
                    continue; // Skip invalid IPs
                }
                $compactPeers .= pack('Nn', $ipLong, $peer['port']);
            }
            $response['peers'] = $compactPeers;
        } else {
            $peerList = [];
            foreach ($peers as $peer) {
                $peerData = [
                    'ip' => $peer['ip'],
                    'port' => (int)$peer['port'],
                ];
                
                if (!$noPeerId) {
                    // Validate hex string before converting
                    if (ctype_xdigit($peer['peer_id']) && strlen($peer['peer_id']) === 40) {
                        $peerData['peer id'] = hex2bin($peer['peer_id']);
                    }
                }
                
                $peerList[] = $peerData;
            }
            $response['peers'] = $peerList;
        }
        
        return $this->bencode($response);
    }
    
    /**
     * Generate scrape response (bencode)
     */
    private function scrapeResponse(array $stats): string
    {
        return $this->bencode(['files' => $stats]);
    }
    
    /**
     * Bencode encoder
     */
    private function bencode($data): string
    {
        if (is_int($data)) {
            return 'i' . $data . 'e';
        }
        
        if (is_string($data)) {
            return strlen($data) . ':' . $data;
        }
        
        if (is_array($data)) {
            // Check if associative array (dictionary)
            if (array_keys($data) !== range(0, count($data) - 1)) {
                $encoded = 'd';
                ksort($data);
                foreach ($data as $key => $value) {
                    $encoded .= $this->bencode((string)$key) . $this->bencode($value);
                }
                return $encoded . 'e';
            } else {
                // List
                $encoded = 'l';
                foreach ($data as $value) {
                    $encoded .= $this->bencode($value);
                }
                return $encoded . 'e';
            }
        }
        
        throw new \InvalidArgumentException('Cannot bencode unsupported data type: ' . gettype($data));
    }
}
