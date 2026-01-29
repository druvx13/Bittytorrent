# Vendor Directory Management

## Why vendor/ is included in the repository

Normally, the `vendor/` directory (containing Composer dependencies) is NOT included in Git repositories. However, **for ease of deployment**, we've included it in this repository.

### Benefits

✅ **No Composer required on production server**
- Just upload files and it works
- Perfect for shared hosting without SSH
- Works on free hosting platforms

✅ **One-step deployment**
- No need to run `composer install`
- No need to install Composer on server
- Faster deployment process

✅ **Guaranteed compatibility**
- Dependencies are tested and known to work
- No version conflicts on production

### Trade-offs

⚠️ **Larger repository size**
- Git repository is ~5-10 MB larger
- Slower git operations (clone, pull)

⚠️ **Manual updates required**
- When dependencies update, need to commit vendor/
- More commits to track

## For Developers

If you're developing and want to update dependencies:

### Updating Dependencies

1. **Update composer.json** (if adding/removing packages)

2. **Run composer update:**
```bash
composer update --no-dev --optimize-autoloader
```

3. **Test the application** to ensure everything works

4. **Commit the changes:**
```bash
git add vendor/ composer.lock
git commit -m "Update dependencies"
git push
```

### Adding New Dependencies

```bash
# Add new package
composer require vendor/package --no-dev

# Commit changes
git add composer.json composer.lock vendor/
git commit -m "Add vendor/package dependency"
git push
```

### Checking for Security Updates

```bash
# Check for security issues
composer audit

# Update if needed
composer update --no-dev --optimize-autoloader

# Commit if updated
git add vendor/ composer.lock
git commit -m "Security update: dependencies"
git push
```

## For Deployers

When deploying this application:

### Option 1: Direct File Upload (Recommended)

Simply upload ALL files including the `vendor/` directory:

```
Your files to upload:
├── public/           ← Upload this
├── src/              ← Upload this
├── vendor/           ← Upload this (IMPORTANT!)
├── views/            ← Upload this
├── config/           ← Upload this
├── bin/              ← Upload this
├── var/              ← Upload this
├── .env              ← Create/edit this on server
├── .htaccess         ← Upload this
└── composer.json     ← Upload this
```

**No need to run composer install!**

### Option 2: Git Clone

```bash
# Clone the repo
git clone https://github.com/druvx13/Bittytorrent.git
cd Bittytorrent

# vendor/ is already included, ready to use!
cp .env.example .env
# Edit .env with your settings
php bin/init-database.php
```

### Verification

After deployment, verify vendor/ is present:

```bash
# Check if vendor directory exists
ls -la vendor/

# Check if autoload works
php -r "require 'vendor/autoload.php'; echo 'Autoload OK';"
```

If you get "vendor/autoload.php not found", the vendor directory wasn't uploaded correctly.

## If You Want to Exclude vendor/

If you prefer the traditional approach (exclude vendor/ from Git):

1. **Edit `.gitignore`:**
```bash
# Uncomment this line:
/vendor/
```

2. **Remove vendor from Git:**
```bash
git rm -r --cached vendor/
git commit -m "Remove vendor directory from Git"
git push
```

3. **Update documentation** to require `composer install` during deployment

4. **On each deployment:**
```bash
git pull
composer install --no-dev --optimize-autoloader
```

## Best Practices

### For Production Deployments

- ✅ Use `--no-dev` flag (excludes development dependencies)
- ✅ Use `--optimize-autoloader` flag (faster autoloading)
- ✅ Keep vendor/ in sync with composer.lock
- ✅ Test after updating dependencies

### For Development

- ✅ Run `composer install` (includes dev dependencies for testing)
- ✅ Use `composer update` sparingly (only when needed)
- ✅ Always commit composer.lock changes
- ✅ Check for security updates monthly

### Security

- ✅ Run `composer audit` before deployments
- ✅ Update dependencies with security fixes immediately
- ✅ Subscribe to security advisories for your dependencies
- ✅ Use specific version constraints in composer.json

## Troubleshooting

### "Class not found" errors after deployment

**Solution:**
```bash
composer dump-autoload --optimize --no-dev
git add vendor/
git commit -m "Rebuild autoloader"
```

### Vendor directory is huge

**Solution:**
Remove development dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

This reduces vendor/ size by excluding PHPUnit, code sniffers, etc.

### Merge conflicts in vendor/

**Solution:**
```bash
# Take their version
git checkout --theirs vendor/
composer install --no-dev --optimize-autoloader
git add vendor/
```

## FAQ

**Q: Should I include vendor/ in my repository?**

A: It depends on your deployment scenario:
- **Yes** if: Shared hosting, no SSH, no Composer available
- **No** if: VPS with full control, automated deployments, CI/CD

**Q: How often should I update dependencies?**

A: 
- Security updates: Immediately
- Minor updates: Monthly
- Major updates: Quarterly (test thoroughly)

**Q: What if vendor/ is too large for my Git hosting?**

A: Use Git LFS or switch to traditional approach (exclude vendor/)

**Q: Can I use this with Docker?**

A: Yes, but Dockerfile should run `composer install` - don't rely on vendor/ in image

## Summary

✅ **vendor/ IS included in this repository**
✅ **No need to run composer install for basic deployment**
✅ **Just upload files and it works**
✅ **Update dependencies when needed and commit changes**

This approach prioritizes ease of deployment over Git best practices, making it ideal for users who need a simple, working application without complex build steps.
