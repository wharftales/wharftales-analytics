# 🚨 Diagnosing 500 Internal Server Error

## All files returning 500 error? Follow these steps:

---

## Step 1: Test PHP Itself

### Visit: `info.php`
```
https://yourdomain.com/analytics/info.php
```

**Expected:** Full PHP info page  
**If 500 error:** PHP is not working at all - check server configuration

---

## Step 2: Test Simple PHP

### Visit: `test.php`
```
https://yourdomain.com/analytics/test.php
```

**Expected:** "✅ PHP is working!" message  
**If 500 error:** Check server error logs (see below)

---

## Step 3: Check Server Error Logs

### Apache Error Log Locations:

**Ubuntu/Debian:**
```bash
tail -50 /var/log/apache2/error.log
```

**CentOS/RHEL:**
```bash
tail -50 /var/log/httpd/error_log
```

**cPanel:**
```
/usr/local/apache/logs/error_log
or via cPanel > Error Log
```

**Plesk:**
```
/var/www/vhosts/yourdomain.com/logs/error_log
```

---

## Common 500 Error Causes

### 1. .htaccess Syntax Error

**Check:**
```bash
# Temporarily rename .htaccess
mv .htaccess .htaccess.bak

# Try visiting test.php again
# If it works, .htaccess is the problem
```

**Fix:**
```apache
# Make sure .htaccess has correct syntax
# Check for typos in RewriteRule
# Ensure mod_rewrite is enabled
```

---

### 2. PHP Version Too Old

**Check:**
```bash
php -v
```

**Required:** PHP 7.4 or higher

**Fix:**
```bash
# Ubuntu/Debian
sudo apt-get install php8.1

# Or use cPanel/Plesk to select PHP version
```

---

### 3. Missing PHP Extensions

**Check in error log for:**
```
Call to undefined function PDO::__construct
```

**Fix:**
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3 php-pdo
sudo systemctl restart apache2

# CentOS/RHEL
sudo yum install php-pdo
sudo systemctl restart httpd
```

---

### 4. Permissions Issue

**Check:**
```bash
ls -la /path/to/analytics/
```

**Should see:**
```
drwxr-xr-x  (755 for directories)
-rw-r--r--  (644 for files)
```

**Fix:**
```bash
# Fix permissions
find /path/to/analytics -type d -exec chmod 755 {} \;
find /path/to/analytics -type f -exec chmod 644 {} \;
```

---

### 5. Suhosin/Security Module Blocking

**Check error log for:**
```
ALERT - canary mismatch
suhosin
```

**Fix:**
```bash
# Disable suhosin or adjust settings
# In php.ini:
suhosin.executor.disable_eval = off
```

---

### 6. Memory Limit Too Low

**Check error log for:**
```
Allowed memory size exhausted
```

**Fix:**
```php
# In php.ini or .htaccess:
php_value memory_limit 128M
```

---

### 7. Session Directory Not Writable

**Check error log for:**
```
session_start(): Failed to read session data
```

**Fix:**
```bash
# Check session save path
php -i | grep session.save_path

# Make it writable
chmod 1777 /var/lib/php/sessions
```

---

## Diagnostic File Tests

### Test 1: info.php (Simplest)
```
Just shows phpinfo()
If this fails: PHP is completely broken
```

### Test 2: test.php (Simple)
```
No dependencies, just basic checks
If this fails: Check error logs
```

### Test 3: debug.php (Detailed)
```
Loads config, checks database
If this fails: Check what debug.php shows
```

### Test 4: health.php (Requirements)
```
Full system check
If this fails: See error logs
```

---

## Quick Fixes to Try

### 1. Disable .htaccess temporarily
```bash
mv .htaccess .htaccess.bak
```

### 2. Enable error display
Create `show-errors.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Errors enabled. Now visit other pages.";
```

### 3. Check file ownership
```bash
# Make sure web server owns files
chown -R www-data:www-data /path/to/analytics

# Or for Apache:
chown -R apache:apache /path/to/analytics
```

### 4. Increase PHP limits
Add to `.htaccess`:
```apache
php_value memory_limit 128M
php_value max_execution_time 300
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

---

## What to Check in Error Logs

Look for these patterns:

### Pattern 1: Module not found
```
PHP Fatal error: Call to undefined function
```
→ Missing PHP extension

### Pattern 2: Permission denied
```
Permission denied
```
→ File/directory permissions

### Pattern 3: Syntax error
```
Parse error: syntax error
```
→ PHP file has syntax error

### Pattern 4: Memory exhausted
```
Allowed memory size of X bytes exhausted
```
→ Increase memory_limit

### Pattern 5: Session error
```
session_start(): Failed
```
→ Session directory not writable

---

## Emergency Bypass

If everything fails, create `emergency.php`:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Emergency Diagnostic</h1>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "\nLoaded Extensions:\n";
print_r(get_loaded_extensions());
echo "\nPHP Info:\n";
phpinfo();
echo "</pre>";
```

---

## Files to Check (in order)

1. ✅ `info.php` - Does PHP work at all?
2. ✅ `test.php` - Does simple PHP work?
3. ✅ `debug.php` - What's the actual problem?
4. ✅ `health.php` - Are requirements met?
5. ✅ `index.php` - Does the app work?

---

## Most Likely Causes (in order)

1. **mod_rewrite not enabled** → Disable .htaccess temporarily
2. **PDO SQLite not installed** → Install php-sqlite3
3. **Wrong PHP version** → Upgrade to PHP 7.4+
4. **Permissions issue** → Fix with chmod
5. **.htaccess syntax error** → Check RewriteRule syntax

---

## Get Help

When asking for help, provide:

1. **PHP Version:** `php -v`
2. **Server:** Apache/Nginx/etc
3. **OS:** Ubuntu/CentOS/etc
4. **Error log:** Last 20 lines
5. **What test.php shows:** If anything
6. **Hosting:** Shared/VPS/Dedicated

---

**Start with `info.php` and work your way through the tests!** 🔍
