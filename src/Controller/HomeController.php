<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Model\Torrent;

/**
 * Home Controller
 * 
 * Handles homepage and torrent listing
 */
class HomeController extends BaseController
{
    /**
     * Show homepage with recent torrents
     */
    public function index(): void
    {
        $torrentModel = new Torrent();
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = isset($_GET['search']) ? $this->sanitize($_GET['search']) : '';
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        
        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        $perPage = 25;
        $torrents = $torrentModel->getAll($filters, $page, $perPage);
        $totalTorrents = $torrentModel->count($filters);
        $totalPages = ceil($totalTorrents / $perPage);
        
        // Get categories for filter
        $stmt = $this->app->getDb()->query("SELECT * FROM categories ORDER BY position, name");
        $categories = $stmt->fetchAll();
        
        $this->render('home/index.twig', [
            'title' => 'Home',
            'torrents' => $torrents,
            'categories' => $categories,
            'page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'selected_category' => $categoryId,
        ]);
    }
}
