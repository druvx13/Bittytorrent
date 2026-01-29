<?php

declare(strict_types=1);

namespace Bittytorrent\Service;

/**
 * Torrent File Parser
 * 
 * Parses .torrent files and extracts metadata
 */
class TorrentParser
{
    /**
     * Parse torrent file
     */
    public function parse(string $filepath): ?array
    {
        if (!file_exists($filepath)) {
            return null;
        }
        
        $content = file_get_contents($filepath);
        if ($content === false) {
            return null;
        }
        
        try {
            $decoded = $this->bdecode($content);
            
            if (!is_array($decoded) || !isset($decoded['info'])) {
                return null;
            }
            
            // Calculate info hash
            $infoHash = sha1($this->bencode($decoded['info']));
            
            // Extract metadata
            $info = $decoded['info'];
            $size = 0;
            
            if (isset($info['files'])) {
                // Multi-file torrent
                foreach ($info['files'] as $file) {
                    $size += $file['length'] ?? 0;
                }
            } elseif (isset($info['length'])) {
                // Single file torrent
                $size = $info['length'];
            }
            
            return [
                'info_hash' => $infoHash,
                'name' => $info['name'] ?? 'Unknown',
                'size' => $size,
                'announce' => $decoded['announce'] ?? null,
                'announce_list' => $decoded['announce-list'] ?? [],
                'created_by' => $decoded['created by'] ?? null,
                'creation_date' => $decoded['creation date'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extract all tracker URLs from parsed torrent data
     *
     * @param array $parsedData Torrent metadata from parse()
     * @return array List of unique tracker URLs
     */
    public function extractTrackerUrls(array $parsedData): array
    {
        $trackers = [];

        // Add main announce URL
        if (!empty($parsedData['announce'])) {
            $trackers[] = $parsedData['announce'];
        }

        // Add announce-list URLs
        if (!empty($parsedData['announce_list']) && is_array($parsedData['announce_list'])) {
            foreach ($parsedData['announce_list'] as $tier) {
                if (is_array($tier)) {
                    foreach ($tier as $url) {
                        if (is_string($url) && !empty($url)) {
                            $trackers[] = $url;
                        }
                    }
                } elseif (is_string($tier) && !empty($tier)) {
                    $trackers[] = $tier;
                }
            }
        }

        // Remove duplicates and filter
        $trackers = array_unique($trackers);
        
        // Filter out our own tracker
        $trackers = array_filter($trackers, function($url) {
            // Don't scrape ourselves
            return !str_contains($url, $_SERVER['HTTP_HOST'] ?? '');
        });

        return array_values($trackers);
    }

    /**
     * Convert tracker announce URL to scrape URL
     *
     * @param string $announceUrl Announce URL
     * @return string|null Scrape URL or null if can't convert
     */
    public function convertToScrapeUrl(string $announceUrl): ?string
    {
        // HTTP/HTTPS trackers
        if (preg_match('%^(https?://.*?/)announce([^/]*)$%i', $announceUrl, $m)) {
            return $m[1] . 'scrape' . $m[2];
        }

        // UDP trackers - just return as-is (no /announce suffix usually)
        if (str_starts_with($announceUrl, 'udp://')) {
            // Remove /announce if present
            return preg_replace('%/announce$%i', '', $announceUrl);
        }

        return null;
    }
    
    /**
     * Decode bencoded data
     */
    private function bdecode(string $data, int &$pos = 0): mixed
    {
        if ($pos >= strlen($data)) {
            throw new \Exception('Unexpected end of data');
        }
        
        $char = $data[$pos];
        
        // Integer
        if ($char === 'i') {
            $pos++;
            $end = strpos($data, 'e', $pos);
            if ($end === false) {
                throw new \Exception('Invalid integer encoding');
            }
            $value = (int)substr($data, $pos, $end - $pos);
            $pos = $end + 1;
            return $value;
        }
        
        // List
        if ($char === 'l') {
            $pos++;
            $list = [];
            while ($data[$pos] !== 'e') {
                $list[] = $this->bdecode($data, $pos);
            }
            $pos++;
            return $list;
        }
        
        // Dictionary
        if ($char === 'd') {
            $pos++;
            $dict = [];
            while ($data[$pos] !== 'e') {
                $key = $this->bdecode($data, $pos);
                $value = $this->bdecode($data, $pos);
                $dict[$key] = $value;
            }
            $pos++;
            return $dict;
        }
        
        // String
        if (is_numeric($char)) {
            $colon = strpos($data, ':', $pos);
            if ($colon === false) {
                throw new \Exception('Invalid string encoding');
            }
            $length = (int)substr($data, $pos, $colon - $pos);
            $pos = $colon + 1;
            $value = substr($data, $pos, $length);
            $pos += $length;
            return $value;
        }
        
        throw new \Exception('Invalid bencoded data');
    }
    
    /**
     * Encode data to bencode format
     */
    private function bencode(mixed $data): string
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
        
        throw new \Exception('Cannot encode data type');
    }
}
