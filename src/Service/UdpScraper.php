<?php

declare(strict_types=1);

namespace Bittytorrent\Service;

/**
 * UDP Tracker Scraper
 * 
 * Implements UDP tracker scraping protocol
 * Based on BEP 15: http://www.bittorrent.org/beps/bep_0015.html
 */
class UdpScraper
{
    private int $timeout;
    private const CONNECT_ACTION = 0;
    private const SCRAPE_ACTION = 2;
    private const PROTOCOL_ID = 0x41727101980; // Magic constant for UDP trackers

    public function __construct(int $timeout = 5)
    {
        $this->timeout = $timeout;
    }

    /**
     * Scrape a UDP tracker for given info hashes
     *
     * @param string $url UDP tracker URL (udp://tracker.example.com:port)
     * @param array $infoHashes Array of hex info hashes
     * @return array Results keyed by info hash
     * @throws \Exception On scrape failure
     */
    public function scrape(string $url, array $infoHashes): array
    {
        if (count($infoHashes) > 74) {
            throw new \Exception('Too many info hashes (max 74 for UDP)');
        }

        // Parse URL
        if (!preg_match('%udp://([^:/]*)(?::([0-9]*))?%i', $url, $matches)) {
            throw new \Exception('Invalid UDP tracker URL');
        }

        $host = $matches[1];
        $port = isset($matches[2]) && $matches[2] ? (int)$matches[2] : 80;

        // Open UDP socket
        $socket = @fsockopen("udp://$host", $port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            throw new \Exception("Could not open UDP connection: $errstr ($errno)");
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            // Step 1: Connect
            $connectionId = $this->connect($socket);

            // Step 2: Scrape
            $results = $this->scrapeRequest($socket, $connectionId, $infoHashes);

            fclose($socket);
            return $results;

        } catch (\Exception $e) {
            fclose($socket);
            throw $e;
        }
    }

    /**
     * Perform connection handshake
     *
     * @param resource $socket
     * @return string Connection ID (8 bytes)
     * @throws \Exception
     */
    private function connect($socket): string
    {
        $transactionId = mt_rand(0, 0xFFFFFFFF);

        // Build connect request
        // Protocol ID (8 bytes) + Action (4 bytes) + Transaction ID (4 bytes)
        $packet = pack('N', 0x417) . pack('N', 0x27101980) .  // Protocol ID
                  pack('N', self::CONNECT_ACTION) .           // Action: connect
                  pack('N', $transactionId);                   // Transaction ID

        fwrite($socket, $packet);

        // Read connect response (16 bytes)
        $response = fread($socket, 16);
        if (strlen($response) < 16) {
            throw new \Exception('Invalid connect response from tracker');
        }

        $data = unpack('Naction/Ntransaction_id', $response);
        
        if ($data['action'] !== self::CONNECT_ACTION) {
            throw new \Exception('Invalid action in connect response');
        }
        
        if ($data['transaction_id'] !== $transactionId) {
            throw new \Exception('Transaction ID mismatch in connect response');
        }

        // Extract connection ID (last 8 bytes)
        return substr($response, 8, 8);
    }

    /**
     * Send scrape request and parse response
     *
     * @param resource $socket
     * @param string $connectionId
     * @param array $infoHashes
     * @return array
     * @throws \Exception
     */
    private function scrapeRequest($socket, string $connectionId, array $infoHashes): array
    {
        $transactionId = mt_rand(0, 0xFFFFFFFF);

        // Build info hash binary data
        $hashData = '';
        foreach ($infoHashes as $hash) {
            $binary = hex2bin($hash);
            if ($binary === false || strlen($binary) !== 20) {
                throw new \Exception("Invalid info hash: $hash");
            }
            $hashData .= $binary;
        }

        // Build scrape request
        // Connection ID (8 bytes) + Action (4 bytes) + Transaction ID (4 bytes) + Info hashes (20 bytes each)
        $packet = $connectionId .
                  pack('N', self::SCRAPE_ACTION) .
                  pack('N', $transactionId) .
                  $hashData;

        fwrite($socket, $packet);

        // Read scrape response
        // Header: Action (4 bytes) + Transaction ID (4 bytes)
        // Per hash: Seeders (4 bytes) + Completed (4 bytes) + Leechers (4 bytes)
        $responseSize = 8 + (12 * count($infoHashes));
        $response = fread($socket, $responseSize);

        if (strlen($response) < 8) {
            throw new \Exception('Too short scrape response');
        }

        $header = unpack('Naction/Ntransaction_id', $response);
        
        if ($header['action'] !== self::SCRAPE_ACTION) {
            if ($header['action'] === 3) {
                // Error response
                $errorMsg = substr($response, 8);
                throw new \Exception("Tracker error: $errorMsg");
            }
            throw new \Exception('Invalid action in scrape response');
        }
        
        if ($header['transaction_id'] !== $transactionId) {
            throw new \Exception('Transaction ID mismatch in scrape response');
        }

        // Parse torrent data
        $results = [];
        $offset = 8; // Skip header

        foreach ($infoHashes as $hash) {
            if ($offset + 12 <= strlen($response)) {
                $data = unpack('Nseeders/Ncompleted/Nleechers', substr($response, $offset, 12));
                $results[$hash] = [
                    'seeders' => $data['seeders'],
                    'leechers' => $data['leechers'],
                    'completed' => $data['completed']
                ];
                $offset += 12;
            } else {
                // Not enough data for this hash
                $results[$hash] = false;
            }
        }

        return $results;
    }
}
