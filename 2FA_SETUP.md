# Two-Factor Authentication (2FA) Setup Guide

## 🔐 Overview
Two-Factor Authentication has been successfully implemented in your analytics platform using TOTP (Time-based One-Time Password), compatible with Google Authenticator, Authy, and other authenticator apps.

## 📋 Installation Steps

### 1. Run Database Migration
First, update your database schema to add 2FA support:

```bash
cd /Users/giovannidecarlo/Documents/Development/Projects/wharftales/wharftales-analytics
php app/migrate-2fa.php
```

This will add the following columns to the `users` table:
- `two_factor_secret` - Stores the TOTP secret key
- `two_factor_enabled` - Flag (0=disabled, 1=enabled)
- `two_factor_backup_codes` - JSON array of backup codes

### 2. Enable 2FA for Your Account
1. Log in to your analytics dashboard
2. Click the **🔐 lock icon** in the top navigation
3. Scan the QR code with your authenticator app
4. Enter the 6-digit code to verify
5. **IMPORTANT:** Save your 10 backup codes in a secure location!

## ✨ Features

### TOTP-Based Authentication
- Compatible with any TOTP app:
  - ✅ Google Authenticator
  - ✅ Microsoft Authenticator
  - ✅ Authy
  - ✅ 1Password
  - ✅ Bitwarden
  - ✅ Any RFC 6238 compliant app

### Backup Codes
- 10 single-use backup codes generated during setup
- Use them if you lose access to your authenticator app
- Each code is 8 characters (e.g., A1B2C3D4)
- Codes are removed after use

### Security Features
- ✅ Time-based codes (30-second windows)
- ✅ Password required to disable 2FA
- ✅ 5-minute timeout for 2FA verification
- ✅ Backup codes for account recovery

## 🔄 Login Flow

### Without 2FA
1. Enter email and password
2. → Logged in

### With 2FA Enabled
1. Enter email and password
2. → Redirected to 2FA verification page
3. Enter 6-digit code from authenticator app (or backup code)
4. → Logged in

## 🛠️ Managing 2FA

### Enable 2FA
1. Click 🔐 in header
2. Scan QR code with authenticator app
3. Enter verification code
4. Save backup codes

### Disable 2FA
1. Click 🔐 in header
2. Enter your password
3. Click "Disable 2FA"

## 📁 Created Files

### Core Library
- `app/TwoFactor.php` - TOTP implementation (generate secrets, verify codes, create QR codes)

### User Interface
- `app/two-factor.php` - 2FA management page (enable/disable, QR code display)
- `app/verify-2fa.php` - Login verification page
- `app/migrate-2fa.php` - Database migration script

### Modified Files
- `app/login.php` - Updated to check for 2FA and redirect
- `app/header.php` - Added 🔐 icon link to 2FA settings

## 🔒 Security Best Practices

1. **Always save backup codes** - Store them securely (password manager, safe, etc.)
2. **Enable 2FA for all admin accounts** - Extra protection for privileged access
3. **Don't share your secret key** - The QR code/secret should never be shared
4. **Use a reliable authenticator app** - Keep it updated and backed up

## 🐛 Troubleshooting

### "Invalid code" error
- Check your device's time is synced (TOTP relies on accurate time)
- Try entering the next code that appears
- If still failing, use a backup code

### Lost authenticator app
- Use one of your backup codes to log in
- Disable and re-enable 2FA with a new device

### Backup codes not working
- Codes are case-sensitive
- Each code can only be used once
- Ensure there are no spaces

## 📊 Technical Details

- **Algorithm:** RFC 6238 (TOTP - Time-Based One-Time Password)
- **Hash:** HMAC-SHA1
- **Time Step:** 30 seconds
- **Code Length:** 6 digits
- **Secret Length:** 16 characters (Base32)
- **Clock Drift Tolerance:** ±1 time window (±30 seconds)

## ✅ Next Steps

1. Run the migration script
2. Enable 2FA on your account
3. Test login with 2FA
4. Encourage other users to enable 2FA
5. Consider making 2FA mandatory for admin accounts (optional enhancement)

---

**Note:** The implementation uses pure PHP with no external dependencies for TOTP generation and validation. QR codes are generated using Google Charts API for simplicity.
