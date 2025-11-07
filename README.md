# 📊 WharfTales Analytics

A simple, privacy-focused, cookieless analytics platform built with PHP and SQLite. Similar to Plausible and Umami, but self-hosted and easy to install.

## ✨ Features

- **🔒 Privacy-First & GDPR Compliant**: Cookieless tracking, no personal data collection
- **👥 Multi-User Support**: Create multiple users with different access levels
- **🌐 Multi-Site Tracking**: Track analytics for multiple websites from one dashboard
- **🎯 Role-Based Access**: Admins have full access, regular users only see assigned sites
- **📈 Comprehensive Analytics**: 
  - Pageviews and unique visitors
  - Top pages and referrers
  - Browser, OS, and device statistics
  - Bounce rate and session duration
  - Daily traffic trends
- **⚡ Lightweight**: Built with vanilla PHP and SQLite - no heavy frameworks
- **🚀 Easy Installation**: Simple setup wizard, no complex configuration

## 🛠️ Requirements

- PHP 7.4 or higher
- SQLite3 extension (usually included with PHP)
- Apache with mod_rewrite (or Nginx with similar configuration)
- Write permissions for the application directory

## 📦 Installation

### 1. Upload Files

Upload all files to your web server (e.g., `/var/www/html/analytics/` or your hosting directory).

### 2. Set Permissions

Ensure the web server can write to the application directory:

```bash
chmod 755 /path/to/analytics
```

The application will automatically create a `data/` directory for the SQLite database.

### 3. Configure Web Server

#### Apache

The included `.htaccess` file should work automatically if `mod_rewrite` is enabled.

To enable mod_rewrite:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

Add this to your server block:

```nginx
location / {
    try_files $uri $uri/ $uri.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}

location /data/ {
    deny all;
}
```

### 4. Run Setup

Navigate to your analytics URL in a web browser:

```
https://yourdomain.com/analytics/
```

You'll be automatically redirected to the setup page where you'll create your admin account.

### 5. Add Your First Site

After setup:
1. Click "Add New Site"
2. Enter your site name and domain
3. Copy the tracking script
4. Add it to your website before the closing `</body>` tag

## 🎯 Usage

### Adding the Tracking Script

After creating a site, you'll get a tracking script like this:

```html
<script data-site-id="site_xxxxx" src="https://yourdomain.com/analytics/track.js"></script>
```

Add this to your website's HTML, just before the closing `</body>` tag.

### Managing Users (Admin Only)

Admins can:
- Create new users
- Delete users
- Set admin privileges
- View all sites

Regular users can only view sites they have access to.

### Viewing Analytics

Click on any site from your dashboard to view:
- Real-time statistics
- Traffic trends (7, 30, or 90 days)
- Top pages and referrers
- Browser, OS, and device breakdown

## 🔒 Privacy & GDPR Compliance

This analytics platform is designed to be privacy-friendly and GDPR compliant:

- **No Cookies**: The tracking script doesn't use cookies or local storage
- **No Personal Data**: We don't collect names, emails, or any PII
- **Anonymous Tracking**: Visitor hashes are created using IP + User Agent + Date (rotated daily)
- **Respects DNT**: Honors "Do Not Track" browser settings
- **Domain Restriction**: Only accepts tracking requests from configured domains
- **No Cross-Site Tracking**: Each site is tracked independently

### What We Track

- Page URLs (paths only, no query parameters with sensitive data)
- Referrer URLs
- Browser type
- Operating system
- Device type (desktop/mobile/tablet)
- Anonymous visitor hash (changes daily)

### What We DON'T Track

- Personal information
- IP addresses (used only for hashing, not stored)
- Cookies or persistent identifiers
- Form inputs or user interactions
- Cross-site behavior

## 🗄️ Database

The application uses SQLite for simplicity and performance. The database file is stored in `data/analytics.db`.

### Backup

To backup your data, simply copy the `data/` directory:

```bash
cp -r data/ backup-$(date +%Y%m%d)/
```

### Performance

SQLite is suitable for small to medium traffic sites (up to millions of pageviews). For very high traffic sites, consider:
- Regular database optimization
- Archiving old data
- Upgrading to PostgreSQL or MySQL (requires code modifications)

## 🔧 Configuration

Edit `config.php` to customize:

- `DB_PATH`: Database file location
- `SALT`: Security salt (change this to a random string)

## 🚀 Deployment Tips

### Production Checklist

- [ ] Change the `SALT` value in `config.php` to a random string
- [ ] Ensure HTTPS is enabled
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Protect the `data/` directory from web access
- [ ] Enable PHP error logging (disable display_errors in production)
- [ ] Set up regular database backups

### Performance Optimization

For high-traffic sites:

1. **Enable PHP OPcache**:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
```

2. **Database Optimization**:
```bash
# Run periodically to optimize database
sqlite3 data/analytics.db "VACUUM;"
sqlite3 data/analytics.db "ANALYZE;"
```

3. **Archive Old Data**:
Consider archiving pageviews older than 90 days to keep the database lean.

## 🐛 Troubleshooting

### Setup page shows "Database error"

- Check write permissions on the application directory
- Ensure SQLite3 PHP extension is installed: `php -m | grep sqlite3`

### Tracking script not working

- Verify the domain in site settings matches your website's domain
- Check browser console for errors
- Ensure the tracking script URL is correct and accessible
- Verify CORS headers are properly set

### Analytics not showing data

- Wait a few minutes after adding the tracking script
- Check that the tracking script is properly installed on your site
- Verify the site domain configuration matches your website
- Check browser's Do Not Track setting isn't blocking the script

## 📝 License

This project is open source and available for personal and commercial use.

## 🤝 Contributing

Contributions are welcome! Feel free to submit issues and pull requests.

## 📧 Support

For issues and questions, please create an issue on the project repository.

---

Built with ❤️ for privacy-conscious website owners.
