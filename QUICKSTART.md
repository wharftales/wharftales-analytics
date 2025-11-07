# 🚀 Quick Start Guide

Get your analytics platform running in 5 minutes!

## Step 1: Upload Files (1 min)

Upload all files to your web server:
```bash
# Via FTP/SFTP or command line
scp -r * user@yourserver:/var/www/html/analytics/
```

## Step 2: Set Permissions (30 sec)

```bash
chmod 755 /var/www/html/analytics
```

That's it! The app will create the database automatically.

## Step 3: Run Setup (2 min)

1. Open your browser and go to: `https://yourdomain.com/analytics/`
2. You'll see the setup wizard
3. Fill in:
   - Your name
   - Email address
   - Password (min 8 characters)
4. Click "Complete Setup"

## Step 4: Add Your First Site (1 min)

1. Click "Add New Site"
2. Enter:
   - **Site Name**: "My Website"
   - **Domain**: "example.com" (without http://)
3. Click "Add Site"
4. Copy the tracking script shown

## Step 5: Install Tracking Code (30 sec)

Add the script to your website before `</body>`:

```html
<script data-site-id="site_xxxxx" src="https://yourdomain.com/analytics/track.js"></script>
```

## Done! 🎉

Your analytics are now live. Visit your site and check the dashboard to see data flowing in.

---

## Common Scenarios

### Scenario 1: Tracking Multiple Sites

1. Dashboard → "Add New Site"
2. Repeat for each website
3. Each gets a unique tracking script
4. All analytics in one dashboard

### Scenario 2: Adding Team Members

1. Dashboard → "Users" (admin only)
2. Click "Create New User"
3. Fill in details
4. Choose admin or regular user
5. Regular users only see assigned sites

### Scenario 3: Viewing Analytics

1. Dashboard → Click any site card
2. Choose time period (7d, 30d, 90d)
3. View:
   - Total pageviews
   - Unique visitors
   - Top pages
   - Traffic sources
   - Browser/OS stats

---

## Troubleshooting

### "Database error" on setup
```bash
# Check permissions
chmod 755 /path/to/analytics
ls -la /path/to/analytics
```

### Tracking not working
1. Check browser console for errors
2. Verify domain matches in settings
3. Ensure script URL is correct
4. Wait 1-2 minutes for data to appear

### Can't login
1. Clear browser cookies
2. Try password reset (if implemented)
3. Check database exists: `ls data/analytics.db`

---

## Next Steps

- ✅ **Secure it**: Change `SALT` in `config.php`
- ✅ **Enable HTTPS**: Use Let's Encrypt
- ✅ **Backup**: Copy `data/` directory regularly
- ✅ **Monitor**: Check analytics weekly
- ✅ **Optimize**: Run `VACUUM` on database monthly

---

## Quick Reference

### File Structure
```
analytics/
├── config.php          # Configuration
├── install.php         # Setup wizard
├── login.php           # Login page
├── index.php           # Dashboard
├── site-add.php        # Add site
├── site-view.php       # View analytics
├── site-settings.php   # Site settings
├── users.php           # User management
├── track.php           # Tracking endpoint
├── track.js            # Tracking script
└── data/               # Database (auto-created)
    └── analytics.db
```

### Important URLs
- Setup: `/install.php`
- Login: `/login.php`
- Dashboard: `/index.php`
- Users: `/users.php` (admin only)

### Default Behavior
- First user is always admin
- Admins see all sites
- Regular users see assigned sites only
- Data rotates daily for privacy
- Do Not Track is respected

---

Need help? Check `README.md` for detailed documentation.
