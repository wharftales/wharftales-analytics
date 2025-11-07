# 🔧 Simple Fix - Use Direct URLs

## Quick Solution

If routing is causing issues, just use **direct URLs** - they always work!

---

## Option 1: Disable Router (Simplest)

### Step 1: Rename router.php
```bash
mv router.php router.php.disabled
```

### Step 2: Delete or rename .htaccess
```bash
mv .htaccess .htaccess.disabled
```

### Step 3: Update links to use direct URLs

**In `index.php` (line ~277):**
```php
// Change from:
<a href="/site/<?= $site['id'] ?>/" class="btn btn-primary">View Analytics</a>

// To:
<a href="/app/site-view.php?id=<?= $site['id'] ?>" class="btn btn-primary">View Analytics</a>
```

**In `app/site-view.php` (lines ~397-399):**
```php
// Change from:
<a href="/site/<?= $siteId ?>/7d" class="period-btn...">7 Days</a>
<a href="/site/<?= $siteId ?>/30d" class="period-btn...">30 Days</a>
<a href="/site/<?= $siteId ?>/90d" class="period-btn...">90 Days</a>

// To:
<a href="/app/site-view.php?id=<?= $siteId ?>&period=7d" class="period-btn...">7 Days</a>
<a href="/app/site-view.php?id=<?= $siteId ?>&period=30d" class="period-btn...">30 Days</a>
<a href="/app/site-view.php?id=<?= $siteId ?>&period=90d" class="period-btn...">90 Days</a>
```

**In `app/site-view.php` (line ~403):**
```php
// Change form action from:
<form class="date-range-form" method="GET" action="/site/<?= $siteId ?>/">

// To:
<form class="date-range-form" method="GET" action="/app/site-view.php">
<input type="hidden" name="id" value="<?= $siteId ?>">
```

**In `app/site-view.php` (line ~411):**
```php
// Change from:
<a href="/site/<?= $siteId ?>/" ...>Clear</a>

// To:
<a href="/app/site-view.php?id=<?= $siteId ?>" ...>Clear</a>
```

**In `app/site-settings.php` (line ~240):**
```php
// Change from:
<a href="/site/<?= $site['id'] ?>/" class="btn btn-primary">View Analytics</a>

// To:
<a href="/app/site-view.php?id=<?= $site['id'] ?>" class="btn btn-primary">View Analytics</a>
```

---

## Option 2: Debug Router

### Add debug output to router.php:

```php
<?php
// At the top of router.php, add:
error_log("Router called: " . $_SERVER['REQUEST_URI']);

// After path parsing, add:
error_log("Parsed path: " . $path);

// After each match, add:
error_log("Matched site route with ID: " . $matches[1]);
```

Then check your error log to see what's happening.

---

## Option 3: Test Router Directly

Visit: `http://localhost/analytics/test-router.php`

This will show you if the router logic is working correctly.

---

## Recommended: Use Direct URLs

**Pros:**
- ✅ Always works
- ✅ No .htaccess needed
- ✅ No routing complexity
- ✅ Works on any server
- ✅ Easier to debug

**Cons:**
- ❌ URLs are longer
- ❌ Not as "pretty"

**Example URLs:**
```
Dashboard: /index.php
Site view: /app/site-view.php?id=1
With period: /app/site-view.php?id=1&period=30d
Settings: /app/site-settings.php?id=1
```

---

## Quick Test

1. **Disable routing:**
   ```bash
   mv router.php router.php.disabled
   mv .htaccess .htaccess.disabled
   ```

2. **Visit directly:**
   ```
   http://localhost/analytics/app/site-view.php?id=1
   ```

3. **Does it work?**
   - ✅ YES → Use direct URLs, update links
   - ❌ NO → Different issue (check if logged in)

---

## Summary

**If routing is problematic:**
1. Disable router and .htaccess
2. Use direct URLs everywhere
3. App works perfectly!

**URLs will be:**
- `/app/site-view.php?id=1` instead of `/site/1/`
- Still clean, just not as short
- **100% reliable**

---

**Choose reliability over pretty URLs!** 🚀
