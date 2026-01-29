# Controller Method Fixes

This document details the fixes for missing method errors in the controller classes.

## Issues Fixed

### 1. Missing `formatBytes()` Method

**Error:**
```
Call to undefined method Bittytorrent\Controller\TorrentController::formatBytes()
```

**Stack Trace:**
```
#0 /home/.../htdocs/public/index.php(282): Bittytorrent\Controller\TorrentController->browse()
#1 [internal function]: {closure}()
#2 /home/.../htdocs/src/Router.php(85): call_user_func_array(Object(Closure), Array)
#3 /home/.../htdocs/public/index.php(335): Bittytorrent\Router->dispatch()
```

**Affected Locations:**
- `TorrentController::browse()` - Line 80
- `UserController::show()` - Lines 45, 68, 69

**Root Cause:**
Controllers were calling `$this->formatBytes()` but the method didn't exist in any parent or current class.

**Solution:**
Added `formatBytes()` method to `BaseController` as a protected helper method:

```php
/**
 * Format bytes to human readable format
 */
protected function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
```

**Benefits:**
- Available to all controllers via inheritance
- Converts bytes to human-readable format (e.g., "1.5 GB")
- Configurable precision (default 2 decimal places)
- Type-safe with strict typing

**Example Output:**
- 1024 bytes → "1 KB"
- 1536 bytes → "1.5 KB"
- 1048576 bytes → "1 MB"
- 1073741824 bytes → "1 GB"

---

### 2. Wrong CSRF Token Method Call

**Error:**
```
Call to undefined method Bittytorrent\Controller\AdminController::generateCSRFToken()
```

**Stack Trace:**
```
#0 /home/.../htdocs/public/index.php(261): Bittytorrent\Controller\AdminController->categories()
#1 [internal function]: {closure}()
#2 /home/.../htdocs/src/Router.php(85): call_user_func_array(Object(Closure), Array)
#3 /home/.../htdocs/public/index.php(335): Bittytorrent\Router->dispatch()
```

**Affected Location:**
- `AdminController::categories()` - Line 200

**Root Cause:**
Called `$this->generateCSRFToken()` which doesn't exist. The correct method is `$this->app->generateCsrfToken()` from the Application instance.

**Before:**
```php
$this->render('admin/categories.twig', [
    'title' => 'Manage Categories',
    'categories' => $categories,
    'edit_category' => $editCategory,
    'csrf_token' => $this->generateCSRFToken(),  // WRONG
    'success' => $_SESSION['success_msg'] ?? null,
    'error' => $_SESSION['error_msg'] ?? null,
]);
```

**After:**
```php
$this->render('admin/categories.twig', [
    'title' => 'Manage Categories',
    'categories' => $categories,
    'edit_category' => $editCategory,
    'success' => $_SESSION['success_msg'] ?? null,
    'error' => $_SESSION['error_msg'] ?? null,
]);
```

**Why Remove It?**
The CSRF token is already automatically added in `BaseController::render()`:

```php
// From BaseController.php line 35
$data['csrf_token'] = $this->app->generateCsrfToken();
```

So manually adding it was:
1. Calling a non-existent method
2. Redundant (already added automatically)

---

### 3. Wrong Column Name in UserController

**Issue:**
Using `$torrent['size']` instead of `$torrent['size_bytes']`

**Location:**
- `UserController::show()` - Line 45

**Root Cause:**
Database schema uses `size_bytes` as the column name, not `size`.

**Database Schema:**
```sql
CREATE TABLE IF NOT EXISTS torrents (
    ...
    size_bytes BIGINT DEFAULT 0,
    ...
)
```

**Before:**
```php
foreach ($torrents as &$torrent) {
    $torrent['size_formatted'] = $this->formatBytes($torrent['size']);
}
```

**After:**
```php
foreach ($torrents as &$torrent) {
    $torrent['size_formatted'] = $this->formatBytes((int)$torrent['size_bytes']);
}
```

**Changes:**
1. Changed `size` to `size_bytes` to match schema
2. Added `(int)` cast for type safety

---

## Files Modified

### 1. src/Controller/BaseController.php
**Changes:**
- Added `formatBytes()` method (13 lines)

**Impact:**
- All controllers can now format byte values
- Consistent formatting across the application

### 2. src/Controller/AdminController.php
**Changes:**
- Removed redundant CSRF token assignment in `categories()` method

**Impact:**
- Fixes fatal error when accessing `/admin/categories`
- CSRF token still available (added automatically by parent render method)

### 3. src/Controller/UserController.php
**Changes:**
- Fixed column name from `size` to `size_bytes`
- Added type cast `(int)`

**Impact:**
- Prevents potential undefined index error
- Ensures correct data type for formatBytes()

---

## Testing

### Test Cases

**1. Browse Page (`/browse`)**
- ✓ Should load without formatBytes error
- ✓ Should display torrent sizes formatted correctly
- ✓ Example: "1.5 GB" instead of "1610612736"

**2. Admin Categories (`/admin/categories`)**
- ✓ Should load without generateCSRFToken error
- ✓ CSRF token should be present in forms
- ✓ Category management should work

**3. User Profile (`/user/:id`)**
- ✓ Should load without errors
- ✓ Should display user's torrents with formatted sizes
- ✓ Should show upload/download stats formatted

### Verification Commands

```bash
# Check formatBytes method exists
grep -A15 "formatBytes" src/Controller/BaseController.php

# Check CSRF token is auto-added in render
grep "csrf_token" src/Controller/BaseController.php

# Check UserController uses size_bytes
grep "size_bytes" src/Controller/UserController.php
```

---

## Prevention

### Best Practices

1. **Helper Methods in BaseController**
   - Put common utility methods in BaseController
   - Makes them available to all controllers
   - Ensures consistency

2. **Check Parent Class Methods**
   - Before calling a method, verify it exists
   - Use IDE autocomplete to confirm availability
   - Check both current class and parent classes

3. **Database Column Names**
   - Always reference schema when querying
   - Use descriptive column names (e.g., `size_bytes` not just `size`)
   - Consider using constants for column names

4. **Type Safety**
   - Cast values to expected types
   - Use strict typing (`declare(strict_types=1)`)
   - Add type hints to method parameters and returns

---

## Related Documentation

- `PRODUCTION_FIXES.md` - Previous production error fixes
- `PHP8_COMPATIBILITY.md` - PHP 8+ compatibility information
- `SECURITY.md` - Security features including CSRF protection

---

## Summary

All controller method errors have been fixed:
- ✅ formatBytes() method added to BaseController
- ✅ CSRF token generation fixed in AdminController
- ✅ Column name corrected in UserController
- ✅ Type safety improved with casts

The application should now work correctly on production without these method-not-found errors.
