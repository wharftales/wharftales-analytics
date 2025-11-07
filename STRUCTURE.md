# 📁 Project Structure

```
wharftales-analytics/
│
├── 📄 config.php                 # Core configuration & helpers
│   ├── Database connection
│   ├── Authentication functions
│   ├── Session management
│   └── Helper functions
│
├── 🚀 INSTALLATION & SETUP
│   ├── 📄 install.php            # Setup wizard
│   ├── 📄 login.php              # User login
│   └── 📄 logout.php             # Session cleanup
│
├── 📊 DASHBOARD & NAVIGATION
│   ├── 📄 index.php              # Main dashboard
│   │   ├── Site listing
│   │   ├── User info
│   │   └── Quick actions
│   │
│   └── 📄 users.php              # User management (admin)
│       ├── Create users
│       ├── Delete users
│       └── Manage permissions
│
├── 🌐 SITE MANAGEMENT
│   ├── 📄 site-add.php           # Add new site
│   │   ├── Site creation form
│   │   ├── Domain configuration
│   │   └── Tracking ID generation
│   │
│   ├── 📄 site-settings.php      # Site configuration
│   │   ├── Tracking code display
│   │   ├── Installation guide
│   │   └── GDPR info
│   │
│   └── 📄 site-view.php          # Analytics dashboard
│       ├── Overview stats
│       ├── Daily trends
│       ├── Top pages
│       ├── Top referrers
│       ├── Browser stats
│       ├── OS stats
│       └── Device breakdown
│
├── 📈 TRACKING SYSTEM
│   ├── 📄 track.js               # Client-side script
│   │   ├── Cookieless tracking
│   │   ├── Page view capture
│   │   ├── SPA support
│   │   ├── DNT respect
│   │   └── Beacon API
│   │
│   └── 📄 track.php              # Server endpoint
│       ├── CORS handling
│       ├── Domain verification
│       ├── Visitor hashing
│       ├── User agent parsing
│       └── Data storage
│
├── ⚙️ CONFIGURATION
│   ├── 📄 .htaccess              # Apache config
│   │   ├── URL rewriting
│   │   ├── Security headers
│   │   └── Directory protection
│   │
│   └── 📄 .gitignore             # Git exclusions
│       ├── Database files
│       ├── Data directory
│       └── System files
│
├── 📚 DOCUMENTATION
│   ├── 📄 README.md              # Main documentation
│   │   ├── Features
│   │   ├── Installation
│   │   ├── Usage
│   │   ├── Privacy info
│   │   └── Troubleshooting
│   │
│   ├── 📄 INSTALL.md             # Quick install guide
│   ├── 📄 QUICKSTART.md          # 5-minute guide
│   ├── 📄 FEATURES.md            # Feature details
│   ├── 📄 PROJECT_SUMMARY.md     # Project overview
│   └── 📄 STRUCTURE.md           # This file
│
└── 📁 data/                      # Auto-created on setup
    ├── 📄 .htaccess              # Access protection
    └── 📄 analytics.db           # SQLite database

```

## 🔄 Request Flow

### 1. First Time Setup
```
Browser → install.php
         ↓
    Create database
         ↓
    Create admin user
         ↓
    Redirect to dashboard
```

### 2. User Login
```
Browser → login.php
         ↓
    Verify credentials
         ↓
    Create session
         ↓
    Redirect to dashboard
```

### 3. View Dashboard
```
Browser → index.php
         ↓
    Check authentication
         ↓
    Load user's sites
         ↓
    Display dashboard
```

### 4. Add Site
```
Browser → site-add.php
         ↓
    Create site record
         ↓
    Generate tracking ID
         ↓
    Redirect to settings
```

### 5. View Analytics
```
Browser → site-view.php?id=X
         ↓
    Check permissions
         ↓
    Query pageviews
         ↓
    Calculate metrics
         ↓
    Display reports
```

### 6. Track Pageview
```
Website → track.js
         ↓
    Collect data
         ↓
    POST to track.php
         ↓
    Verify domain
         ↓
    Hash visitor
         ↓
    Store in database
```

## 🗄️ Database Schema

