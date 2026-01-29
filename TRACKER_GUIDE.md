# BitTorrent Tracker Usage Guide

## Overview

Bittytorrent includes a fully functional BitTorrent tracker that handles announce and scrape requests from BitTorrent clients. This guide explains how to use and maintain the tracker.

## Table of Contents

- [How the Tracker Works](#how-the-tracker-works)
- [Tracker URLs](#tracker-urls)
- [Using Your Torrents](#using-your-torrents)
- [Configuration](#configuration)
- [Maintenance](#maintenance)
- [Statistics Updates](#statistics-updates)
- [Troubleshooting](#troubleshooting)

## How the Tracker Works

The BitTorrent tracker performs several functions:

### 1. **Announce Endpoint** (`/announce`)
- Receives periodic updates from BitTorrent clients
- Tracks active peers (seeders and leechers)
- Returns peer lists to help clients connect
- Updates peer statistics in real-time

### 2. **Scrape Endpoint** (`/scrape`)
- Allows clients to query torrent statistics
- Returns seeder/leecher counts without announcing
- Supports both single and multi-torrent scrapes

### 3. **Peer Management**
- Tracks peer information (IP, port, upload/download stats)
- Automatically removes inactive peers
- Updates torrent statistics (seeders, leechers, completed)

## Tracker URLs

Your tracker provides two main endpoints:

### Announce URL
```
http://yourdomain.com/announce
```

This is the primary tracker URL that BitTorrent clients use to:
- Announce their presence
- Get peer lists
- Report download progress

### Scrape URL
```
http://yourdomain.com/scrape
```

Used by clients to query statistics without announcing.

## Using Your Torrents

### Step 1: Create a Torrent with Your Tracker

When creating a .torrent file, use your tracker announce URL:

**Using transmission-cli:**
```bash
transmission-create -t http://yourdomain.com/announce -c "My comment" myfile.zip
```

**Using mktorrent:**
```bash
mktorrent -a http://yourdomain.com/announce -c "My comment" myfile.zip
```

**Using qBittorrent GUI:**
1. Tools → Torrent Creator
2. Add your announce URL: `http://yourdomain.com/announce`
3. Select file/folder
4. Create torrent

### Step 2: Upload to Bittytorrent

1. Log in to your Bittytorrent site
2. Click "Upload Torrent"
3. Select the .torrent file you created
4. Fill in details (title, description, category)
5. Submit

### Step 3: Download and Seed

1. Download the .torrent file from your site
2. Open it in your BitTorrent client
3. The client will automatically connect to your tracker
4. Start seeding the file

### Step 4: Share

Others can now:
1. Download the .torrent file from your site
2. Open it in their BitTorrent client
3. The tracker connects them to seeders
4. Download begins!

## Configuration

The tracker behavior can be configured via environment variables in `.env`:

### Announce Interval
```bash
# How often clients should announce (in seconds)
# Default: 1800 (30 minutes)
ANNOUNCE_INTERVAL=1800

# Minimum interval between announces
# Default: 900 (15 minutes)
MIN_INTERVAL=900
```

### Peer Limits
```bash
# Default number of peers to return
# Default: 50
DEFAULT_PEERS=50

# Maximum number of peers to return
# Default: 50
MAX_PEERS=50
```

### Tracker Mode
```bash
# Allow tracking of unregistered torrents
# Default: true (open tracker)
# Set to false for private tracker
TRACKER_OPEN=true

# Allow full scrape requests (all torrents)
# Default: false (disabled for performance)
# Only enable on small trackers
FULL_SCRAPE=false
```

## Maintenance

The tracker requires periodic maintenance to stay healthy.

### Automatic Maintenance Script

A maintenance script is provided at `bin/tracker-maintenance.php` that:

1. **Cleans Old Peers**: Removes peers that haven't announced in 2x the announce interval
2. **Updates Statistics**: Syncs seeder/leecher counts from peers table to torrents table

### Setting Up Cron

**Recommended:** Run every 15-30 minutes

Edit your crontab:
```bash
crontab -e
```

Add this line (adjust path as needed):
```bash
*/15 * * * * /usr/bin/php /path/to/bittytorrent/bin/tracker-maintenance.php >> /path/to/bittytorrent/var/logs/tracker-maintenance.log 2>&1
```

This will:
- Run every 15 minutes
- Clean inactive peers
- Update torrent statistics
- Log output to a file

### Manual Maintenance

You can also run maintenance manually:

```bash
cd /path/to/bittytorrent
php bin/tracker-maintenance.php
```

Expected output:
```
==============================================
BitTorrent Tracker Maintenance
==============================================
Started at: 2024-01-29 12:00:00

1. Cleaning old/inactive peers...
   ✓ Removed 15 inactive peer(s)

2. Updating torrent statistics...
   ✓ Updated stats for 42 torrent(s)

==============================================
Maintenance completed successfully!
Finished at: 2024-01-29 12:00:02
==============================================
```

## Statistics Updates

### Real-Time Updates

The tracker updates statistics in real-time when:
- A peer announces (adds/updates peer)
- A peer stops (removes peer)
- A peer completes download (increments completed count)

### Periodic Updates

The maintenance script ensures:
- Inactive peers are removed
- Seeder/leecher counts are accurate
- Database stays clean and performant

### Viewing Statistics

Statistics are displayed on:
- **Torrent detail pages**: Shows current seeders, leechers, completed
- **Browse page**: Shows stats for all torrents
- **Admin dashboard**: Shows overall tracker stats

## Troubleshooting

### Seeders/Leechers Show 0

**Problem:** Statistics aren't updating

**Solutions:**

1. **Check if maintenance script is running:**
   ```bash
   php bin/tracker-maintenance.php
   ```

2. **Verify cron is set up:**
   ```bash
   crontab -l | grep tracker-maintenance
   ```

3. **Check logs:**
   ```bash
   tail -f var/logs/app.log
   tail -f var/logs/tracker-maintenance.log
   ```

4. **Verify peers are announcing:**
   ```sql
   SELECT COUNT(*) FROM peers;
   SELECT info_hash, COUNT(*) FROM peers GROUP BY info_hash;
   ```

### Clients Can't Connect to Tracker

**Problem:** BitTorrent clients show "tracker offline" or "couldn't connect"

**Solutions:**

1. **Verify tracker URLs are accessible:**
   ```bash
   curl "http://yourdomain.com/announce"
   # Should return bencode error (expected without parameters)
   ```

2. **Check web server logs:**
   ```bash
   tail -f /var/log/apache2/error.log  # Apache
   tail -f /var/log/nginx/error.log    # Nginx
   ```

3. **Test with simple announce:**
   ```bash
   curl "http://yourdomain.com/announce?info_hash=12345678901234567890&peer_id=12345678901234567890&port=6881&uploaded=0&downloaded=0&left=1000"
   ```

4. **Verify firewall allows traffic:**
   - Ensure port 80/443 is open
   - Check server firewall rules

### Announce URL Not Showing

**Problem:** Tracker announce URL missing from torrent detail page

**Solution:** The announce URL should automatically appear on torrent detail pages. If not:

1. Clear browser cache
2. Verify `APP_URL` is set correctly in `.env`
3. Check that template changes are deployed

### Performance Issues

**Problem:** Tracker slowing down with many peers

**Solutions:**

1. **Increase maintenance frequency:**
   ```bash
   # Run every 10 minutes instead of 15
   */10 * * * * /usr/bin/php /path/to/tracker-maintenance.php
   ```

2. **Optimize database:**
   ```sql
   OPTIMIZE TABLE peers;
   OPTIMIZE TABLE torrents;
   ```

3. **Add indexes (already in schema):**
   ```sql
   -- These should already exist
   SHOW INDEX FROM peers;
   SHOW INDEX FROM torrents;
   ```

4. **Reduce peer limits in `.env`:**
   ```bash
   DEFAULT_PEERS=30
   MAX_PEERS=50
   ```

## Advanced Usage

### Private Tracker Mode

To run a private tracker (only registered torrents):

1. Edit `.env`:
   ```bash
   TRACKER_OPEN=false
   ```

2. Only torrents uploaded through your site will be tracked
3. Unknown info_hashes will be rejected

### Custom Announce Intervals

Shorter intervals = more frequent updates but higher server load:

```bash
# Fast updates (every 10 minutes)
ANNOUNCE_INTERVAL=600
MIN_INTERVAL=300

# Slower updates (every hour)
ANNOUNCE_INTERVAL=3600
MIN_INTERVAL=1800
```

### Monitoring

Check tracker health:

```sql
-- Active torrents
SELECT COUNT(*) FROM torrents WHERE seeders > 0 OR leechers > 0;

-- Total active peers
SELECT COUNT(*) FROM peers;

-- Top torrents by peers
SELECT t.title, t.seeders, t.leechers, 
       (t.seeders + t.leechers) as total_peers
FROM torrents t
ORDER BY total_peers DESC
LIMIT 10;

-- Peer activity in last hour
SELECT COUNT(*) FROM peers 
WHERE updated_at > UNIX_TIMESTAMP() - 3600;
```

## Best Practices

1. **Run maintenance regularly**: Every 15-30 minutes
2. **Monitor logs**: Check for errors and warnings
3. **Keep announce intervals reasonable**: 30-60 minutes is typical
4. **Clean old torrents**: Remove torrents with no activity
5. **Use HTTPS**: Encrypt tracker communication
6. **Backup database**: Regular backups of peers and torrents tables
7. **Monitor disk space**: Log files can grow large

## Support

If you encounter issues:

1. Check this guide
2. Review application logs (`var/logs/app.log`)
3. Test tracker endpoints manually
4. Verify database connectivity
5. Check file permissions

The tracker is designed to be robust and self-maintaining with proper cron setup!
