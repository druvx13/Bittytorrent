# 🚀 Getting Started with Bittytorrent

Welcome! This guide will walk you through setting up Bittytorrent step-by-step, from installation to your first torrent upload. Perfect for beginners!

---

## 📋 Table of Contents

1. [What You'll Need](#what-youll-need)
2. [Understanding the Setup](#understanding-the-setup)
3. [Step-by-Step Installation](#step-by-step-installation)
4. [First-Time Configuration](#first-time-configuration)
5. [Uploading Your First Torrent](#uploading-your-first-torrent)
6. [User Management](#user-management)
7. [Common Issues & Troubleshooting](#common-issues--troubleshooting)
8. [Next Steps](#next-steps)

---

## 📦 What You'll Need

### Required Software

Before starting, make sure you have:

1. **PHP 8.1 or higher**
   - Check with: `php -v`
   - If not installed: 
     - Ubuntu/Debian: `sudo apt install php8.1 php8.1-cli php8.1-fpm`
     - macOS (Homebrew): `brew install php@8.1`
     - Windows: Download from [php.net](https://windows.php.net/download/)

2. **Composer** (PHP package manager)
   - Check with: `composer --version`
   - Install from: [getcomposer.org](https://getcomposer.org/download/)

3. **Web Server** (choose one):
   - **Apache** with mod_rewrite (recommended for beginners)
   - **Nginx** (requires additional configuration)
   - **PHP Built-in Server** (development only)

4. **Git** (to clone the repository)
   - Check with: `git --version`
   - Install: [git-scm.com](https://git-scm.com/downloads)

### Required PHP Extensions

Check if you have these extensions (they usually come with PHP):
```bash
php -m | grep -E "pdo|mysql|mbstring|json"
```

You should see:
- pdo
- pdo_mysql
- mbstring
- json

If any are missing, install them:
- Ubuntu/Debian: `sudo apt install php8.1-mysql php8.1-mbstring`
- macOS: Usually included with Homebrew PHP
- Windows: Uncomment extensions in `php.ini`

### MySQL Database

You'll also need a MySQL server:
- Ubuntu/Debian: `sudo apt install mysql-server`
- macOS (Homebrew): `brew install mysql`
- Windows: Download from [MySQL Downloads](https://dev.mysql.com/downloads/mysql/)

### System Resources

- **Disk Space:** ~100 MB for the application + space for torrents
- **RAM:** At least 256 MB available
- **Operating System:** Linux, macOS, or Windows

---

## 🎓 Understanding the Setup

Before we start, let's understand what we're building:

### What is Bittytorrent?

Bittytorrent is a **complete BitTorrent tracker website**. It includes:

1. **Tracker Server** - Coordinates peer connections for torrents
2. **Web Interface** - Website for uploading and browsing torrents
3. **User System** - Registration, login, and permissions
4. **Database** - Stores torrents, users, and tracker data

### Architecture Overview

```
Bittytorrent/
├── public/          # Web-accessible files (your web server points here)
├── src/             # Application code (controllers, models, services)
├── views/           # HTML templates
├── config/          # Configuration files
├── var/             # Variable data (database, logs, cache)
├── bin/             # Command-line scripts
└── vendor/          # Dependencies (installed by Composer)
```

### How It Works

1. Users access your website through their browser
2. They can register, login, and upload .torrent files
3. BitTorrent clients connect to your tracker to find peers
4. The tracker coordinates peer-to-peer file sharing

---

## 🔧 Step-by-Step Installation

### Step 1: Download Bittytorrent

Open a terminal/command prompt and run:

```bash
# Navigate to where you want to install (e.g., your home directory)
cd ~

# Clone the repository
git clone https://github.com/druvx13/Bittytorrent.git

# Enter the directory
cd Bittytorrent
```

**What this does:** Downloads all the Bittytorrent files to your computer.

---

### Step 2: Install Dependencies

Bittytorrent uses several PHP libraries. Composer will download them automatically:

```bash
composer install --no-dev --optimize-autoloader
```

**What this does:** 
- Downloads required libraries (Twig, Monolog, etc.)
- Optimizes loading for better performance
- Creates a `vendor/` directory with all dependencies

**Expected output:**
```
Loading composer repositories...
Installing dependencies from lock file
...
Generating optimized autoload files
```

**Time:** 1-2 minutes depending on your internet speed

---

### Step 3: Create Configuration File

Copy the example configuration:

```bash
cp .env.example .env
```

**For development:** The defaults are fine! You can skip editing for now.

**For production:** Edit `.env` with your settings:

```bash
nano .env  # or use your favorite editor
```

Key settings to change:
```env
APP_NAME="Your Tracker Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# MySQL Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bittytorrent
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

Press `Ctrl+X`, then `Y`, then `Enter` to save in nano.

**Database Setup:**

Before initializing the database, make sure you have created a MySQL user and database:

```bash
# Login to MySQL as root
mysql -u root -p

# Create database and user
CREATE DATABASE bittytorrent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bittytorrent'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON bittytorrent.* TO 'bittytorrent'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### Step 4: Initialize the Database

This creates your MySQL database with tables and a default admin account:

```bash
php bin/init-database.php
```

**Expected output:**
```
Bittytorrent Database Initialization
=====================================

Database Configuration:
  Host: localhost:3306
  Database: bittytorrent
  User: bittytorrent

Connected to MySQL server successfully.

Creating database 'bittytorrent'...
Database created successfully.
Loading schema...
Executing schema...

✓ Database initialized successfully!

Default Admin Credentials:
==========================
Username: admin
Password: admin123

⚠️  IMPORTANT: Change the default password immediately!
```

**What this does:**
- Creates MySQL database with proper charset (utf8mb4)
- Creates all necessary database tables
- Adds default categories (Movies, TV Shows, Music, etc.)
- Creates an admin account

**Time:** ~5-10 seconds

---

### Step 5: Set Permissions

Make sure the application can write to necessary directories:

```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
```

**On Windows:** Skip this step (permissions work differently)

**What this does:** 
- Allows the web server to write logs
- Allows users to upload torrent files
- Enables database updates

---

### Step 6: Start the Application

Choose your preferred method:

#### Option A: PHP Built-in Server (Development)

**Easiest for testing:**

```bash
cd public
php -S localhost:8000
```

**Access:** Open http://localhost:8000 in your browser

**Stop server:** Press `Ctrl+C` in the terminal

#### Option B: Apache (Production)

1. **Enable mod_rewrite:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. **Create virtual host:**
```bash
sudo nano /etc/apache2/sites-available/bittytorrent.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName bittytorrent.local
    DocumentRoot /path/to/Bittytorrent/public
    
    <Directory /path/to/Bittytorrent/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/bittytorrent-error.log
    CustomLog ${APACHE_LOG_DIR}/bittytorrent-access.log combined
</VirtualHost>
```

3. **Enable site:**
```bash
sudo a2ensite bittytorrent.conf
sudo systemctl restart apache2
```

4. **Add to hosts file:**
```bash
sudo nano /etc/hosts
```
Add: `127.0.0.1 bittytorrent.local`

**Access:** http://bittytorrent.local

#### Option C: Nginx (Production)

1. **Create config:**
```bash
sudo nano /etc/nginx/sites-available/bittytorrent
```

Add:
```nginx
server {
    listen 80;
    server_name bittytorrent.local;
    root /path/to/Bittytorrent/public;
    index index.php;

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

2. **Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/bittytorrent /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

---

## 🎯 First-Time Configuration

### Access the Website

1. Open your browser
2. Navigate to your site (e.g., http://localhost:8000)
3. You should see the Bittytorrent homepage!

### Login as Administrator

1. Click **"Login"** in the top navigation
2. Enter credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
3. Click **"Login"**

### Change Admin Password

**IMPORTANT:** Change the default password immediately!

1. Click your username in the top right
2. Select **"Profile"** (when implemented) or change in database:

```bash
# Alternative: Use this script to change password
php bin/change-password.php admin
```

---

## 📤 Uploading Your First Torrent

### Create a Torrent File

Before uploading, you need a .torrent file. Use a BitTorrent client:

**Using qBittorrent (free, recommended):**
1. Download and install [qBittorrent](https://www.qbittorrent.org/)
2. Go to Tools → Torrent Creator
3. Select the file(s) you want to share
4. Set tracker URL: `http://your-domain.com/announce`
5. Click "Create"

### Upload to Bittytorrent

1. Make sure you're logged in
2. Click **"Upload"** in the navigation
3. Fill in the form:
   - **Torrent File:** Browse and select your .torrent file
   - **Title:** Give it a descriptive name
   - **Category:** Choose appropriate category
   - **Description:** Describe what it is
4. Click **"Upload Torrent"**

**Success!** Your torrent is now listed on the site.

### Download and Test

1. Find your torrent on the homepage
2. Click the title to view details
3. Click **"Download Torrent"** button
4. Open the downloaded .torrent file in your BitTorrent client
5. Start downloading/seeding!

---

## 👥 User Management

### Allow User Registration

By default, users can register themselves:

1. Users click **"Register"**
2. Fill in username, email, password
3. Account is created automatically

### Manage Users (Admin Only)

Admin features (to be implemented):
- View all users
- Edit user details
- Delete users
- Change user roles
- View user statistics

---

## 🔧 Common Issues & Troubleshooting

### Issue: "500 Internal Server Error"

**Causes & Solutions:**

1. **Permissions issue**
   ```bash
   chmod -R 755 var/
   chmod -R 755 public/uploads/
   ```

2. **Missing .env file**
   ```bash
   cp .env.example .env
   ```

3. **Check error log**
   ```bash
   tail -f var/logs/app.log
   ```

### Issue: "Database connection failed"

**Solution:**
```bash
# Check MySQL is running
sudo systemctl status mysql  # Linux
brew services list | grep mysql  # macOS

# Reinitialize database
php bin/init-database.php

# Verify database credentials in .env
cat .env | grep DB_

# Test MySQL connection
mysql -h localhost -u your_user -p bittytorrent
```

### Issue: "Page not found (404)" on all pages

**For Apache:**
```bash
# Ensure mod_rewrite is enabled
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**For Nginx:**
Check that your config includes the `try_files` directive.

### Issue: "Composer command not found"

**Install Composer:**
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

### Issue: Missing PHP extensions

**Check what's missing:**
```bash
php -m
```

**Install on Ubuntu/Debian:**
```bash
sudo apt install php8.1-mysql php8.1-mbstring php8.1-xml
```

### Issue: Cannot upload files

**Solution:**
```bash
# Create uploads directory
mkdir -p public/uploads
chmod 755 public/uploads

# Check PHP upload limits
php -i | grep upload_max_filesize
```

To increase, edit `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
```

### Issue: Tracker not working

**Verify tracker endpoint:**
```bash
curl "http://localhost:8000/announce?info_hash=12345678901234567890&peer_id=12345678901234567890&port=6881&uploaded=0&downloaded=0&left=0"
```

Should return bencoded response starting with `d`.

### Getting More Help

1. **Check logs:** `var/logs/app.log`
2. **Enable debug mode:** Set `APP_DEBUG=true` in `.env`
3. **Read documentation:** See [SETUP.md](SETUP.md) for detailed info
4. **Ask for help:** Open an issue on GitHub

---

## 🎓 Next Steps

### Production Deployment

For a live website, you should:

1. **Enable HTTPS**
   ```bash
   # Using Let's Encrypt (free)
   sudo certbot --apache  # or --nginx
   ```

2. **Secure Configuration**
   - Set `APP_ENV=production` in `.env`
   - Set `APP_DEBUG=false`
   - Change all default passwords

3. **Set up backups**
   ```bash
   # Backup MySQL database daily
   mysqldump -u bittytorrent -p bittytorrent > backups/db-$(date +%Y%m%d).sql
   
   # Or use a cron job
   0 2 * * * mysqldump -u bittytorrent -p'password' bittytorrent | gzip > /backups/db-$(date +\%Y\%m\%d).sql.gz
   ```

4. **Configure cron jobs**
   ```bash
   # Clean old peers hourly
   0 * * * * cd /path/to/Bittytorrent && php -r "require 'vendor/autoload.php'; \$app = Bittytorrent\Application::getInstance(); \$tracker = new Bittytorrent\Service\Tracker(); \$tracker->cleanOldPeers();"
   ```

### Customization

- **Change site name:** Edit `APP_NAME` in `.env`
- **Add categories:** Edit database or through admin panel
- **Customize theme:** Edit files in `views/` and `public/css/`
- **Add plugins:** Check `plugins/` directory

### Learning More

- **PHP 8 Compatibility:** [PHP8_COMPATIBILITY.md](PHP8_COMPATIBILITY.md)
- **Security Features:** [SECURITY.md](SECURITY.md)
- **Detailed Setup:** [SETUP.md](SETUP.md)

---

## 🎉 Congratulations!

You now have a working BitTorrent tracker website! 

**What you've achieved:**
- ✅ Installed and configured Bittytorrent
- ✅ Created a database with admin account
- ✅ Set up a web server
- ✅ Uploaded your first torrent
- ✅ Learned basic troubleshooting

### Share Your Experience

- ⭐ Star the repository on GitHub
- 🐛 Report bugs or suggest features
- 💬 Help others in GitHub Discussions
- 🤝 Contribute improvements

---

## 📞 Need Help?

- 📖 **Documentation:** Check other .md files in the repository
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/druvx13/Bittytorrent/issues)
- 💬 **Questions:** [GitHub Discussions](https://github.com/druvx13/Bittytorrent/discussions)
- 🔒 **Security Issues:** Use GitHub Security Advisories

---

<div align="center">

**Happy Tracking! 🚀**

Made with ❤️ for the BitTorrent community

</div>
