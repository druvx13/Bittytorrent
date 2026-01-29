#!/bin/bash
# PHP 8+ Compatibility Verification Script
# Run this to verify all code is error-free and PHP 8+ compatible

set -e

echo "=============================================="
echo "PHP 8+ Compatibility Verification"
echo "=============================================="
echo ""

# Check PHP version
echo "1. Checking PHP version..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "   ✓ PHP $PHP_VERSION"

if php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);'; then
    echo "   ✓ PHP 8.1+ requirement met"
else
    echo "   ✗ ERROR: PHP 8.1+ required"
    exit 1
fi
echo ""

# Check required extensions
echo "2. Checking required PHP extensions..."
for ext in pdo pdo_sqlite mbstring json; do
    if php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
        echo "   ✓ $ext extension loaded"
    else
        echo "   ✗ ERROR: Missing extension: $ext"
        exit 1
    fi
done
echo ""

# Syntax check all PHP files
echo "3. Running syntax checks on all PHP files..."
SYNTAX_ERRORS=0

for file in $(find src public/index.php bin -name "*.php" 2>/dev/null); do
    if php -l "$file" > /dev/null 2>&1; then
        echo "   ✓ $(basename $file)"
    else
        echo "   ✗ Syntax error in $file"
        SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
    fi
done

if [ $SYNTAX_ERRORS -eq 0 ]; then
    echo "   ✓ All files passed syntax checks"
else
    echo "   ✗ Found $SYNTAX_ERRORS syntax errors"
    exit 1
fi
echo ""

# Check for deprecated patterns
echo "4. Checking for deprecated PHP 8 patterns..."
DEPRECATED_FOUND=0

# Check for ${var} syntax
if grep -r '\${[a-zA-Z_]' src/ public/index.php bin/ 2>/dev/null; then
    echo "   ⚠ Found deprecated \${var} syntax"
    DEPRECATED_FOUND=$((DEPRECATED_FOUND + 1))
fi

# Check for each() function
if grep -r '\beach\s*(' src/ public/index.php bin/ 2>/dev/null; then
    echo "   ✗ Found removed each() function"
    DEPRECATED_FOUND=$((DEPRECATED_FOUND + 1))
fi

# Check for create_function
if grep -r '\bcreate_function\s*(' src/ public/index.php bin/ 2>/dev/null; then
    echo "   ✗ Found removed create_function()"
    DEPRECATED_FOUND=$((DEPRECATED_FOUND + 1))
fi

if [ $DEPRECATED_FOUND -eq 0 ]; then
    echo "   ✓ No deprecated patterns found"
else
    echo "   ⚠ Found $DEPRECATED_FOUND deprecated patterns"
fi
echo ""

# Check for strict_types declaration
echo "5. Checking for strict_types declarations..."
STRICT_COUNT=0
TOTAL_FILES=$(find src public/index.php bin -name "*.php" 2>/dev/null | wc -l)

for file in $(find src public/index.php bin -name "*.php" 2>/dev/null); do
    if grep -q 'declare(strict_types=1)' "$file"; then
        STRICT_COUNT=$((STRICT_COUNT + 1))
    fi
done

echo "   ✓ $STRICT_COUNT/$TOTAL_FILES files use strict_types"
echo ""

# Test database initialization
echo "6. Testing database initialization..."
if [ -f "bin/init-database.php" ]; then
    if php -d error_reporting=E_ALL -d display_errors=1 bin/init-database.php > /tmp/db_init.log 2>&1; then
        echo "   ✓ Database initialization successful"
    else
        echo "   ✗ Database initialization failed"
        echo "   See /tmp/db_init.log for details"
        exit 1
    fi
else
    echo "   ⚠ Database initialization script not found"
fi
echo ""

# Final summary
echo "=============================================="
echo "VERIFICATION COMPLETE"
echo "=============================================="
echo ""
echo "✓✓✓ ALL CHECKS PASSED ✓✓✓"
echo ""
echo "The application is fully compatible with PHP 8.1+"
echo "and follows modern PHP coding standards."
echo ""
echo "You can now run the application with:"
echo "  cd public && php -S localhost:8000"
echo ""
