<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Service\Tracker;

/**
 * Tracker Controller
 * 
 * Handles BitTorrent tracker announce and scrape requests
 */
class TrackerController extends BaseController
{
    /**
     * Handle announce request
     */
    public function announce(): void
    {
        header('Content-Type: text/plain');
        
        $tracker = new Tracker();
        
        // Get parameters from GET request
        $params = [
            'info_hash' => $_GET['info_hash'] ?? '',
            'peer_id' => $_GET['peer_id'] ?? '',
            'port' => $_GET['port'] ?? 0,
            'uploaded' => $_GET['uploaded'] ?? 0,
            'downloaded' => $_GET['downloaded'] ?? 0,
            'left' => $_GET['left'] ?? 0,
            'compact' => $_GET['compact'] ?? 0,
            'no_peer_id' => $_GET['no_peer_id'] ?? 0,
            'event' => $_GET['event'] ?? '',
            'ip' => $_GET['ip'] ?? null,
            'numwant' => $_GET['numwant'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        
        echo $tracker->announce($params);
    }
    
    /**
     * Handle scrape request
     */
    public function scrape(): void
    {
        header('Content-Type: text/plain');
        
        $tracker = new Tracker();
        
        // Get info_hash parameter(s)
        $infoHashes = [];
        
        if (isset($_GET['info_hash'])) {
            if (is_array($_GET['info_hash'])) {
                $infoHashes = $_GET['info_hash'];
            } else {
                $infoHashes[] = $_GET['info_hash'];
            }
        }
        
        echo $tracker->scrape($infoHashes);
    }
}
