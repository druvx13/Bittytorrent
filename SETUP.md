# Bittytorrent - Modern BitTorrent Tracker

A modern, secure PHP BitTorrent tracker rebuilt with contemporary technologies and best practices.

## Features

- **Modern PHP 8.1+** with strict typing and PSR-4 autoloading
- **SQLite Database** with PDO and prepared statements for security
- **Twig Templating Engine** for clean, maintainable views
- **Bootstrap 5** for responsive, modern UI
- **Secure Authentication** using Argon2id password hashing
- **CSRF Protection** on all forms
- **Security Headers** (CSP, X-Frame-Options, etc.)
- **BitTorrent Tracker** with announce and scrape endpoints
- **Comprehensive Logging** with Monolog
- **Clean Architecture** with controllers, models, and services

## Requirements

- PHP 8.1 or higher
- PHP Extensions: PDO, SQLite3, mbstring, JSON
- Apache with mod_rewrite (or Nginx with appropriate configuration)
- Composer

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/druvx13/Bittytorrent.git
cd Bittytorrent
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Edit `.env` and configure your settings:

```env
APP_NAME="Bittytorrent"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

DB_CONNECTION=sqlite
DB_DATABASE=var/database/bittytorrent.sqlite
```

### 4. Initialize Database

```bash
php bin/init-database.php
```

This will create the SQLite database with the initial schema and a default admin account.

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change the default admin password immediately after first login!

### 5. Set Permissions

```bash
chmod -R 755 var/
chmod -R 755 public/uploads/
```

### 6. Configure Web Server

#### Apache

The repository includes `.htaccess` files for Apache. Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Point your document root to the `public/` directory.

#### Nginx

Example Nginx configuration:

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

### 7. Enable HTTPS (Recommended)

For production, enable HTTPS:

1. Obtain an SSL certificate (e.g., Let's Encrypt)
2. Update `.env`: Set `APP_URL` to use `https://`
3. Uncomment HTTPS redirect in `.htaccess` or configure in Nginx

## Usage

### Accessing the Application

Navigate to your configured domain in a web browser. You should see the homepage.

### User Management

- **Registration:** Users can register at `/register`
- **Login:** Users can login at `/login`
- **Admin Panel:** Admins can access admin features (future implementation)

### Uploading Torrents

1. Login to your account
2. Click "Upload" in the navigation
3. Select a .torrent file
4. Fill in title, category, and description
5. Submit the form

### Tracker Endpoints

The tracker provides two main endpoints:

- **Announce:** `/announce?info_hash=...&peer_id=...&port=...&uploaded=...&downloaded=...&left=...`
- **Scrape:** `/scrape?info_hash=...`

These endpoints are compatible with standard BitTorrent clients.

## Security Features

### Password Hashing

User passwords are hashed using Argon2id with the following parameters:
- Memory cost: 65536 KB
- Time cost: 4
- Parallelism: 1

### CSRF Protection

All forms include CSRF tokens that are validated server-side.

### Input Validation

All user input is sanitized and validated before processing.

### Security Headers

The application sets the following security headers:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy` (in production)

### Session Security

Sessions are configured with:
- HttpOnly cookies
- Strict SameSite policy
- Periodic session ID regeneration

## Development

### Running Locally

For development, you can use PHP's built-in server:

```bash
cd public
php -S localhost:8000
```

Access the application at `http://localhost:8000`

### Enabling Debug Mode

In `.env`, set:

```env
APP_ENV=development
APP_DEBUG=true
```

This will enable detailed error messages and disable template caching.

### Database Management

To reset the database:

```bash
php bin/init-database.php
```

This will recreate the database and reset all data.

## File Structure

```
Bittytorrent/
├── bin/                    # Command-line scripts
│   └── init-database.php   # Database initialization
├── config/                 # Configuration files
│   └── schema.sql          # Database schema
├── public/                 # Public web root
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   ├── uploads/            # Uploaded torrent files
│   ├── .htaccess           # Apache configuration
│   └── index.php           # Application entry point
├── src/                    # Application source code
│   ├── Controller/         # Controllers
│   ├── Model/              # Data models
│   ├── Service/            # Business logic services
│   ├── Application.php     # Core application class
│   └── Router.php          # Routing system
├── var/                    # Variable data
│   ├── cache/              # Cache files
│   ├── database/           # SQLite database
│   └── logs/               # Log files
├── vendor/                 # Composer dependencies
├── views/                  # Twig templates
│   ├── auth/               # Authentication views
│   ├── error/              # Error pages
│   ├── home/               # Homepage views
│   ├── layout/             # Layout templates
│   └── torrent/            # Torrent views
├── .env                    # Environment configuration
├── .env.example            # Example environment file
├── .gitignore              # Git ignore rules
├── .htaccess               # Root Apache configuration
├── composer.json           # PHP dependencies
└── README.md               # This file
```

## Troubleshooting

### Permission Errors

If you encounter permission errors, ensure the web server user has write access to:
- `var/cache/`
- `var/logs/`
- `var/database/`
- `public/uploads/`

### Database Errors

If the database file is corrupted or missing:

```bash
php bin/init-database.php
```

### 500 Internal Server Error

1. Check the error log: `var/logs/app.log`
2. Ensure all dependencies are installed: `composer install`
3. Verify file permissions
4. Enable debug mode in `.env` to see detailed errors

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Write clear commit messages
5. Submit a pull request

## License

This project is licensed under the GNU General Public License v3.0 or later. See the LICENSE file for details.

## Security

If you discover a security vulnerability, please email the maintainer directly rather than using the issue tracker.

## Credits

- Original Bittytorrent by [@atmoner](https://github.com/atmoner)
- Modernization and security improvements

## Support

For issues and questions, please use the GitHub issue tracker.
