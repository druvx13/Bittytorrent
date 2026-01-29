# Production Deployment Guide

**🎉 EASY DEPLOYMENT:** The `vendor/` directory is **included in this repository**. You do NOT need Composer to deploy!

This guide covers deploying Bittytorrent to a production server.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Deployment Methods](#deployment-methods)
- [Step-by-Step Deployment](#step-by-step-deployment)
- [Common Hosting Platforms](#common-hosting-platforms)
- [Post-Deployment](#post-deployment)
- [Troubleshooting](#troubleshooting)
- [Security Checklist](#security-checklist)

## Prerequisites

Before deploying, ensure your production server has:

- **PHP 8.1 or higher**
- **MySQL 5.7+ or MariaDB 10.2+**
- **Apache or Nginx** web server
- **mod_rewrite** enabled (Apache) or equivalent (Nginx)
- **PHP Extensions**:
  - pdo
  - pdo_mysql
  - mbstring
  - json
  - curl (optional)

**Note:** ✅ **Composer is NOT required!** The `vendor/` directory is included in the repository for easy deployment.

## Deployment Methods

### Method 1: Git Clone (Recommended)

Best for servers with SSH access. **No Composer needed!**

### Method 2: FTP/SFTP Upload

For shared hosting without SSH access. **No Composer needed!**

### Method 3: cPanel/Control Panel

For hosting with control panel interfaces.

## Step-by-Step Deployment

### Method 1: Git Clone (SSH Access)

**✅ vendor/ is already included - No Composer installation required!**

**1. Connect to your server:**
```bash
ssh user@your-server.com
```

**2. Navigate to web root:**
```bash
cd /var/www/html  # or your web root directory
```

**3. Clone the repository:**
```bash
git clone https://github.com/druvx13/Bittytorrent.git
cd Bittytorrent
```

**4. Dependencies already included:**
```bash
# ✅ vendor/ directory is already in the repo!
# NO NEED to run: composer install
```

**5. Set up environment:**
```bash
cp .env.example .env
nano .env  # Edit with your database credentials
```

**6. Initialize database:**
```bash
php bin/init-database.php
```

**7. Set permissions:**
```bash
chmod 755 -R .
chmod 777 var/logs var/cache public/uploads
chown -R www-data:www-data .  # or your web server user
```

**8. Configure web server:**

See [Web Server Configuration](#web-server-configuration) below.

---

### Method 2: FTP/SFTP Upload

**✅ No Composer needed - just upload all files!**

**1. Prepare locally (optional):**

Download the repository as ZIP from GitHub or clone it:
```bash
git clone https://github.com/druvx13/Bittytorrent.git
```

**2. Upload files:**

Upload ALL files and directories to your server, including:
- `public/` directory
- `src/` directory
- **`vendor/` directory** ← **ALREADY INCLUDED!**
- `config/` directory
- `views/` directory
- `bin/` directory
- `var/` directory
- `composer.json`, `composer.lock`
- `.htaccess` file (if using Apache)

**Note:** You do NOT need to run `composer install` - just upload everything!

**3. Create .env file:**

Either create it locally and upload, or create on server:
- Copy `.env.example` to `.env`
- Edit with your production database credentials

**3. Set up database via browser:**

Navigate to: `https://your-domain.com/init-db-web.php` (if created)

Or use phpMyAdmin/MySQL command line to run the SQL from `config/schema.sql`

**4. Set permissions:**

Using your FTP client or hosting control panel:
- Set `var/logs/` to writable (777 or 755)
- Set `var/cache/` to writable (777 or 755)
- Set `public/uploads/` to writable (777 or 755)

**5. Configure document root:**

Point your domain to the `public/` directory (see hosting-specific instructions below).

---

### Method 3: cPanel Deployment

**1. Upload via File Manager or FTP:**
- Upload all files to `public_html/` or a subdirectory
- Make sure to upload the `vendor/` directory

**2. Create Database:**
- Go to MySQL Databases in cPanel
- Create a new database
- Create a database user
- Add user to database with ALL PRIVILEGES
- Note the database name, username, password

**3. Configure Environment:**
- Navigate to File Manager
- Edit `.env` file with database credentials
- Set `DB_CONNECTION=mysql`
- Set `DB_HOST=localhost`
- Set `DB_DATABASE=your_database_name`
- Set `DB_USERNAME=your_database_user`
- Set `DB_PASSWORD=your_database_password`

**4. Initialize Database:**
- Go to phpMyAdmin
- Select your database
- Import `config/schema.sql`

Or use Terminal (if available):
```bash
cd /home/username/public_html
php bin/init-database.php
```

**5. Set Document Root:**
- In cPanel → Domains → your domain
- Change Document Root to: `/home/username/public_html/public`

**6. Set Permissions:**
- File Manager → select `var/logs`, `var/cache`, `public/uploads`
- Right-click → Change Permissions → 755 or 777

## Common Hosting Platforms

### 000webhost / InfinityFree / FreeHosting

These free hosting platforms often have limitations:

**Steps:**
1. Upload via FTP (FileZilla recommended)
2. Upload TO: `htdocs/` or `public_html/`
3. **Important:** Upload the `vendor/` directory!
4. Create MySQL database via control panel
5. Edit `.env` with database credentials
6. Import `config/schema.sql` via phpMyAdmin
7. Change site's root directory to point to `public/` subdirectory

**Common Issues:**
- No SSH access → Upload `vendor/` directory manually
- Limited PHP extensions → Check before deployment
- .htaccess may be restricted → Contact support

### Shared Hosting (GoDaddy, Bluehost, HostGator)

**Steps:**
1. Upload all files via FTP to `public_html/`
2. In control panel, set document root to `public_html/public/`
3. Create MySQL database
4. Edit `.env` file
5. Run `php bin/init-database.php` via SSH or import SQL via phpMyAdmin

### VPS (DigitalOcean, Linode, Vultr)

Use Method 1 (Git + Composer) - you have full control.

### Cloud Platforms (AWS, Google Cloud, Azure)

Deploy using standard LAMP/LEMP stack on EC2/Compute Engine/Virtual Machine.

## Web Server Configuration

### Apache (.htaccess)

The `.htaccess` files are already included. Ensure mod_rewrite is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Ensure your VirtualHost allows .htaccess:
```apache
<Directory /var/www/html/Bittytorrent/public>
    AllowOverride All
    Require all granted
</Directory>
```

### Nginx

Create a server block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/Bittytorrent/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Reload Nginx:
```bash
sudo systemctl reload nginx
```

## Post-Deployment

### 1. Test Installation

Visit your site: `https://your-domain.com`

You should see the homepage with torrent listings.

### 2. Login as Admin

Default admin credentials:
- Username: `admin`
- Password: `admin123`

**⚠️ CHANGE THE PASSWORD IMMEDIATELY!**

### 3. Configure Settings

Go to Admin Panel → Settings and configure:
- Site name
- Site URL
- Tracker URL
- Email settings (if applicable)

### 4. SSL Certificate (HTTPS)

**Using Let's Encrypt (Free):**
```bash
sudo apt install certbot python3-certbot-apache  # or python3-certbot-nginx
sudo certbot --apache  # or --nginx
```

**Using cPanel:**
- Go to SSL/TLS → Install SSL Certificate
- Use AutoSSL or upload your certificate

### 5. Setup Cron Jobs (Optional)

For automated tasks (peer cleanup, statistics):

```bash
# Edit crontab
crontab -e

# Add these lines (adjust paths):
*/5 * * * * php /var/www/html/Bittytorrent/bin/cleanup-peers.php
0 0 * * * php /var/www/html/Bittytorrent/bin/generate-stats.php
```

## Troubleshooting

### Error: "vendor/autoload.php not found"

**This error should not occur anymore** since vendor/ is included in the repository.

**If you still see this error:**
1. Check if you uploaded the `vendor/` directory
2. Verify `vendor/autoload.php` file exists on server
3. Check file permissions (should be readable)

**Quick fix:**
```bash
# If vendor is missing, verify it's in your local copy
ls -la vendor/

# If vendor exists locally, upload it to server
# Make sure to upload the ENTIRE vendor/ directory
```

**Alternative if using Git:**
```bash
git status  # Check if vendor is ignored
git pull    # Get latest with vendor included
ls -la vendor/  # Should show all dependencies
```

**If you prefer to use Composer instead:**
See `VENDOR_MANAGEMENT.md` for instructions on excluding vendor/ and using Composer.

### Error: "Database connection failed"

**Check:**
1. `.env` file exists and has correct credentials
2. Database exists
3. Database user has privileges
4. MySQL server is running
5. Host is correct (usually `localhost`)

### Error: "500 Internal Server Error"

**Check:**
1. PHP error logs: `var/logs/app.log`
2. Server error logs: `/var/log/apache2/error.log`
3. File permissions on `var/logs/`, `var/cache/`
4. `.htaccess` file exists in `public/`

### Error: "Permission denied" writing to logs

**Fix:**
```bash
chmod 777 var/logs var/cache public/uploads
# or
chown -R www-data:www-data var/
```

### .htaccess not working

**Apache:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Check VirtualHost allows `AllowOverride All`

### Page not found / 404 on all pages

**Check:**
1. Document root points to `public/` directory
2. mod_rewrite enabled (Apache)
3. `.htaccess` exists in `public/`
4. Nginx config has correct try_files

### Blank page / No error shown

**Enable error display temporarily:**

Edit `public/index.php`, add after opening PHP tag:
```php
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

**Remove this in production!**

## Security Checklist

Before going live:

- [ ] Change admin password from default
- [ ] Set secure session secret in `.env`
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Restrict database user privileges (no GRANT, DROP on production)
- [ ] Set proper file permissions (not 777 on everything)
- [ ] Keep `vendor/`, `config/`, `src/`, `bin/` outside web root if possible
- [ ] Configure firewall (allow only 80, 443, SSH)
- [ ] Regular backups of database and uploads
- [ ] Keep dependencies updated: `composer update`
- [ ] Monitor logs in `var/logs/`
- [ ] Implement rate limiting on tracker endpoints
- [ ] Consider fail2ban for brute force protection

## Backup & Updates

### Backup

**Database:**
```bash
mysqldump -u username -p database_name > backup.sql
```

**Files:**
```bash
tar -czf backup.tar.gz public/uploads/ var/database/ .env
```

### Updates

**Git deployment:**
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/migrate.php  # if migrations exist
```

**Manual deployment:**
- Download new version
- Upload files (except .env and uploads)
- Run any migration scripts

## Performance Tips

1. **Enable OPcache** in PHP
2. **Use Twig cache** (already enabled)
3. **Enable gzip compression** in Apache/Nginx
4. **Use CDN** for static assets
5. **Database indexes** (already in schema)
6. **PHP-FPM** instead of mod_php
7. **Redis/Memcached** for sessions (advanced)

## Support

If you encounter issues:

1. Check documentation: `INSTALL.md`, `SETUP.md`, `GETTING_STARTED.md`
2. Check logs: `var/logs/app.log`
3. Review this deployment guide
4. Search error messages online
5. Open an issue on GitHub

## Quick Deployment Checklist

- [ ] Server meets requirements (PHP 8.1+, MySQL)
- [ ] Files uploaded (including `vendor/`)
- [ ] `.env` configured with database credentials
- [ ] Database created and initialized
- [ ] File permissions set (writable: logs, cache, uploads)
- [ ] Document root points to `public/` directory
- [ ] mod_rewrite enabled (Apache) or Nginx configured
- [ ] Site accessible in browser
- [ ] Admin login works
- [ ] Admin password changed
- [ ] HTTPS enabled
- [ ] Security settings configured

---

**Congratulations! Your Bittytorrent tracker is now deployed!** 🎉
