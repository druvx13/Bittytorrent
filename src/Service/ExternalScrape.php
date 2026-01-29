<?php

declare(strict_types=1);

namespace Bittytorrent\Service;

use PDO;
use Psr\Log\LoggerInterface;

/**
 * External Scrape Service
 * Scrapes statistics from external BitTorrent trackers
 */
class ExternalScrape
{
    private PDO $db;
    private LoggerInterface $logger;
    private int $timeout;

    public function __construct(PDO $db, LoggerInterface $logger, int $timeout = 10)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    /**
     * Scrape a single external tracker for given info hashes
     *
     * @param string $scrapeUrl Tracker scrape URL
     * @param array $infoHashes Array of info hashes to scrape
     * @return array Results keyed by info hash
     */
    public function scrapeTracker(string $scrapeUrl, array $infoHashes): array
    {
        $results = [];

        if (empty($infoHashes)) {
            return $results;
        }

        try {
            // Build scrape URL with info_hash parameters
            $url = $scrapeUrl;
            $separator = str_contains($url, '?') ? '&' : '?';
            
            foreach ($infoHashes as $infoHash) {
                // Convert hex info hash to raw binary for URL encoding
                $binaryHash = hex2bin($infoHash);
                if ($binaryHash === false) {
                    $this->logger->warning("Invalid info hash: $infoHash");
                    continue;
                }
                $url .= $separator . 'info_hash=' . urlencode($binaryHash);
                $separator = '&';
            }

            // Make HTTP request with timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->timeout,
                    'user_agent' => 'Bittytorrent/1.0'
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                $this->logger->error("Failed to scrape tracker: $scrapeUrl");
                return $results;
            }

            // Parse bencode response
            $data = $this->parseBencodeResponse($response);
            
            if (isset($data['files']) && is_array($data['files'])) {
                // Standard scrape response format
                foreach ($data['files'] as $hash => $stats) {
                    $hexHash = bin2hex($hash);
                    $results[$hexHash] = [
                        'seeders' => (int)($stats['complete'] ?? 0),
                        'leechers' => (int)($stats['incomplete'] ?? 0),
                        'completed' => (int)($stats['downloaded'] ?? 0)
                    ];
                }
            }

            $this->logger->info("Successfully scraped tracker: $scrapeUrl", [
                'torrents' => count($results)
            ]);

        } catch (\Exception $e) {
            $this->logger->error("Error scraping tracker: " . $e->getMessage(), [
                'url' => $scrapeUrl
            ]);
        }

