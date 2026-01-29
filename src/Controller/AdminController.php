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
            'recent_torrents' => $torrentModel->getAll(1, 5),
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
        $torrents = $torrentModel->getAll($page, $perPage);
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
}
