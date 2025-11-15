<?php
require_once 'config.php';
require_once 'TwoFactor.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();
$user = getCurrentUser();
$db = getDb();

// Set defaults if columns don't exist yet
if (!isset($user['two_factor_enabled'])) {
    $user['two_factor_enabled'] = 0;
}
if (!isset($user['two_factor_secret'])) {
    $user['two_factor_secret'] = null;
}
if (!isset($user['two_factor_backup_codes'])) {
    $user['two_factor_backup_codes'] = null;
}

$error = '';
$success = '';
$qrCode = '';
$backupCodes = [];

// Handle enable 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enable') {
    $code = trim($_POST['code'] ?? '');
    $tempSecret = $_POST['temp_secret'] ?? '';
    
    if (empty($code)) {
        $error = "Please enter the 6-digit code from your authenticator app";
    } elseif (!TwoFactor::verifyCode($tempSecret, $code)) {
        $error = "Invalid code. Please try again.";
    } else {
        // Generate backup codes
        $backupCodesArray = TwoFactor::generateBackupCodes(10);
        $backupCodesJson = json_encode($backupCodesArray);
        
        // Enable 2FA
        $stmt = $db->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1, two_factor_backup_codes = ? WHERE id = ?");
        $stmt->execute([$tempSecret, $backupCodesJson, $user['id']]);
        
        $success = "Two-Factor Authentication enabled successfully! Please save your backup codes.";
        $backupCodes = $backupCodesArray;
        
        // Refresh user data
        $user = getCurrentUser();
    }
}

// Handle disable 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disable') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = "Password is required to disable 2FA";
    } elseif (!password_verify($password, $user['password'])) {
        $error = "Incorrect password";
    } else {
        $stmt = $db->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0, two_factor_backup_codes = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $success = "Two-Factor Authentication has been disabled";
        $user = getCurrentUser();
    }
}

// Generate QR code for setup (if 2FA not enabled)
if (!$user['two_factor_enabled']) {
    $tempSecret = TwoFactor::generateSecret();
    $qrCode = TwoFactor::getQRCodeUrl($tempSecret, $user['email']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication</title>
    <script src="/app/theme.js"></script>
    <link rel="stylesheet" href="/app/common.css">
    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 30px;
        }
        .card {
            padding: 32px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 14px;
        }
        .qr-container {
            text-align: center;
            padding: 30px;
            background: var(--bg-tertiary);
            border-radius: 8px;
            margin: 20px 0;
        }
        .qr-container img {
            border: 4px solid white;
            border-radius: 8px;
        }
        .secret-code {
            font-family: monospace;
            font-size: 18px;
            background: var(--bg-tertiary);
            padding: 12px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
            color: var(--accent-primary);
            letter-spacing: 2px;
        }
        .backup-codes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: 8px;
        }
        .backup-code {
            font-family: monospace;
            font-size: 16px;
            padding: 10px;
            background: var(--bg-secondary);
            border-radius: 4px;
            text-align: center;
            color: var(--accent-primary);
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box {
            background: var(--bg-tertiary);
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        .info-box ul {
            margin-left: 20px;
            color: var(--text-secondary);
        }
        .info-box li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <h1>🔐 Two-Factor Authentication</h1>
        <p class="subtitle">
            Status: 
            <?php if ($user['two_factor_enabled']): ?>
                <span class="status-badge status-enabled">✓ Enabled</span>
            <?php else: ?>
                <span class="status-badge status-disabled">Disabled</span>
            <?php endif; ?>
        </p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!$user['two_factor_enabled']): ?>
            <!-- Enable 2FA -->
            <div class="card">
                <h2>Enable Two-Factor Authentication</h2>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    Add an extra layer of security to your account by requiring a code from your authenticator app in addition to your password.
                </p>
                
                <div class="info-box">
                    <h3>📱 Supported Apps</h3>
                    <ul>
                        <li>Google Authenticator</li>
                        <li>Microsoft Authenticator</li>
                        <li>Authy</li>
                        <li>1Password</li>
                        <li>Any TOTP-compatible app</li>
                    </ul>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px;">Step 1: Scan QR Code</h3>
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                    Open your authenticator app and scan this QR code:
                </p>
                
                <div class="qr-container">
                    <img src="<?= htmlspecialchars($qrCode) ?>" alt="QR Code">
                </div>
                
                <p style="color: var(--text-secondary); margin-bottom: 10px; text-align: center;">
                    Or enter this secret key manually:
                </p>
                <div class="secret-code"><?= htmlspecialchars($tempSecret) ?></div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px;">Step 2: Verify Code</h3>
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                    Enter the 6-digit code from your authenticator app to verify:
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="enable">
                    <input type="hidden" name="temp_secret" value="<?= htmlspecialchars($tempSecret) ?>">
                    
                    <div class="form-group">
                        <label>6-Digit Code</label>
                        <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="000000">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enable 2FA</button>
                </form>
            </div>
            
            <?php if (!empty($backupCodes)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h2>⚠️ Save Your Backup Codes</h2>
                    <div class="warning-box">
                        <strong>Important!</strong> Save these backup codes in a secure location. Each code can be used once if you lose access to your authenticator app.
                    </div>
                    
                    <div class="backup-codes">
                        <?php foreach ($backupCodes as $code): ?>
                            <div class="backup-code"><?= htmlspecialchars($code) ?></div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button onclick="printBackupCodes()" class="btn btn-secondary">🖨️ Print Codes</button>
                    <button onclick="copyBackupCodes()" class="btn btn-secondary">📋 Copy Codes</button>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Disable 2FA -->
            <div class="card">
                <h2>Two-Factor Authentication is Enabled</h2>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    Your account is protected with two-factor authentication. You'll need both your password and a code from your authenticator app to log in.
                </p>
                
                <div class="info-box">
                    <h3>✓ Active Security</h3>
                    <ul>
                        <li>Authenticator app configured</li>
                        <li>10 backup codes available</li>
                        <li>Login requires 6-digit code</li>
                    </ul>
                </div>
                
                <h3 style="margin-top: 30px; margin-bottom: 15px;">Disable Two-Factor Authentication</h3>
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                    Enter your password to disable 2FA:
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="disable">
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">Disable 2FA</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        function copyBackupCodes() {
            const codes = <?= json_encode($backupCodes ?? []) ?>;
            const text = codes.join('\n');
            navigator.clipboard.writeText(text).then(() => {
                alert('Backup codes copied to clipboard!');
            });
        }
        
        function printBackupCodes() {
            window.print();
        }
    </script>
</body>
</html>
