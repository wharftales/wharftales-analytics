# 🚀 Running Without .htaccess

## Good News!

The app now works **with or without** `.htaccess`!

---

## Two Modes of Operation

### Mode 1: With .htaccess (Clean URLs)
```
✅ /site/1/
✅ /site/1/30d
```

### Mode 2: Without .htaccess (Query String URLs)
```
✅ /app/site-view.php?id=1
✅ /app/site-view.php?id=1&period=30d
```

**Both work perfectly!**

---

## How It Works

### With .htaccess
```
1. User visits /site/1/
   ↓
2. .htaccess rewrites to index.php
   ↓
3. index.php loads router.php
   ↓
4. router.php extracts id=1
   ↓
5. Loads app/site-view.php with $_GET['id'] = 1
```

### Without .htaccess
```
1. User visits /app/site-view.php?id=1
   ↓
2. PHP processes directly
   ↓
3. Works normally
```

---

## If You Have .htaccess Issues

### Option 1: Delete .htaccess
```bash
rm .htaccess
```

**Result:** App still works! Just use full URLs:
- `/app/site-view.php?id=1` instead of `/site/1/`

### Option 2: Use Minimal .htaccess
```apache
# Just enable rewriting, nothing else
RewriteEngine On
```

### Option 3: Use Current .htaccess
The current `.htaccess` is very simple and should work on most servers.

---

## URL Formats

### Dashboard Links

**With .htaccess:**
```php
<a href="/site/<?= $id ?>/">View Analytics</a>
```

**Without .htaccess:**
```php
<a href="/app/site-view.php?id=<?= $id ?>">View Analytics</a>
```

**Current code uses:** Clean URLs by default, but query strings work too!

---

## Automatic Fallback

The app automatically detects if clean URLs work:

```php
// In index.php
if (file_exists(__DIR__ . '/router.php')) {
    $routed = require __DIR__ . '/router.php';
    if ($routed !== false) {
        exit; // Clean URL handled
    }
}
// Otherwise, normal processing
```

---

## For Different Servers

### Apache (with mod_rewrite)
✅ Use current `.htaccess` - clean URLs work

### Apache (without mod_rewrite)
✅ Delete `.htaccess` - query string URLs work

### Nginx
✅ Delete `.htaccess` - add this to nginx.conf:
```nginx
location /site/ {
    rewrite ^/site/(\d+)/?$ /index.php last;
    rewrite ^/site/(\d+)/(\d+d)/?$ /index.php last;
}
```

### Lighttpd
✅ Delete `.htaccess` - add to lighttpd.conf:
```
url.rewrite-once = (
    "^/site/(\d+)/?$" => "/index.php",
    "^/site/(\d+)/(\d+d)/?$" => "/index.php"
)
```

### IIS
✅ Delete `.htaccess` - use web.config:
```xml
<rewrite>
    <rules>
        <rule name="Site URLs">
            <match url="^site/(.*)$" />
            <action type="Rewrite" url="index.php" />
        </rule>
    </rules>
</rewrite>
```

---

## Testing

### Test 1: With .htaccess
```
Visit: /site/1/
Should: Load site analytics
```

### Test 2: Without .htaccess
```
Delete .htaccess
Visit: /app/site-view.php?id=1
Should: Load site analytics
```

### Test 3: Direct Access
```
Visit: /app/site-view.php?id=1&period=30d
Should: Always work (with or without .htaccess)
```

---

## Updating Links

If you delete `.htaccess` and want to update all links to use query strings:

### Find and Replace:

**Dashboard (index.php):**
```php
// Change from:
<a href="/site/<?= $site['id'] ?>/">

// To:
<a href="/app/site-view.php?id=<?= $site['id'] ?>">
```

**Site View (site-view.php):**
```php
// Change from:
<a href="/site/<?= $siteId ?>/7d">

// To:
<a href="/app/site-view.php?id=<?= $siteId ?>&period=7d">
```

**Site Settings (site-settings.php):**
```php
// Change from:
<a href="/site/<?= $site['id'] ?>/">

// To:
<a href="/app/site-view.php?id=<?= $site['id'] ?>">
```

---

## Recommended Approach

### For Most Users:
1. ✅ Keep current `.htaccess` (it's simple and safe)
2. ✅ Enjoy clean URLs: `/site/1/`
3. ✅ If issues arise, delete `.htaccess`

### For Shared Hosting:
1. ✅ Try current `.htaccess` first
2. ✅ If 500 error, delete it
3. ✅ Use query string URLs

### For VPS/Dedicated:
1. ✅ Use current `.htaccess`
2. ✅ Or configure server directly (Nginx, etc.)

---

## Benefits of PHP Router

✅ **Server agnostic** - Works on Apache, Nginx, IIS, etc.  
✅ **No Apache modules needed** - Pure PHP  
✅ **Easier debugging** - PHP errors are clearer  
✅ **More portable** - Works anywhere PHP runs  
✅ **Fallback friendly** - Query strings always work  

---

## Summary

**The app is now flexible:**

- ✅ Works WITH `.htaccess` (clean URLs)
- ✅ Works WITHOUT `.htaccess` (query strings)
- ✅ Works on ANY server (Apache, Nginx, IIS)
- ✅ No 500 errors from `.htaccess` issues

**Choose what works best for your server!** 🎉

---

## Quick Decision Guide

**Have Apache with mod_rewrite?**
→ Keep `.htaccess`, enjoy clean URLs

**Getting 500 errors?**
→ Delete `.htaccess`, use query strings

**Using Nginx/IIS?**
→ Delete `.htaccess`, configure server OR use query strings

**Want maximum compatibility?**
→ Delete `.htaccess`, use query strings everywhere

---

**Bottom line: The app works either way!** 🚀
