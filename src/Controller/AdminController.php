<?php

declare(strict_types=1);

namespace Bittytorrent\Controller;

use Bittytorrent\Model\User;
use Bittytorrent\Model\Torrent;

/**
 * Admin Controller
 * 
 * Handles admin panel and administrative functions
 */
class AdminController extends BaseController
{
    /**
     * Show admin dashboard
     */
    public function dashboard(): void
    {
        $this->requireAdmin();
        
        // Get statistics
        $userModel = new User();
        $torrentModel = new Torrent();
        
        $stats = [
            'total_users' => $userModel->count(),
            'total_torrents' => $torrentModel->count(),
            'recent_users' => $userModel->getAll(1, 5),
            'recent_torrents' => $torrentModel->getAll([], 1, 5),
        ];
        
        $this->render('admin/dashboard.twig', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
        ]);
    }
    
    /**
     * List all users
     */
    public function users(): void
    {
        $this->requireAdmin();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 25;
        
        $userModel = new User();
        $users = $userModel->getAll($page, $perPage);
        $totalUsers = $userModel->count();
        $totalPages = (int)ceil($totalUsers / $perPage);
        
        $this->render('admin/users.twig', [
            'title' => 'Manage Users',
            'users' => $users,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
        ]);
    }
    
    /**
     * List all torrents
     */
    public function torrents(): void
    {
        $this->requireAdmin();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 25;
        
        $torrentModel = new Torrent();
        $torrents = $torrentModel->getAll([], $page, $perPage);
        $totalTorrents = $torrentModel->count();
        $totalPages = (int)ceil($totalTorrents / $perPage);
        
        $this->render('admin/torrents.twig', [
            'title' => 'Manage Torrents',
            'torrents' => $torrents,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_torrents' => $totalTorrents,
        ]);
    }
    
    /**
     * Delete user
     */
    public function deleteUser(): void
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/users');
            return;
        }
        
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $csrf = $_POST['csrf_token'] ?? '';
        
        if (!$this->verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/users');
            return;
        }
        
        // Prevent deleting yourself
        if ($userId === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'Cannot delete your own account';
            $this->redirect('/admin/users');
            return;
        }
        
        $userModel = new User();
        if ($userModel->delete($userId)) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user';
        }
        
        $this->redirect('/admin/users');
    }
    
    /**
     * Delete torrent
     */
    public function deleteTorrent(): void
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/torrents');
            return;
        }
        
        $torrentId = isset($_POST['torrent_id']) ? (int)$_POST['torrent_id'] : 0;
        $csrf = $_POST['csrf_token'] ?? '';
        
        if (!$this->verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            $this->redirect('/admin/torrents');
            return;
        }
        
        $torrentModel = new Torrent();
        if ($torrentModel->delete($torrentId)) {
            $_SESSION['success'] = 'Torrent deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete torrent';
        }
        
        $this->redirect('/admin/torrents');
    }
    
    /**
     * Show settings page
     */
    public function settings(): void
    {
        $this->requireAdmin();
        
        $this->render('admin/settings.twig', [
            'title' => 'Settings',
        ]);
    }
    
    /**
     * Manage categories
     */
    public function categories(): void
    {
        $this->requireAdmin();
        
        $editId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
        $editCategory = null;
        
        if ($editId) {
            $stmt = $this->app->getDb()->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$editId]);
            $editCategory = $stmt->fetch();
        }
        
        // Get all categories with torrent counts
        $stmt = $this->app->getDb()->query("
            SELECT c.*, COUNT(t.id) as torrent_count
            FROM categories c
            LEFT JOIN torrents t ON c.id = t.category_id
            GROUP BY c.id
            ORDER BY c.position, c.name
        ");
        $categories = $stmt->fetchAll();
        
        $this->render('admin/categories.twig', [
            'title' => 'Manage Categories',
            'categories' => $categories,
            'edit_category' => $editCategory,
            'success' => $_SESSION['success_msg'] ?? null,
            'error' => $_SESSION['error_msg'] ?? null,
        ]);
        
        unset($_SESSION['success_msg'], $_SESSION['error_msg']);
    }
    
    /**
     * Add new category
     */
    public function addCategory(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $this->validateCSRFToken();
        
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        
        if (empty($name)) {
            $_SESSION['error_msg'] = 'Category name is required';
            header('Location: /admin/categories');
            exit;
        }
        
        $stmt = $this->app->getDb()->prepare("
            INSERT INTO categories (name, description, position)
            VALUES (?, ?, ?)
        ");
        
        try {
            $stmt->execute([$name, $description, $position]);
            $_SESSION['success_msg'] = 'Category added successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error adding category: ' . $e->getMessage();
        }
        
        header('Location: /admin/categories');
        exit;
    }
    
    /**
     * Edit category
     */
    public function editCategory(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $this->validateCSRFToken();
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $position = (int)($_POST['position'] ?? 0);
        
        if (empty($name) || $id <= 0) {
            $_SESSION['error_msg'] = 'Invalid input';
            header('Location: /admin/categories');
            exit;
        }
        
        $stmt = $this->app->getDb()->prepare("
            UPDATE categories SET name = ?, description = ?, position = ? WHERE id = ?
        ");
        
        try {
            $stmt->execute([$name, $description, $position, $id]);
            $_SESSION['success_msg'] = 'Category updated successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error updating category: ' . $e->getMessage();
        }
        
        header('Location: /admin/categories');
        exit;
    }
    
    /**
     * Delete category
     */
    public function deleteCategory(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $this->validateCSRFToken();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $_SESSION['error_msg'] = 'Invalid category ID';
            header('Location: /admin/categories');
            exit;
        }
        
        // Check if category has torrents
        $checkStmt = $this->app->getDb()->prepare("SELECT COUNT(*) FROM torrents WHERE category_id = ?");
        $checkStmt->execute([$id]);
        $torrentCount = (int)$checkStmt->fetchColumn();
        
        if ($torrentCount > 0) {
            $_SESSION['error_msg'] = 'Cannot delete category with existing torrents';
            header('Location: /admin/categories');
            exit;
        }
        
        $stmt = $this->app->getDb()->prepare("DELETE FROM categories WHERE id = ?");
        
        try {
            $stmt->execute([$id]);
            $_SESSION['success_msg'] = 'Category deleted successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error deleting category: ' . $e->getMessage();
        }
        
        header('Location: /admin/categories');
        exit;
    }

    /**
     * List external trackers
     */
    public function externalTrackers(): void
    {
        $this->requireAdmin();

        // Get all external trackers
        $stmt = $this->app->getDatabase()->query("
            SELECT * FROM external_trackers 
            ORDER BY enabled DESC, name ASC
        ");
        $trackers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate success rates
        foreach ($trackers as &$tracker) {
            if ($tracker['scrape_count'] > 0) {
                $tracker['success_rate'] = round(($tracker['success_count'] / $tracker['scrape_count']) * 100, 1);
            } else {
                $tracker['success_rate'] = 0;
            }
        }

        $this->render('admin/external_trackers.twig', [
            'title' => 'External Trackers',
            'trackers' => $trackers,
        ]);
    }

    /**
     * Add external tracker
     */
    public function addExternalTracker(): void
    {
        $this->requireAdmin();
        $this->validateCsrfToken();

        $name = trim($_POST['name'] ?? '');
        $scrapeUrl = trim($_POST['scrape_url'] ?? '');

        if (empty($name) || empty($scrapeUrl)) {
            $_SESSION['error_msg'] = 'Name and scrape URL are required';
            header('Location: /admin/external-trackers');
            exit;
        }

        try {
            $stmt = $this->app->getDatabase()->prepare("
                INSERT INTO external_trackers (name, scrape_url, enabled, created_at, updated_at)
                VALUES (?, ?, 1, ?, ?)
            ");
            $now = time();
            $stmt->execute([$name, $scrapeUrl, $now, $now]);

            $_SESSION['success_msg'] = 'External tracker added successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error adding tracker: ' . $e->getMessage();
        }

        header('Location: /admin/external-trackers');
        exit;
    }

    /**
     * Edit external tracker
     */
    public function editExternalTracker(): void
    {
        $this->requireAdmin();
        $this->validateCsrfToken();

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $scrapeUrl = trim($_POST['scrape_url'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;

        if (!$id || empty($name) || empty($scrapeUrl)) {
            $_SESSION['error_msg'] = 'Invalid tracker data';
            header('Location: /admin/external-trackers');
            exit;
        }

        try {
            $stmt = $this->app->getDatabase()->prepare("
                UPDATE external_trackers 
                SET name = ?, scrape_url = ?, enabled = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $scrapeUrl, $enabled, time(), $id]);

            $_SESSION['success_msg'] = 'External tracker updated successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error updating tracker: ' . $e->getMessage();
        }

        header('Location: /admin/external-trackers');
        exit;
    }

    /**
     * Delete external tracker
     */
    public function deleteExternalTracker(): void
    {
        $this->requireAdmin();
        $this->validateCsrfToken();

        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $_SESSION['error_msg'] = 'Invalid tracker ID';
            header('Location: /admin/external-trackers');
            exit;
        }

        try {
            $stmt = $this->app->getDatabase()->prepare("
                DELETE FROM external_trackers WHERE id = ?
            ");
            $stmt->execute([$id]);

            $_SESSION['success_msg'] = 'External tracker deleted successfully';
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error deleting tracker: ' . $e->getMessage();
        }

        header('Location: /admin/external-trackers');
        exit;
    }

    /**
     * Manually trigger scrape
     */
    public function scrapeNow(): void
    {
        $this->requireAdmin();
        $this->validateCsrfToken();

        try {
            $externalScrape = new \Bittytorrent\Service\ExternalScrape(
                $this->app->getDatabase(),
                $this->app->getLogger()
            );

            $stats = $externalScrape->scrapeAllTorrents();

            $_SESSION['success_msg'] = sprintf(
                'Scrape completed: %d torrents scraped, %d successes',
                $stats['torrents_scraped'],
                $stats['successes']
            );
        } catch (\Exception $e) {
            $_SESSION['error_msg'] = 'Error during scrape: ' . $e->getMessage();
        }

        header('Location: /admin/external-trackers');
        exit;
    }
}
