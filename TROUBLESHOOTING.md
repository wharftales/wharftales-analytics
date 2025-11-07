# 🔧 Troubleshooting 500 Errors

## Problem: 500 Internal Server Error on Fresh Install

If you're getting 500 errors on `index.php` or `health.php`, use these diagnostic tools.

---

## 🚨 Quick Diagnosis

### Step 1: Test Basic PHP
Visit: `https://yourdomain.com/analytics/test.php`

**Should show:**
```
PHP is working!
PHP Version: 8.x.x
PDO SQLite: YES
Current directory: /path/to/analytics
Data directory exists: NO (or YES)
Parent writable: YES
```

**If you see this:** PHP is working, continue to Step 2  
**If you get 500 error:** PHP configuration issue, check server error logs

---

### Step 2: Detailed Debug
Visit: `https://yourdomain.com/analytics/debug.php`

**This will show:**
- ✅ PHP version and extensions
- ✅ File system permissions
- ✅ Config file status
- ✅ Database status
- ✅ Actual error messages

**Look for red ✗ marks** and fix those issues first.

---

### Step 3: Health Check
Visit: `https://yourdomain.com/analytics/health.php`

**This will show:**
- System requirements check
- What needs to be fixed
- Specific instructions

---

## 🔍 Common Issues & Solutions

### Issue 1: PDO SQLite Not Installed
**Symptom:** `PDO SQLite: NO` in test.php

**Solution:**
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3
sudo systemctl restart apache2

# CentOS/RHEL
sudo yum install php-pdo
sudo systemctl restart httpd

# Check php.ini
extension=pdo_sqlite
```

---

### Issue 2: Permission Denied
**Symptom:** `Parent writable: NO` in test.php

**Solution:**
```bash
# Make directory writable
chmod 755 /path/to/analytics

# Or create data directory manually
mkdir data
chmod 755 data
```

---

### Issue 3: Session Errors
**Symptom:** "Headers already sent" in error log

**Solution:** Already fixed in config.php with `@session_start()`

---

### Issue 4: .htaccess Not Working
**Symptom:** 404 errors on clean URLs

**Solution:**
```apache
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check AllowOverride in Apache config
<Directory /path/to/analytics>
    AllowOverride All
</Directory>
```

---

### Issue 5: Database Can't Be Created
**Symptom:** Error about database file

**Solution:**
```bash
# Create data directory
mkdir data
chmod 755 data

# Or make parent writable
chmod 755 /path/to/analytics
```

---

## 📋 Diagnostic Files Created

### 1. `test.php` - Simplest Test
- Just checks if PHP works
- Shows basic info
- No dependencies

### 2. `debug.php` - Detailed Diagnostics
- Comprehensive system check
- Shows actual errors
- Tests config loading
- Tests database connection

### 3. `health.php` - Pre-Installation Check
- Checks all requirements
- Shows what needs fixing
- Provides specific instructions

---

## 🔧 Manual Checks

### Check PHP Error Log
```bash
# Find error log location
php -i | grep error_log

# View last 50 lines
tail -50 /path/to/error.log

# Watch in real-time
tail -f /path/to/error.log
```

### Check Apache Error Log
```bash
# Ubuntu/Debian
tail -50 /var/log/apache2/error.log

# CentOS/RHEL
tail -50 /var/log/httpd/error_log
```

### Test PHP CLI
```bash
cd /path/to/analytics
php test.php
```

---

## 🎯 Installation Flow

### Correct Flow:
```
1. Upload files
   ↓
2. Visit test.php → Should work
   ↓
3. Visit debug.php → Check for issues
   ↓
4. Fix any red ✗ issues
   ↓
5. Visit health.php → Should show "Ready"
   ↓
6. Visit index.php → Redirects to install.php
   ↓
7. Complete setup → Success!
```

---

## 🚀 Quick Fix Checklist

- [ ] PHP 7.4+ installed
- [ ] PDO SQLite extension enabled
- [ ] Directory writable (755 permissions)
- [ ] .htaccess file uploaded
- [ ] mod_rewrite enabled (Apache)
- [ ] AllowOverride All set (Apache)
- [ ] No BOM in PHP files
- [ ] No whitespace before `<?php`

---

## 📞 Still Having Issues?

### Get More Info:

1. **Visit test.php** - Does it work?
2. **Visit debug.php** - What's red?
3. **Check error logs** - What's the actual error?
4. **Check permissions** - Can PHP write files?

### Common Error Messages:

**"Call to undefined function PDO::__construct"**
→ PDO not installed

**"could not find driver"**
→ PDO SQLite driver not installed

**"Permission denied"**
→ Directory not writable

**"Headers already sent"**
→ Already fixed with @session_start()

**"No such file or directory"**
→ Missing files, re-upload

---

## 🎉 Success Indicators

When everything works:
- ✅ `test.php` shows all YES
- ✅ `debug.php` shows all green ✓
- ✅ `health.php` shows "Ready to Install"
- ✅ `index.php` redirects to install page
- ✅ Can complete setup wizard
- ✅ Dashboard loads

---

## 🔒 Security Note

**After installation succeeds, you can delete:**
- `test.php`
- `debug.php`

**Keep:**
- `health.php` (useful for diagnostics)

Or protect them with authentication if you want to keep them.

---

**Use the diagnostic files to identify the exact issue!** 🔍
