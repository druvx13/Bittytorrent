# 🚀 Bittytorrent - Modern BitTorrent Tracker

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-green.svg)](LICENSE)

A modern, secure PHP BitTorrent tracker rebuilt with contemporary technologies and best practices.

![Bittytorrent Homepage](https://i.imgur.com/pYv0Q9b.png)

---

## 📋 Table of Contents

- [What is BitTorrent?](#what-is-bittorrent)
- [What is Bittytorrent?](#what-is-bittytorrent)
- [Features](#features)
- [Quick Start](#quick-start)
- [Documentation](#documentation)
- [Requirements](#requirements)
- [Support](#support)
- [Security](#security)
- [License](#license)

---

## 🎯 What is BitTorrent?

BitTorrent is a peer-to-peer file sharing protocol that enables efficient distribution of large files. It requires:

1. **BitTorrent Client** - Software to download/upload files
2. **BitTorrent Tracker** - Server that coordinates peer connections
3. **Torrent File** - Metadata file describing the content
4. **Content** - The actual files being shared

The tracker helps clients find other peers sharing the same files, enabling distributed downloading.

## 📦 What is Bittytorrent?

Bittytorrent is a **modern, secure PHP BitTorrent tracker** that allows you to deploy a complete torrent-sharing website. It provides:

- A functional BitTorrent tracker (announce/scrape endpoints)
- User management with secure authentication
- Torrent upload and management interface
- Category-based organization
- Search and filtering capabilities
- Modern, responsive web interface

## ✨ Features

### Core Functionality
- 🎯 **BitTorrent Tracker** - Fully functional announce and scrape endpoints
- 👥 **User Management** - Registration, authentication, profiles, and roles
- 📤 **Torrent Operations** - Upload, view, search, download, and categorize
- 🔍 **Search & Filter** - Find torrents by category, keywords, and more

### Technology Stack
- ⚡ **PHP 8.1+** - Modern PHP with strict typing and PSR-4 autoloading
- 🗄️ **SQLite Database** - Lightweight, portable database with PDO
- 🎨 **Twig Templates** - Clean, secure templating engine
- 📱 **Bootstrap 5** - Responsive, mobile-friendly UI
- 📦 **Composer** - Modern dependency management

### Security Features
- 🔐 **Argon2id Password Hashing** - Industry-standard password security
- 🛡️ **CSRF Protection** - Token-based protection on all forms
- 🔒 **Security Headers** - CSP, X-Frame-Options, and more
- ✅ **Input Validation** - Comprehensive sanitization and validation
- 📝 **Prepared Statements** - SQL injection protection

### Developer Features
- 📚 **Clean Architecture** - MVC pattern with controllers, models, services
- 🔧 **PSR Standards** - PSR-4 autoloading, PSR-3 logging
- 📊 **Comprehensive Logging** - Monolog integration for debugging
- 🎯 **Type Safety** - Strict typing throughout the codebase

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.1 or higher
- Composer
- Web server (Apache with mod_rewrite OR Nginx)
- Basic command line knowledge

### Installation (5 minutes)

```bash
# 1. Clone the repository
git clone https://github.com/druvx13/Bittytorrent.git
cd Bittytorrent

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Configure environment
cp .env.example .env
# Edit .env with your settings (optional for development)

# 4. Initialize database
php bin/init-database.php

# 5. Set permissions
chmod -R 755 var/
chmod -R 755 public/uploads/

# 6. Start development server (or configure Apache/Nginx)
cd public && php -S localhost:8000
```

**That's it!** Visit `http://localhost:8000` in your browser.

**Default Admin Login:**
- Username: `admin`
- Password: `admin123`

⚠️ **Change the default password immediately!**

---

## 📖 Documentation

We provide comprehensive documentation to help you get started:

- **[GETTING_STARTED.md](GETTING_STARTED.md)** - Complete beginner's guide with detailed explanations
- **[SETUP.md](SETUP.md)** - Detailed installation and configuration guide
- **[INSTALL.md](INSTALL.md)** - Quick deployment reference
- **[SECURITY.md](SECURITY.md)** - Security features and best practices
- **[PHP8_COMPATIBILITY.md](PHP8_COMPATIBILITY.md)** - PHP 8+ compatibility verification

### Quick Links

- 🆕 **New to Bittytorrent?** → Start with [GETTING_STARTED.md](GETTING_STARTED.md)
- ⚙️ **Production Deployment?** → See [SETUP.md](SETUP.md)
- 🔧 **Quick Setup?** → Check [INSTALL.md](INSTALL.md)
- 🔒 **Security Info?** → Read [SECURITY.md](SECURITY.md)

---

## 💻 Requirements

### Minimum Requirements

- **PHP:** 8.1 or higher
- **Extensions:** PDO, PDO_SQLite, mbstring, JSON
- **Web Server:** Apache (with mod_rewrite) or Nginx
- **Composer:** Latest version
- **Disk Space:** ~50 MB for application + space for torrents

### Recommended

- PHP 8.2 or 8.3 for best performance
- HTTPS/SSL certificate for production
- Linux/Unix server environment
- At least 512 MB RAM
- Cron job for peer cleanup

---

## 🤝 Support

### Getting Help

- 📖 **Documentation** - Check our comprehensive guides first
- 🐛 **Issues** - [Open an issue](https://github.com/druvx13/Bittytorrent/issues) on GitHub
- 💬 **Discussions** - Ask questions in [GitHub Discussions](https://github.com/druvx13/Bittytorrent/discussions)

### Troubleshooting

Common issues and solutions:

**500 Internal Server Error**
- Check `var/logs/app.log` for errors
- Verify file permissions (755 for directories, 644 for files)
- Ensure all PHP extensions are installed

**Database Errors**
- Run `php bin/init-database.php` to reinitialize
- Check that `var/database/` is writable

**Missing Dependencies**
- Run `composer install` to install all dependencies

**For more:** See [GETTING_STARTED.md](GETTING_STARTED.md) troubleshooting section

---

## 🔒 Security

Security is a top priority. This modernized version includes:

- ✅ Argon2id password hashing
- ✅ CSRF token protection
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (Twig auto-escaping)
- ✅ Security headers (CSP, X-Frame-Options, etc.)
- ✅ Input validation and sanitization
- ✅ Secure session management

**Found a vulnerability?** Please report it privately via GitHub Security Advisories.

### Hall of Fame

Security researchers who helped improve the original Bittytorrent:
- [Bouneh](https://twitter.com/BugBouneh) - Stored XSS
- [Memon Irshad](https://twitter.com/irshad9998) - XSS
- [Taha Smily](https://twitter.com/TahakhanTaha) - XSS

---

## 📄 License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

**Technologies Used:**
- [Twig](https://twig.symfony.com/) - Templating engine
- [Bootstrap 5](https://getbootstrap.com/) - UI framework
- [Monolog](https://github.com/Seldaek/monolog) - Logging library
- [Symfony Components](https://symfony.com/) - HTTP foundation
- [PHP dotenv](https://github.com/vlucas/phpdotenv) - Environment management

**Original Bittytorrent** by [@atmoner](https://github.com/atmoner)

---

<div align="center">

**Made with ❤️ for the BitTorrent community**

⭐ Star this repo if you find it useful!

[Report Bug](https://github.com/druvx13/Bittytorrent/issues) · [Request Feature](https://github.com/druvx13/Bittytorrent/issues) · [Contribute](https://github.com/druvx13/Bittytorrent/pulls)

</div>  
 
 


 

