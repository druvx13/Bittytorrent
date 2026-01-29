<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

/**
 * RSS Controller
 * 
 * Handles RSS feed generation for torrents
 */
class RSSController extends BaseController
{
    /**
     * Generate RSS feed for latest torrents
     */
    public function feed(): void
    {
        $limit = 50;
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        
        // Build query
        $where = '1=1';
        $params = [];
        
        if ($categoryId) {
            $where = 'category_id = ?';
            $params[] = $categoryId;
        }
        
        // Get torrents
        $stmt = $this->app->getDb()->prepare("
            SELECT t.*, u.username, c.name as category_name
            FROM torrents t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE $where
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        $stmt->execute(array_merge($params, [$limit]));
        $torrents = $stmt->fetchAll();
        
        // Get site info
        $siteUrl = $this->getSiteUrl();
        $siteName = $this->app->getConfig('site_name') ?? 'Bittytorrent';
        
        // Set RSS headers
        header('Content-Type: application/rss+xml; charset=UTF-8');
        
        // Generate RSS
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo '<channel>' . "\n";
        echo '  <title>' . htmlspecialchars($siteName) . ' - Latest Torrents</title>' . "\n";
        echo '  <link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        echo '  <description>Latest torrents from ' . htmlspecialchars($siteName) . '</description>' . "\n";
        echo '  <language>en-us</language>' . "\n";
        echo '  <atom:link href="' . htmlspecialchars($siteUrl . '/rss') . '" rel="self" type="application/rss+xml" />' . "\n";
        
        foreach ($torrents as $torrent) {
            $title = htmlspecialchars($torrent['name']);
            $link = htmlspecialchars($siteUrl . '/torrent/' . $torrent['id']);
            $description = htmlspecialchars($torrent['description'] ?? 'No description');
            $pubDate = date('r', strtotime($torrent['created_at']));
            $category = htmlspecialchars($torrent['category_name'] ?? 'Uncategorized');
            $author = htmlspecialchars($torrent['username'] ?? 'Unknown');
            
            echo '  <item>' . "\n";
            echo '    <title>' . $title . '</title>' . "\n";
            echo '    <link>' . $link . '</link>' . "\n";
            echo '    <description>' . $description . '</description>' . "\n";
            echo '    <pubDate>' . $pubDate . '</pubDate>' . "\n";
            echo '    <category>' . $category . '</category>' . "\n";
            echo '    <author>' . $author . '</author>' . "\n";
            echo '    <guid isPermaLink="true">' . $link . '</guid>' . "\n";
            echo '  </item>' . "\n";
        }
        
        echo '</channel>' . "\n";
        echo '</rss>';
        exit;
    }
    
    /**
     * Get site URL
     */
    private function getSiteUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
