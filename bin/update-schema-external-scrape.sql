-- Migration script to add external scrape feature tables
-- Run this to add external tracker support to existing database

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
