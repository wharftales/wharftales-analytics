<?php
require_once 'config.php';
require_once 'TwoFactor.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

// Check if 2FA verification is pending
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_timestamp'])) {
    header('Location: /app/login.php');
    exit;
}

// Expire 2FA session after 5 minutes
if (time() - $_SESSION['2fa_timestamp'] > 300) {
    unset($_SESSION['2fa_user_id']);
    unset($_SESSION['2fa_timestamp']);
    header('Location: /app/login.php?error=timeout');
    exit;
}

$error = '';
$db = getDb();

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['2fa_user_id']]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['2fa_user_id']);
    unset($_SESSION['2fa_timestamp']);
    header('Location: /app/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    if (empty($code)) {
        $error = "Please enter the 6-digit code";
    } else {
        // Try authenticator code first
        if (TwoFactor::verifyCode($user['two_factor_secret'], $code)) {
            // Valid code - complete login
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_timestamp']);
            header('Location: /index.php');
            exit;
        } else {
            // Try backup codes
            $backupCodes = json_decode($user['two_factor_backup_codes'], true) ?: [];
            $codeIndex = array_search($code, $backupCodes);
            
            if ($codeIndex !== false) {
                // Valid backup code - use it and remove it
                array_splice($backupCodes, $codeIndex, 1);
                
                $stmt = $db->prepare("UPDATE users SET two_factor_backup_codes = ? WHERE id = ?");
                $stmt->execute([json_encode($backupCodes), $user['id']]);
                
                // Complete login
                $_SESSION['user_id'] = $user['id'];
                unset($_SESSION['2fa_user_id']);
                unset($_SESSION['2fa_timestamp']);
                header('Location: /index.php');
                exit;
            } else {
                $error = "Invalid code. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Analytics Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .icon {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 4px;
            font-family: monospace;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .help-text {
            color: #999;
            font-size: 13px;
            margin-top: 15px;
            text-align: center;
        }
        .backup-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .backup-link:hover {
            text-decoration: underline;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔐</div>
        <h1>Verification Required</h1>
        <p class="subtitle">Enter the 6-digit code from your authenticator app</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Authentication Code</label>
                <input type="text" name="code" maxlength="6" pattern="[0-9A-Z]{6,8}" required autofocus placeholder="000000">
            </div>
            
            <button type="submit">Verify & Sign In</button>
        </form>
        
        <p class="help-text">
            You can also use one of your backup codes if you've lost access to your authenticator app.
        </p>
        
        <a href="/app/login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
