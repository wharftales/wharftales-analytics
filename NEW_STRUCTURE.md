# 📁 New Clean Structure

The project has been reorganized for a cleaner structure!

## 🎯 Root Directory (Clean!)

```
wharftales-analytics/
│
├── 📄 index.php              # Main entry point (dashboard)
├── 📄 track.js               # Public tracking script
│
├── 📁 app/                   # All application files
│   ├── config.php
│   ├── install.php
│   ├── login.php
│   ├── logout.php
│   ├── site-add.php
│   ├── site-settings.php
│   ├── site-view.php
│   ├── track.php
│   └── users.php
│
├── 📁 data/                  # Database (auto-created)
│   └── analytics.db
│
├── 📁 Documentation/
│   ├── README.md
│   ├── INSTALL.md
│   ├── QUICKSTART.md
│   ├── FEATURES.md
│   ├── PROJECT_SUMMARY.md
│   ├── STRUCTURE.md
│   └── CHECKLIST.md
│
└── ⚙️ Configuration
    ├── .htaccess
    └── .gitignore
```

## ✨ Benefits

### Cleaner Root
- Only essential public files in root
- `index.php` - Main entry point
- `track.js` - Public tracking script
- Everything else organized in `/app/`

### Better Organization
- All PHP application logic in `/app/`
- Database in `/data/`
- Documentation separate
- Configuration files in root

### Easier Maintenance
- Clear separation of concerns
- Easy to find files
- Logical grouping
- Professional structure

## 🔄 What Changed

### File Locations
- ✅ All PHP app files moved to `/app/`
- ✅ `index.php` stays in root (entry point)
- ✅ `track.js` stays in root (public access)
- ✅ Database in `/data/` (unchanged)

### Path Updates
All internal paths have been updated:
- ✅ `require_once` statements point to `/app/`
- ✅ Redirects use `/app/` prefix
- ✅ Links updated in all pages
- ✅ Database path points to parent `/data/`

### URLs
- Dashboard: `/index.php` or `/`
- Login: `/app/login.php`
- Install: `/app/install.php`
- Sites: `/app/site-*.php`
- Users: `/app/users.php`
- Tracking: `/track.js` (public)
- API: `/app/track.php`

## 📝 Key Files

### Public (Root)
- **index.php** - Main dashboard, first page users see
- **track.js** - Client tracking script (must be publicly accessible)

### Application (/app/)
- **config.php** - Configuration & helpers
- **install.php** - Setup wizard
- **login.php** - Authentication
- **logout.php** - Session cleanup
- **site-add.php** - Add new sites
- **site-settings.php** - Site configuration
- **site-view.php** - Analytics dashboard
- **track.php** - Tracking endpoint
- **users.php** - User management

### Data (/data/)
- **analytics.db** - SQLite database (auto-created)
- **.htaccess** - Access protection (auto-created)

## 🚀 Installation (Unchanged)

1. Upload all files
2. Set permissions: `chmod 755 /path/to/analytics`
3. Visit: `https://yourdomain.com/analytics/`
4. Complete setup wizard
5. Add sites and start tracking!

## 🔗 Internal Linking

All links have been updated to use the new structure:

```php
// Dashboard links
/app/login.php
/app/logout.php
/app/users.php
/app/site-add.php
/app/site-view.php
/app/site-settings.php

// Public links
/index.php (dashboard)
/track.js (tracking script)
```

## ✅ Everything Still Works!

- ✅ Setup wizard
- ✅ Login/logout
- ✅ Dashboard
- ✅ Site management
- ✅ Analytics viewing
- ✅ User management
- ✅ Tracking script
- ✅ Data collection

## 📊 Tracking Script (Unchanged)

The tracking script URL remains the same:

```html
<script data-site-id="site_xxxxx" src="https://yourdomain.com/analytics/track.js"></script>
```

No changes needed to existing installations!

## 🎨 Benefits Summary

1. **Cleaner root directory** - Only 2 PHP files visible
2. **Better organization** - Logical file grouping
3. **Professional structure** - Industry standard layout
4. **Easier navigation** - Find files quickly
5. **Maintainable** - Clear separation of concerns
6. **Scalable** - Easy to add new features

---

**The structure is now cleaner and more professional!** 🎉
