<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Model\Torrent;
use Bittytorrent\Service\TorrentParser;

/**
 * Torrent Controller
 * 
 * Handles torrent viewing, uploading, editing
 */
class TorrentController extends BaseController
{
    /**
     * Browse torrents with search, filter, and pagination
     */
    public function browse(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;
        
        $searchQuery = $_GET['q'] ?? '';
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $sort = $_GET['sort'] ?? 'created_at';
        $order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        
        // Validate sort field
        $allowedSort = ['name', 'created_at', 'size', 'seeders', 'leechers', 'completed'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($searchQuery) {
            $where[] = '(t.name LIKE ? OR t.description LIKE ?)';
            $searchTerm = '%' . $searchQuery . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($categoryId) {
            $where[] = 't.category_id = ?';
            $params[] = $categoryId;
        }
        
        $whereSql = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $this->app->getDb()->prepare("
            SELECT COUNT(*) FROM torrents t WHERE $whereSql
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        
        // Get torrents
        $stmt = $this->app->getDb()->prepare("
            SELECT t.*, u.username, c.name as category_name,
                   (SELECT COUNT(*) FROM peers p WHERE p.info_hash = t.info_hash AND p.is_seeder = 1) as seeders,
                   (SELECT COUNT(*) FROM peers p WHERE p.info_hash = t.info_hash AND p.is_seeder = 0) as leechers
            FROM torrents t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE $whereSql
            ORDER BY t.$sort $order
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $torrents = $stmt->fetchAll();
        
        // Format sizes
        foreach ($torrents as &$torrent) {
            $torrent['size_formatted'] = $this->formatBytes($torrent['size']);
        }
        
        // Get all categories
        $catStmt = $this->app->getDb()->query("
            SELECT * FROM categories ORDER BY sort_order, name
        ");
        $categories = $catStmt->fetchAll();
        
        // Get current category if filtering
        $category = null;
        if ($categoryId) {
            $catDetailStmt = $this->app->getDb()->prepare("SELECT * FROM categories WHERE id = ?");
            $catDetailStmt->execute([$categoryId]);
            $category = $catDetailStmt->fetch();
        }
        
        $this->render('torrent/browse.twig', [
            'title' => 'Browse Torrents',
            'torrents' => $torrents,
            'categories' => $categories,
            'category' => $category,
            'search_query' => $searchQuery,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'sort' => $sort,
            'order' => $order,
        ]);
    }
    
    /**
     * Show torrent details
     */
    public function show(string $id): void
    {
        $torrentModel = new Torrent();
        $torrent = $torrentModel->findById((int)$id);
        
        if (!$torrent) {
            http_response_code(404);
            $this->render('error/404.twig', ['title' => '404 Not Found']);
            return;
        }
        
        // Increment view count
        $torrentModel->incrementViews((int)$id);
        
        // Get peers for this torrent
        $stmt = $this->app->getDb()->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN is_seeder = 1 THEN 1 ELSE 0 END) as seeders,
                   SUM(CASE WHEN is_seeder = 0 THEN 1 ELSE 0 END) as leechers
            FROM peers
            WHERE info_hash = ?
        ");
        $stmt->execute([$torrent['info_hash']]);
        $peerStats = $stmt->fetch();
        
        $this->render('torrent/show.twig', [
            'title' => $torrent['title'],
            'torrent' => $torrent,
            'peer_stats' => $peerStats,
        ]);
    }
    
    /**
     * Show upload form
     */
    public function showUpload(): void
    {
        $this->requireAuth();
        
        // Get categories
        $stmt = $this->app->getDb()->query("SELECT * FROM categories ORDER BY position, name");
        $categories = $stmt->fetchAll();
        
        $this->render('torrent/upload.twig', [
            'title' => 'Upload Torrent',
            'categories' => $categories,
        ]);
    }
    
    /**
     * Handle torrent upload
     */
    public function upload(): void
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/upload');
            return;
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Verify CSRF token
        if (!$this->verifyCsrf($csrfToken)) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Invalid security token.',
            ]);
            return;
        }
        
        $title = $this->sanitize($_POST['title'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        
        // Validation
        if (empty($title) || strlen($title) < 3) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Title must be at least 3 characters.',
            ]);
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['torrent_file']) || $_FILES['torrent_file']['error'] !== UPLOAD_ERR_OK) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Please upload a valid .torrent file.',
            ]);
            return;
        }
        
        $file = $_FILES['torrent_file'];
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'torrent') {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Only .torrent files are allowed.',
            ]);
            return;
        }
        
        // Parse torrent file
        $torrentParser = new TorrentParser();
        $parsedData = $torrentParser->parse($file['tmp_name']);
        
        if (!$parsedData) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Invalid torrent file format.',
            ]);
            return;
        }
        
        // Check if torrent already exists
        $torrentModel = new Torrent();
        $existing = $torrentModel->findByInfoHash($parsedData['info_hash']);
        
        if ($existing) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'This torrent already exists.',
            ]);
            return;
        }
        
        // Save torrent file
        $uploadsDir = __DIR__ . '/../../public/uploads';
        if (!is_dir($uploadsDir)) {
            if (!mkdir($uploadsDir, 0755, true)) {
                $this->render('torrent/upload.twig', [
                    'title' => 'Upload Torrent',
                    'error' => 'Failed to create uploads directory.',
                ]);
                return;
            }
        }
        
        $filename = $parsedData['info_hash'] . '.torrent';
        $destination = $uploadsDir . '/' . $filename;
        
        // Validate destination path to prevent directory traversal
        $realUploadsDir = realpath($uploadsDir);
        $realDestination = realpath(dirname($destination)) . '/' . basename($destination);
        
        if ($realUploadsDir === false || strpos($realDestination, $realUploadsDir) !== 0) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Invalid file path.',
            ]);
            return;
        }
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Failed to save torrent file.',
            ]);
            return;
        }
        
        // Create torrent entry
        $user = $this->getCurrentUser();
        $torrentId = $torrentModel->create([
            'user_id' => $user['id'],
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'info_hash' => $parsedData['info_hash'],
            'size_bytes' => $parsedData['size'] ?? 0,
        ]);
        
        if ($torrentId) {
            $this->logger->info('Torrent uploaded', [
                'torrent_id' => $torrentId,
                'user_id' => $user['id'],
                'title' => $title,
            ]);
            
            $this->redirect('/torrent/' . $torrentId);
        } else {
            $this->render('torrent/upload.twig', [
                'title' => 'Upload Torrent',
                'error' => 'Failed to create torrent entry.',
            ]);
        }
    }
    
    /**
     * Download torrent file
     */
    public function download(string $id): void
    {
        $torrentModel = new Torrent();
        $torrent = $torrentModel->findById((int)$id);
        
        if (!$torrent) {
            http_response_code(404);
            echo 'Torrent not found';
            return;
        }
        
        $filename = $torrent['info_hash'] . '.torrent';
        $filepath = __DIR__ . '/../../public/uploads/' . $filename;
        
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo 'Torrent file not found';
            return;
        }
        
        header('Content-Type: application/x-bittorrent');
        // Sanitize filename to prevent header injection
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $torrent['slug']);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '.torrent"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
    }
}
