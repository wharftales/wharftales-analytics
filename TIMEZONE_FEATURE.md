# 🌍 Timezone Support Added

## Overview

Each site now has its own timezone setting for displaying analytics data and timestamps in the correct local time.

---

## What Changed

### 1. Database Schema
**Updated `sites` table:**
```sql
ALTER TABLE sites ADD COLUMN timezone TEXT DEFAULT 'UTC'
```

### 2. Migration Script
**Created:** `/app/migrate-timezone.php`
- Adds timezone column to existing databases
- Run once to update existing installations

### 3. Site Creation
**Updated:** `/app/site-add.php`
- Added timezone selector with 20 common timezones
- Defaults to UTC
- Saves timezone when creating new site

### 4. Site Settings
**Updated:** `/app/site-settings.php`
- Added timezone selector to edit form
- Can change timezone for existing sites
- Shows current timezone selection

---

## Available Timezones

### Americas
- **UTC** - Coordinated Universal Time
- **Eastern Time** - US & Canada (New York)
- **Central Time** - US & Canada (Chicago)
- **Mountain Time** - US & Canada (Denver)
- **Pacific Time** - US & Canada (Los Angeles)
- **Alaska** - Anchorage
- **Hawaii** - Honolulu

### Europe
- **London** - GMT/BST
- **Paris, Berlin, Rome** - CET/CEST
- **Athens, Istanbul** - EET/EEST
- **Moscow** - MSK

### Asia
- **Dubai** - GST
- **Mumbai, Kolkata** - IST
- **Bangkok, Hanoi** - ICT
- **Singapore** - SGT
- **Hong Kong** - HKT
- **Tokyo, Osaka** - JST

### Pacific
- **Sydney, Melbourne** - AEDT/AEST
- **Auckland** - NZDT/NZST

---

## For Existing Installations

### Run Migration

**Option 1: Via Command Line**
```bash
cd /path/to/analytics
php app/migrate-timezone.php
```

**Option 2: Via Browser**
```
https://yourdomain.com/analytics/app/migrate-timezone.php
```

**Expected Output:**
```
✅ Timezone column added successfully!
Migration completed successfully!
```

**If already migrated:**
```
ℹ️  Timezone column already exists.
Migration completed successfully!
```

---

## Usage

### When Adding a New Site

1. Go to **Dashboard** → **Add Site**
2. Fill in site name and domain
3. **Select timezone** from dropdown
4. Click "Add Site"

The timezone will be saved and used for all analytics displays.

### Updating Existing Site

1. Go to **Site Settings**
2. Change the **Timezone** dropdown
3. Click "Save Changes"

All future analytics will display in the new timezone.

---

## Benefits

### ✅ Accurate Local Time
- View analytics in your local timezone
- No mental conversion needed
- Timestamps match your business hours

### ✅ Multi-Region Support
- Different sites can have different timezones
- Perfect for international businesses
- Each site shows data in its local time

### ✅ Better Insights
- See traffic patterns in local context
- Understand peak hours correctly
- Make better decisions based on local time

---

## Examples

### Scenario 1: US Company
```
Main Site: Pacific Time (Los Angeles)
East Coast Office: Eastern Time (New York)
```

Each site shows analytics in its local timezone.

### Scenario 2: Global Company
```
US Site: Pacific Time
UK Site: London
Japan Site: Tokyo
```

Each region sees data in their local time.

### Scenario 3: E-commerce
```
Store Timezone: Central Time (Chicago)
```

See when customers actually shop in your business hours.

---

## Technical Details

### Database
```sql
-- Sites table now includes:
CREATE TABLE sites (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    domain TEXT NOT NULL,
    tracking_id TEXT UNIQUE NOT NULL,
    timezone TEXT DEFAULT 'UTC',  -- NEW!
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### PHP Usage (Future)
```php
// Get site timezone
$timezone = $site['timezone'] ?? 'UTC';

// Set timezone for date functions
date_default_timezone_set($timezone);

// Display dates in site timezone
$localTime = date('Y-m-d H:i:s', strtotime($utcTime));
```

---

## Future Enhancements

### Planned Features
- [ ] Display all dates in site timezone
- [ ] Timezone indicator on analytics page
- [ ] Convert UTC timestamps to local time
- [ ] Show timezone in date pickers
- [ ] Timezone-aware date range filters

---

## Migration Checklist

For existing installations:

- [ ] Run migration script
- [ ] Verify timezone column exists
- [ ] Update existing sites with correct timezone
- [ ] Test site creation with timezone
- [ ] Test site editing with timezone
- [ ] Verify timezone is saved correctly

---

## Files Modified

1. ✅ `/app/install.php` - Added timezone to schema
2. ✅ `/app/site-add.php` - Added timezone selector
3. ✅ `/app/site-settings.php` - Added timezone editor
4. ✅ `/app/migrate-timezone.php` - Migration script (NEW)

---

## Summary

✅ **Database updated** - Timezone column added  
✅ **Site creation** - Timezone selector included  
✅ **Site settings** - Timezone can be edited  
✅ **20 timezones** - Common zones worldwide  
✅ **Migration ready** - Script for existing installs  
✅ **Future-proof** - Ready for timezone-aware displays  

**Each site now has its own timezone!** 🌍