```
┌─────────────────┐
│     users       │
├─────────────────┤
│ id (PK)         │
│ name            │
│ email (unique)  │
│ password        │
│ is_admin        │
│ created_at      │
└─────────────────┘
         │
         │ 1:N
         ↓
┌─────────────────┐
│   user_sites    │
├─────────────────┤
│ user_id (FK)    │
│ site_id (FK)    │
│ created_at      │
└─────────────────┘
         │
         │ N:1
         ↓
┌─────────────────┐
│     sites       │
├─────────────────┤
│ id (PK)         │
│ name            │
│ domain          │
│ tracking_id     │
│ created_at      │
└─────────────────┘
         │
         │ 1:N
         ↓
┌─────────────────┐
│   pageviews     │
├─────────────────┤
│ id (PK)         │
│ site_id (FK)    │
│ path            │
│ referrer        │
│ user_agent      │
│ browser         │
│ os              │
│ device_type     │
│ visitor_hash    │
│ session_hash    │
│ timestamp       │
└─────────────────┘
```

## 🎨 Page Hierarchy

```
Root
│
├── Public (No Auth)
│   ├── install.php (setup only)
│   └── login.php
│
├── Authenticated
│   ├── index.php (dashboard)
│   ├── site-add.php
│   ├── site-view.php
│   ├── site-settings.php
│   └── logout.php
│
└── Admin Only
    └── users.php
```

## 🔐 Permission Levels

```
┌──────────────────────────────────────┐
│           ADMIN USER                 │
├──────────────────────────────────────┤
│ ✅ View all sites                    │
│ ✅ Add sites                         │
│ ✅ Delete sites                      │
│ ✅ Create users                      │
│ ✅ Delete users                      │
│ ✅ Manage permissions                │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│         REGULAR USER                 │
├──────────────────────────────────────┤
│ ✅ View assigned sites               │
│ ✅ Add sites (auto-assigned)         │
│ ❌ View other users' sites           │
│ ❌ Create users                      │
│ ❌ Delete users                      │
│ ❌ Manage permissions                │
└──────────────────────────────────────┘
```

## 📊 Data Flow

### Tracking Data Flow
```
Website Visitor
       ↓
   track.js (client)
       ↓
   Collect: path, referrer, screen size
       ↓
   POST to track.php
       ↓
   Verify domain
       ↓
   Parse user agent
       ↓
   Generate hashes
       ↓
   Store in pageviews table
       ↓
   Return success
```

### Analytics Data Flow
```
User requests analytics
       ↓
   site-view.php
       ↓
   Check permissions
       ↓
   Query pageviews (filtered by date)
       ↓
   Calculate metrics:
   ├── Total views
   ├── Unique visitors
   ├── Bounce rate
   ├── Session duration
   ├── Top pages
   ├── Top referrers
   ├── Browser stats
   ├── OS stats
   └── Device stats
       ↓
   Render dashboard
```

## 🔒 Security Layers

```
┌─────────────────────────────────────┐
│      Application Security           │
├─────────────────────────────────────┤
│ 1. Authentication (sessions)        │
│ 2. Authorization (role checks)      │
│ 3. Input validation                 │
│ 4. Output escaping                  │
│ 5. Prepared statements              │
│ 6. Password hashing                 │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│      Tracking Security              │
├─────────────────────────────────────┤
│ 1. Domain verification              │
│ 2. CORS headers                     │
│ 3. Anonymous hashing                │
│ 4. Daily hash rotation              │
│ 5. No PII collection                │
│ 6. DNT respect                      │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│      Server Security                │
├─────────────────────────────────────┤
│ 1. .htaccess protection             │
│ 2. Directory restrictions           │
│ 3. Security headers                 │
│ 4. Database file protection         │
└─────────────────────────────────────┘
```

## 📦 Dependencies

```
Required:
├── PHP 7.4+
│   ├── PDO extension
│   ├── SQLite3 extension
│   └── Session support
│
└── Web Server
    ├── Apache (with mod_rewrite)
    └── OR Nginx (with rewrite rules)

Optional:
└── None! (Zero external dependencies)
```

## 🎯 Key Files by Function

### Authentication
- `config.php` - Auth helpers
- `login.php` - Login form
- `logout.php` - Logout handler

### Site Management
- `site-add.php` - Create sites
- `site-settings.php` - Configure sites
- `site-view.php` - View analytics

### Tracking
- `track.js` - Client script
- `track.php` - Server endpoint

### Administration
- `users.php` - User management
- `install.php` - Setup wizard

### Configuration
- `config.php` - App config
- `.htaccess` - Server config
- `.gitignore` - Git config

---

This structure provides a complete, self-contained analytics platform with no external dependencies!
