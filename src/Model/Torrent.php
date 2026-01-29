<?php

declare(strict_types=1);

namespace Bittytorrent\Model;

use PDO;
use Bittytorrent\Application;

/**
 * Torrent Model
 * 
 * Handles torrent management and operations
 */
class Torrent
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Application::getInstance()->getDb();
    }
    
    /**
     * Create a new torrent entry
     */
    public function create(array $data): ?int
    {
        $stmt = $this->db->prepare("
            INSERT INTO torrents 
            (user_id, category_id, title, slug, description, info_hash, size_bytes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([
                $data['user_id'],
                $data['category_id'] ?? null,
                $data['title'],
                $this->generateSlug($data['title']),
                $data['description'] ?? '',
                $data['info_hash'],
                $data['size_bytes'] ?? 0,
                time()
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            Application::getInstance()->getLogger()->error('Torrent creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find torrent by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, u.username, c.name as category_name, c.slug as category_slug
            FROM torrents t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            WHERE t.id = ?
            LIMIT 1
        ");
        
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Find torrent by info hash
     */
    public function findByInfoHash(string $infoHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM torrents 
            WHERE info_hash = ?
            LIMIT 1
        ");
        
        $stmt->execute([$infoHash]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all torrents with pagination and filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "t.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $orderBy = $filters['order_by'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'DESC';
        
        $sql = "
            SELECT t.*, u.username, c.name as category_name, c.slug as category_slug
            FROM torrents t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN categories c ON t.category_id = c.id
            $whereClause
            ORDER BY t.$orderBy $direction
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Count torrents with filters
     */
    public function count(array $filters = []): int
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $where[] = "category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM torrents $whereClause");
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    /**
     * Update torrent
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['title', 'category_id', 'description'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
                
                // Update slug if title changed
                if ($field === 'title') {
                    $updates[] = "slug = ?";
                    $params[] = $this->generateSlug($data[$field]);
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE torrents SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Increment view count
     */
    public function incrementViews(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE torrents SET views = views + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Update peer statistics from scrape
     */
    public function updateStats(string $infoHash, int $seeders, int $leechers, int $completed): bool
    {
        $stmt = $this->db->prepare("
            UPDATE torrents 
            SET seeders = ?, leechers = ?, completed = ?, last_scrape = ?
            WHERE info_hash = ?
        ");
        
        return $stmt->execute([$seeders, $leechers, $completed, time(), $infoHash]);
    }
    
    /**
     * Delete torrent
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM torrents WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Generate URL-friendly slug from title
     */
    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return substr($slug, 0, 200);
    }
}
