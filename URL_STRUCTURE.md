# 🔗 URL Structure

## Clean URLs Implemented

The analytics platform now uses clean, SEO-friendly URLs for better user experience.

---

## 📊 Site Analytics URLs

### Basic Site View
```
/site/1/
/site/2/
/site/{id}/
```
**Old:** `/app/site-view.php?id=1`  
**New:** `/site/1/`

### With Time Period
```
/site/1/7d     → Last 7 days
/site/1/30d    → Last 30 days
/site/1/90d    → Last 90 days
```
**Old:** `/app/site-view.php?id=1&period=30d`  
**New:** `/site/1/30d`

### Custom Date Range
```
/site/1/?period=custom&start=2025-11-01&end=2025-11-30
```
Custom date ranges still use query parameters for flexibility.

---

## 🗂️ Other URLs

### Dashboard
```
/                    → Main dashboard
/index.php           → Main dashboard (alternative)
```

### Authentication
```
/app/login.php       → Login page
/app/logout.php      → Logout
/app/install.php     → Setup wizard
```

### Site Management
```
/app/site-add.php              → Add new site
/app/site-settings.php?id=1    → Site settings
```

### User Management
```
/app/users.php       → User management (admin only)
```

### Tracking
```
/track.js            → Tracking script
/app/track.php       → Tracking endpoint (API)
```

---

## 🎯 URL Patterns

### Site Analytics Pattern
```
Pattern: /site/{id}/{period}
Examples:
  /site/1/          → Site 1, default 7 days
  /site/1/30d       → Site 1, last 30 days
  /site/5/90d       → Site 5, last 90 days
```

### Query String Parameters
```
?period=custom&start=YYYY-MM-DD&end=YYYY-MM-DD
?id=1
```

---

## 🔧 Technical Details

### .htaccess Rules

```apache
# Clean URL for site analytics: /site/1/ or /site/1
RewriteRule ^site/([0-9]+)/?$ /app/site-view.php?id=$1 [L,QSA]

# Clean URL for site analytics with period: /site/1/30d
RewriteRule ^site/([0-9]+)/([0-9]+d)/?$ /app/site-view.php?id=$1&period=$2 [L,QSA]
```

**Flags:**
- `L` - Last rule (stop processing)
- `QSA` - Query String Append (preserve other parameters)

### Regex Breakdown
```
^site/           → Starts with "site/"
([0-9]+)         → Capture one or more digits (site ID)
/?               → Optional trailing slash
$                → End of string
([0-9]+d)        → Capture digits followed by 'd' (period)
```

---

## 📝 Examples in Use

### Navigation Flow
```
1. Dashboard (/)
   ↓
2. Click site → /site/1/
   ↓
3. Change period → /site/1/30d
   ↓
4. Custom range → /site/1/?period=custom&start=2025-11-01&end=2025-11-30
   ↓
5. Settings → /app/site-settings.php?id=1
   ↓
6. Back to analytics → /site/1/
```

### Direct Access
```
✅ /site/1/          → Works
✅ /site/1           → Works (trailing slash optional)
✅ /site/1/7d        → Works
✅ /site/1/30d       → Works
✅ /site/1/90d       → Works
❌ /site/abc/        → Doesn't match (not a number)
❌ /site/1/invalid   → Doesn't match (invalid period)
```

---

## 🎨 Benefits

### User Experience
- **Cleaner URLs** - Easy to read and remember
- **Shareable** - Copy/paste friendly
- **Bookmarkable** - Save specific views
- **Professional** - Modern web standards

### SEO
- **Semantic URLs** - Meaningful structure
- **No query strings** - Cleaner for search engines
- **Hierarchical** - Logical organization

### Development
- **Maintainable** - Clear URL patterns
- **Extensible** - Easy to add new patterns
- **Backward compatible** - Old URLs still work

---

## 🔄 Migration

### Old URLs Still Work
```
✅ /app/site-view.php?id=1           → Still works
✅ /app/site-view.php?id=1&period=30d → Still works
```

### Recommended URLs
```
✨ /site/1/          → Use this
✨ /site/1/30d       → Use this
```

---

## 🚀 Future Enhancements

Potential URL patterns to add:
```
/site/1/compare/7d/30d     → Compare periods
/site/1/export/csv         → Export data
/site/1/realtime           → Real-time view
/sites/                    → All sites overview
/reports/                  → Custom reports
```

---

## 📊 URL Structure Summary

```
Root
├── /                          (Dashboard)
├── /site/{id}/                (Analytics - 7d default)
│   ├── /site/{id}/7d          (7 days)
│   ├── /site/{id}/30d         (30 days)
│   └── /site/{id}/90d         (90 days)
├── /app/
│   ├── login.php              (Authentication)
│   ├── logout.php
│   ├── install.php            (Setup)
│   ├── site-add.php           (Add site)
│   ├── site-settings.php?id=  (Settings)
│   ├── users.php              (User management)
│   └── track.php              (API endpoint)
└── /track.js                  (Tracking script)
```

---

**Clean URLs are now live!** 🎉

Use `/site/1/` instead of `/app/site-view.php?id=1` for a better experience.
