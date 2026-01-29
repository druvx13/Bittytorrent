-- MySQL schema for Bittytorrent
-- Modern, normalized database structure

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    upload_bytes BIGINT DEFAULT 0,
    download_bytes BIGINT DEFAULT 0,
    location VARCHAR(100),
    website VARCHAR(200),
    signature TEXT,
    private_key VARCHAR(32) NOT NULL UNIQUE,
    email_visible TINYINT(1) DEFAULT 0,
    torrents_visible TINYINT(1) DEFAULT 1,
    created_at INT NOT NULL,
    last_login INT,
    is_active TINYINT(1) DEFAULT 1,
    CHECK (role IN ('user', 'admin'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    position INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Torrents table
CREATE TABLE IF NOT EXISTS torrents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT,
    info_hash VARCHAR(40) NOT NULL UNIQUE,
    size_bytes BIGINT DEFAULT 0,
    views INT DEFAULT 0,
    seeders INT DEFAULT 0,
    leechers INT DEFAULT 0,
    completed INT DEFAULT 0,
    last_scrape INT,
    created_at INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Peers table
CREATE TABLE IF NOT EXISTS peers (
    info_hash VARCHAR(40) NOT NULL,
    peer_id VARCHAR(40) NOT NULL,
    user_id INT,
    ip VARCHAR(45) NOT NULL,
    port INT NOT NULL,
    uploaded BIGINT DEFAULT 0,
    downloaded BIGINT DEFAULT 0,
    remaining BIGINT DEFAULT 0,
    is_seeder TINYINT(1) DEFAULT 0,
    user_agent VARCHAR(255),
    updated_at INT NOT NULL,
    PRIMARY KEY (info_hash, peer_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_info_hash (info_hash),
    KEY idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    value TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'string'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (for custom session handler if needed)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    created_at INT NOT NULL,
    updated_at INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugins table
CREATE TABLE IF NOT EXISTS plugins (
    name VARCHAR(100) PRIMARY KEY,
    enabled TINYINT(1) DEFAULT 0,
    config TEXT,
    installed_at INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX idx_torrents_user ON torrents(user_id);
CREATE INDEX idx_torrents_category ON torrents(category_id);
CREATE INDEX idx_torrents_info_hash ON torrents(info_hash);
CREATE INDEX idx_torrents_created ON torrents(created_at);

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123' using Argon2id
INSERT IGNORE INTO users (id, username, email, password, role, private_key, created_at)
VALUES (1, 'admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$YnJvd25mb3hqdW1wc292ZXI$VK0kGvmhvZqm8rL3V0wH9qm7PQFz7h3lR6qkUV6xCqE', 'admin', MD5(CONCAT(RAND(), NOW())), UNIX_TIMESTAMP());

-- Insert default categories
INSERT IGNORE INTO categories (name, slug, description, position) VALUES
('Movies', 'movies', 'Movie torrents', 1),
('TV Shows', 'tv-shows', 'TV show torrents', 2),
('Music', 'music', 'Music torrents', 3),
('Games', 'games', 'Game torrents', 4),
('Software', 'software', 'Software torrents', 5),
('Books', 'books', 'E-book torrents', 6),
('Other', 'other', 'Other torrents', 7);

-- External trackers table
CREATE TABLE IF NOT EXISTS external_trackers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    scrape_url VARCHAR(255) NOT NULL UNIQUE,
    enabled TINYINT(1) DEFAULT 1,
    last_scrape INT DEFAULT 0,
    scrape_count INT DEFAULT 0,
    success_count INT DEFAULT 0,
    fail_count INT DEFAULT 0,
    created_at INT NOT NULL,
    updated_at INT NOT NULL,
    KEY idx_enabled (enabled),
    KEY idx_last_scrape (last_scrape)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Torrent external trackers relationship (many-to-many)
CREATE TABLE IF NOT EXISTS torrent_external_trackers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    torrent_id INT NOT NULL,
    external_tracker_id INT NOT NULL,
    external_seeders INT DEFAULT 0,
    external_leechers INT DEFAULT 0,
    external_completed INT DEFAULT 0,
    last_scrape INT DEFAULT 0,
    last_update INT DEFAULT 0,
    FOREIGN KEY (torrent_id) REFERENCES torrents(id) ON DELETE CASCADE,
    FOREIGN KEY (external_tracker_id) REFERENCES external_trackers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_torrent_tracker (torrent_id, external_tracker_id),
    KEY idx_torrent (torrent_id),
    KEY idx_tracker (external_tracker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT IGNORE INTO settings (`key`, value, type) VALUES
('site_name', 'Bittytorrent', 'string'),
('site_description', 'Modern BitTorrent Tracker', 'string'),
('registration_open', 'true', 'boolean'),
('torrents_per_page', '25', 'integer'),
('theme', 'default', 'string');
