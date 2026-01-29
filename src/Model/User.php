<?php

declare(strict_types=1);

namespace Bittytorrent\Model;

use PDO;
use Bittytorrent\Application;

/**
 * User Model
 * 
 * Handles user authentication and management with secure password hashing
 */
class User
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Application::getInstance()->getDb();
    }
    
    /**
     * Create a new user
     */
    public function create(string $username, string $email, string $password, string $role = 'user'): ?int
    {
        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            return null;
        }
        
        // Hash password using Argon2id
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ]);
        
        // Generate unique private key for tracker authentication
        $privateKey = bin2hex(random_bytes(16));
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password, role, private_key, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([
                $username,
                $email,
                $hashedPassword,
                $role,
                $privateKey,
                time()
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            Application::getInstance()->getLogger()->error('User creation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Authenticate user by username and password
     */
    public function authenticate(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE username = ? AND is_active = 1
            LIMIT 1
        ");
        
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if password needs rehashing (algorithm upgraded)
            if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
                $this->updatePassword((int)$user['id'], $password);
            }
            
            // Update last login
            $this->updateLastLogin((int)$user['id']);
            
            // Remove password from returned data
            unset($user['password']);
            
            return $user;
        }
        
        return null;
    }
    
    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }
    
    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password']);
            return $user;
        }
        
        return null;
    }
    
    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ]);
        
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
        $stmt->execute([time(), $userId]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $allowedFields = ['location', 'website', 'signature', 'email_visible', 'torrents_visible'];
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll(int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        
        $stmt = $this->db->prepare("
            SELECT id, username, email, role, upload_bytes, download_bytes, created_at, last_login, is_active
            FROM users
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Count total users
     */
    public function count(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
    
    /**
     * Update user stats (upload/download)
     */
    public function updateStats(int $userId, int $uploaded, int $downloaded): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET upload_bytes = upload_bytes + ?, 
                download_bytes = download_bytes + ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$uploaded, $downloaded, $userId]);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Delete user
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}