        return $results;
    }

    /**
     * Scrape all enabled external trackers for a specific torrent
     *
     * @param int $torrentId Torrent ID
     * @param string $infoHash Torrent info hash
     * @return bool Success status
     */
    public function scrapeTorrent(int $torrentId, string $infoHash): bool
    {
        // Get all enabled external trackers for this torrent
        $stmt = $this->db->prepare("
            SELECT et.id, et.scrape_url, tet.id as relation_id
            FROM external_trackers et
            LEFT JOIN torrent_external_trackers tet 
                ON et.id = tet.external_tracker_id AND tet.torrent_id = ?
            WHERE et.enabled = 1
        ");
        $stmt->execute([$torrentId]);
        $trackers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($trackers)) {
            return false;
        }

        $totalSuccess = 0;
        $now = time();

        foreach ($trackers as $tracker) {
            // Scrape this tracker
            $results = $this->scrapeTracker($tracker['scrape_url'], [$infoHash]);

            // Update tracker stats
            $stmt = $this->db->prepare("
                UPDATE external_trackers 
                SET last_scrape = ?,
                    scrape_count = scrape_count + 1,
                    success_count = success_count + ?,
                    fail_count = fail_count + ?,
                    updated_at = ?
                WHERE id = ?
            ");
            
            $success = isset($results[$infoHash]) ? 1 : 0;
            $failure = $success ? 0 : 1;
            $stmt->execute([$now, $success, $failure, $now, $tracker['id']]);

            if (isset($results[$infoHash])) {
                $stats = $results[$infoHash];
                
                // Update or insert torrent-tracker relationship
                if ($tracker['relation_id']) {
                    // Update existing
                    $stmt = $this->db->prepare("
                        UPDATE torrent_external_trackers 
                        SET external_seeders = ?,
                            external_leechers = ?,
                            external_completed = ?,
                            last_scrape = ?,
                            last_update = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $stats['seeders'],
                        $stats['leechers'],
                        $stats['completed'],
                        $now,
                        $now,
                        $tracker['relation_id']
                    ]);
                } else {
                    // Insert new
                    $stmt = $this->db->prepare("
                        INSERT INTO torrent_external_trackers 
                        (torrent_id, external_tracker_id, external_seeders, external_leechers, 
                         external_completed, last_scrape, last_update)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $torrentId,
                        $tracker['id'],
                        $stats['seeders'],
                        $stats['leechers'],
                        $stats['completed'],
                        $now,
                        $now
                    ]);
                }
                
                $totalSuccess++;
            }
        }

        // Update torrent's aggregate stats
        $this->updateTorrentAggregateStats($torrentId);

        return $totalSuccess > 0;
    }

    /**
     * Scrape all torrents from all enabled external trackers
     *
     * @return array Stats about the scrape operation
     */
    public function scrapeAllTorrents(): array
    {
        $stats = [
            'torrents_scraped' => 0,
            'trackers_queried' => 0,
            'successes' => 0,
            'failures' => 0
        ];

        // Get all torrents
        $stmt = $this->db->query("
            SELECT id, info_hash 
            FROM torrents 
            ORDER BY last_scrape ASC 
            LIMIT 100
        ");
        $torrents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($torrents as $torrent) {
            $success = $this->scrapeTorrent((int)$torrent['id'], $torrent['info_hash']);
            $stats['torrents_scraped']++;
            if ($success) {
                $stats['successes']++;
            }
        }

        return $stats;
    }

    /**
     * Update torrent's aggregate stats from external trackers
     *
     * @param int $torrentId Torrent ID
     */
    private function updateTorrentAggregateStats(int $torrentId): void
    {
        // Get sum of external stats
        $stmt = $this->db->prepare("
            SELECT 
                SUM(external_seeders) as total_external_seeders,
                SUM(external_leechers) as total_external_leechers,
                SUM(external_completed) as total_external_completed
            FROM torrent_external_trackers
            WHERE torrent_id = ?
        ");
        $stmt->execute([$torrentId]);
        $externalStats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($externalStats) {
            // For now, just update last_scrape time
            // In production, you might want to show external stats separately
            $stmt = $this->db->prepare("
                UPDATE torrents 
                SET last_scrape = ?
                WHERE id = ?
            ");
            $stmt->execute([time(), $torrentId]);
        }
    }

    /**
     * Parse bencode response from tracker
     *
     * @param string $data Bencode encoded data
     * @return array Decoded data
     */
    private function parseBencodeResponse(string $data): array
    {
        try {
            return $this->bdecode($data);
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse bencode response: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Decode bencode data (simplified implementation)
     *
     * @param string $data Bencode data
     * @param int $pos Current position (reference)
     * @return mixed Decoded value
     */
    private function bdecode(string $data, int &$pos = 0): mixed
    {
        if ($pos >= strlen($data)) {
            throw new \Exception("Unexpected end of data");
        }

        $char = $data[$pos];

        // Dictionary
        if ($char === 'd') {
            $dict = [];
            $pos++;
            while ($pos < strlen($data) && $data[$pos] !== 'e') {
                $key = $this->bdecode($data, $pos);
                $value = $this->bdecode($data, $pos);
                $dict[$key] = $value;
            }
            $pos++; // skip 'e'
            return $dict;
        }

        // List
        if ($char === 'l') {
            $list = [];
            $pos++;
            while ($pos < strlen($data) && $data[$pos] !== 'e') {
                $list[] = $this->bdecode($data, $pos);
            }
            $pos++; // skip 'e'
            return $list;
        }

        // Integer
        if ($char === 'i') {
            $pos++;
            $end = strpos($data, 'e', $pos);
            if ($end === false) {
                throw new \Exception("Invalid integer");
            }
            $value = (int)substr($data, $pos, $end - $pos);
            $pos = $end + 1;
            return $value;
        }

        // String
        if (is_numeric($char)) {
            $colon = strpos($data, ':', $pos);
            if ($colon === false) {
                throw new \Exception("Invalid string");
            }
            $length = (int)substr($data, $pos, $colon - $pos);
            $pos = $colon + 1;
            $value = substr($data, $pos, $length);
            $pos += $length;
            return $value;
        }

        throw new \Exception("Invalid bencode data at position $pos");
    }
}
