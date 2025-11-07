# Quick Installation Guide

## Step 1: Upload Files
Upload all files to your web server.

## Step 2: Set Permissions
```bash
chmod 755 /path/to/analytics
```

The application will automatically create a `data/` directory with proper protection.

## Step 3: Access Setup
Navigate to: `https://yourdomain.com/analytics/`

You'll be redirected to the setup wizard automatically.

## Step 4: Create Admin Account
Fill in:
- Your name
- Email address
- Password (minimum 8 characters)

## Step 5: Add Your First Site
1. Click "Add New Site"
2. Enter site name (e.g., "My Blog")
3. Enter domain (e.g., "myblog.com" - without http://)
4. Copy the tracking script
5. Add it to your website before `</body>`

## That's it! 🎉

Your analytics platform is ready. The tracking script will start collecting data immediately.

## Security Note
After installation, change the `SALT` value in `config.php` to a random string for better security.
