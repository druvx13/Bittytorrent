# Quick Deployment Guide

## Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache or Nginx)
- Composer

## Installation Steps

### 1. Clone and Install

```bash
git clone https://github.com/druvx13/Bittytorrent.git
cd Bittytorrent
composer install --no-dev --optimize-autoloader
```

### 2. Configure

```bash
cp .env.example .env
# Edit .env with your MySQL database credentials
nano .env
```

Configure your database connection in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bittytorrent
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

### 3. Initialize Database

```bash
php bin/init-database.php
```

### 4. Set Permissions

```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
chmod 600 .env
```

### 5. Configure Web Server

#### Apache (with .htaccess files provided)

Point document root to `public/` directory:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/Bittytorrent/public
    
    <Directory /path/to/Bittytorrent/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
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

### 6. Enable HTTPS (Production)

```bash
# Using Let's Encrypt
sudo certbot --apache  # or --nginx
```

Update `.env`:
```env
APP_URL=https://your-domain.com
```

## Default Credentials

**Username:** admin  
**Password:** admin123

⚠️ **Change immediately after first login!**

## Testing

Visit your domain and you should see the Bittytorrent homepage.

## Troubleshooting

### 500 Internal Server Error
- Check error log: `var/logs/app.log`
- Verify file permissions
- Ensure PHP extensions are installed

### Database Connection Error
- Run: `php bin/init-database.php`
- Check database file exists in `var/database/`

### Missing Dependencies
- Run: `composer install`

## Maintenance

### Backup Database
```bash
cp var/database/bittytorrent.sqlite var/database/bittytorrent.backup.sqlite
```

### View Logs
```bash
tail -f var/logs/app.log
```

### Clean Old Peers
Add to crontab:
```bash
# Clean old peers every hour
0 * * * * cd /path/to/Bittytorrent && php -r "require 'vendor/autoload.php'; \$app = Bittytorrent\Application::getInstance(); \$tracker = new Bittytorrent\Service\Tracker(); \$tracker->cleanOldPeers();"
```

## Support

For issues, see SETUP.md or create an issue on GitHub.
