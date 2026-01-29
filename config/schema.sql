-- SQLite schema for Bittytorrent
-- Modern, normalized database structure

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    upload_bytes INTEGER DEFAULT 0,
    download_bytes INTEGER DEFAULT 0,
    location VARCHAR(100),
    website VARCHAR(200),
    signature TEXT,
    private_key VARCHAR(32) NOT NULL UNIQUE,
    email_visible BOOLEAN DEFAULT 0,
    torrents_visible BOOLEAN DEFAULT 1,
    created_at INTEGER NOT NULL,
    last_login INTEGER,
    is_active BOOLEAN DEFAULT 1,
    CHECK (role IN ('user', 'admin'))
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    position INTEGER DEFAULT 0
);

-- Torrents table
CREATE TABLE IF NOT EXISTS torrents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    category_id INTEGER,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT,
    info_hash VARCHAR(40) NOT NULL UNIQUE,
    size_bytes INTEGER DEFAULT 0,
    views INTEGER DEFAULT 0,
    seeders INTEGER DEFAULT 0,
    leechers INTEGER DEFAULT 0,
    completed INTEGER DEFAULT 0,
    last_scrape INTEGER,
    created_at INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Peers table
CREATE TABLE IF NOT EXISTS peers (
    info_hash VARCHAR(40) NOT NULL,
    peer_id VARCHAR(40) NOT NULL,
    user_id INTEGER,
    ip VARCHAR(45) NOT NULL,
    port INTEGER NOT NULL,
    uploaded INTEGER DEFAULT 0,
    downloaded INTEGER DEFAULT 0,
    remaining INTEGER DEFAULT 0,
    is_seeder BOOLEAN DEFAULT 0,
    user_agent VARCHAR(255),
    updated_at INTEGER NOT NULL,
    PRIMARY KEY (info_hash, peer_id),
    FOREIGN KEY (info_hash) REFERENCES torrents(info_hash) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(50) PRIMARY KEY,
    value TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'string'
);

-- Sessions table (for custom session handler if needed)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INTEGER,
    data TEXT,
    created_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Plugins table
CREATE TABLE IF NOT EXISTS plugins (
    name VARCHAR(100) PRIMARY KEY,
    enabled BOOLEAN DEFAULT 0,
    config TEXT,
    installed_at INTEGER NOT NULL
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_torrents_user ON torrents(user_id);
CREATE INDEX IF NOT EXISTS idx_torrents_category ON torrents(category_id);
CREATE INDEX IF NOT EXISTS idx_torrents_info_hash ON torrents(info_hash);
CREATE INDEX IF NOT EXISTS idx_torrents_created ON torrents(created_at);
CREATE INDEX IF NOT EXISTS idx_peers_info_hash ON peers(info_hash);
CREATE INDEX IF NOT EXISTS idx_peers_updated ON peers(updated_at);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123' using Argon2id
INSERT OR IGNORE INTO users (id, username, email, password, role, private_key, created_at)
VALUES (1, 'admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$YnJvd25mb3hqdW1wc292ZXI$VK0kGvmhvZqm8rL3V0wH9qm7PQFz7h3lR6qkUV6xCqE', 'admin', substr(hex(randomblob(16)), 1, 32), strftime('%s', 'now'));

-- Insert default categories
INSERT OR IGNORE INTO categories (name, slug, description, position) VALUES
('Movies', 'movies', 'Movie torrents', 1),
('TV Shows', 'tv-shows', 'TV show torrents', 2),
('Music', 'music', 'Music torrents', 3),
('Games', 'games', 'Game torrents', 4),
('Software', 'software', 'Software torrents', 5),
('Books', 'books', 'E-book torrents', 6),
('Other', 'other', 'Other torrents', 7);

-- Insert default settings
INSERT OR IGNORE INTO settings (key, value, type) VALUES
('site_name', 'Bittytorrent', 'string'),
('site_description', 'Modern BitTorrent Tracker', 'string'),
('registration_open', 'true', 'boolean'),
('torrents_per_page', '25', 'integer'),
('theme', 'default', 'string');
