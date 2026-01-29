<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Model\User;

/**
 * User Controller
 * 
 * Handles public user profile viewing
 */
class UserController extends BaseController
{
    /**
     * Show user profile
     */
    public function show(string $id): void
    {
        $userModel = new User();
        $user = $userModel->findById((int)$id);
        
        if (!$user) {
            http_response_code(404);
            $this->render('error/404.twig', ['title' => '404 Not Found']);
            return;
        }
        
        // Get user's torrents
        $stmt = $this->app->getDb()->prepare("
            SELECT t.*, 
                   (SELECT COUNT(*) FROM peers p WHERE p.info_hash = t.info_hash AND p.is_seeder = 1) as seeders,
                   (SELECT COUNT(*) FROM peers p WHERE p.info_hash = t.info_hash AND p.is_seeder = 0) as leechers
            FROM torrents t
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$id]);
        $torrents = $stmt->fetchAll();
        
        // Format sizes
        foreach ($torrents as &$torrent) {
            $torrent['size_formatted'] = $this->formatBytes((int)$torrent['size_bytes']);
        }
        
        // Get torrent count
        $countStmt = $this->app->getDb()->prepare("SELECT COUNT(*) FROM torrents WHERE user_id = ?");
        $countStmt->execute([$id]);
        $torrentCount = (int)$countStmt->fetchColumn();
        
        // Calculate ratio
        $uploaded = (int)$user['uploaded'];
        $downloaded = (int)$user['downloaded'];
        
        if ($downloaded == 0) {
            $ratio = $uploaded > 0 ? '∞' : '0.00';
        } else {
            $ratio = number_format($uploaded / $downloaded, 2);
        }
        
        $this->render('user/show.twig', [
            'title' => $user['username'] . ' - User Profile',
            'user' => $user,
            'torrents' => $torrents,
            'torrent_count' => $torrentCount,
            'uploaded_formatted' => $this->formatBytes($uploaded),
            'downloaded_formatted' => $this->formatBytes($downloaded),
            'ratio' => $ratio,
        ]);
    }
}
