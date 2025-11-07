<?php
require_once 'config.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();
$siteId = $_GET['id'] ?? 0;

$db = getDb();

// Common timezones
$timezones = [
    'UTC' => 'UTC (Coordinated Universal Time)',
    'America/New_York' => 'Eastern Time (US & Canada)',
    'America/Chicago' => 'Central Time (US & Canada)',
    'America/Denver' => 'Mountain Time (US & Canada)',
    'America/Los_Angeles' => 'Pacific Time (US & Canada)',
    'America/Anchorage' => 'Alaska',
    'Pacific/Honolulu' => 'Hawaii',
    'Europe/London' => 'London',
    'Europe/Paris' => 'Paris, Berlin, Rome',
    'Europe/Athens' => 'Athens, Istanbul',
    'Europe/Moscow' => 'Moscow',
    'Asia/Dubai' => 'Dubai',
    'Asia/Kolkata' => 'Mumbai, Kolkata',
    'Asia/Bangkok' => 'Bangkok, Hanoi',
    'Asia/Singapore' => 'Singapore',
    'Asia/Hong_Kong' => 'Hong Kong',
    'Asia/Tokyo' => 'Tokyo, Osaka',
    'Australia/Sydney' => 'Sydney, Melbourne',
    'Pacific/Auckland' => 'Auckland',
];

// Check access
if ($user['is_admin']) {
    $stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
    $stmt->execute([$siteId]);
} else {
    $stmt = $db->prepare("
        SELECT s.* FROM sites s
        INNER JOIN user_sites us ON s.id = us.site_id
        WHERE s.id = ? AND us.user_id = ?
    ");
    $stmt->execute([$siteId, $user['id']]);
}

$site = $stmt->fetch();

if (!$site) {
    header('Location: /index.php');
    exit;
}

// Handle site update
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site'])) {
    $name = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $timezone = $_POST['timezone'] ?? 'UTC';
    
    if (empty($name)) {
        $error = 'Site name is required';
    } elseif (empty($domain)) {
        $error = 'Domain is required';
    } else {
        $stmt = $db->prepare("UPDATE sites SET name = ?, domain = ?, timezone = ? WHERE id = ?");
        $stmt->execute([$name, $domain, $timezone, $siteId]);
        
        // Reload site data
        if ($user['is_admin']) {
            $stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$siteId]);
        } else {
            $stmt = $db->prepare("
                SELECT s.* FROM sites s
                INNER JOIN user_sites us ON s.id = us.site_id
                WHERE s.id = ? AND us.user_id = ?
            ");
            $stmt->execute([$siteId, $user['id']]);
        }
        $site = $stmt->fetch();
        
        $_SESSION['success'] = 'Site updated successfully!';
        header('Location: /app/site-settings.php?id=' . $siteId);
        exit;
    }
}

$trackingUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://" . $_SERVER['HTTP_HOST'] . "/track.js";

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> - Settings</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 30px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        h2 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #333;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            width: 150px;
            color: #333;
            font-size: 14px;
        }
        .info-value {
            flex: 1;
            color: #666;
            font-size: 14px;
            font-family: 'Monaco', 'Courier New', monospace;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 16px;
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 12px;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
        }
        .copy-btn:hover {
            background: #5568d3;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            margin-left: 10px;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #1565c0;
            padding: 16px;
            border-radius: 6px;
            margin-top: 16px;
            font-size: 14px;
            line-height: 1.6;
        }
        .alert-info strong {
            display: block;
            margin-bottom: 8px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h1>⚙️ Site Settings</h1>
            <p class="subtitle">Update site information and configuration</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Site Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($site['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="domain">Domain</label>
                    <input type="text" id="domain" name="domain" value="<?= htmlspecialchars($site['domain']) ?>" required placeholder="example.com">
                </div>
                
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select id="timezone" name="timezone" required>
                        <?php foreach ($timezones as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($site['timezone'] ?? 'UTC') === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Tracking ID:</div>
                    <div class="info-value"><?= htmlspecialchars($site['tracking_id']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Created:</div>
                    <div class="info-value"><?= date('F j, Y', strtotime($site['created_at'])) ?></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_site" class="btn btn-primary">Save Changes</button>
                    <a href="/site/<?= $site['id'] ?>/" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Installation Instructions</h2>
            <p style="color: #666; margin-bottom: 16px;">
                Add this script tag to your website's HTML, just before the closing <code>&lt;/body&gt;</code> tag:
            </p>
            
            <div class="code-block">
                <button class="copy-btn" onclick="copyCode()">Copy</button>
                <pre id="tracking-code">&lt;script data-site-id="<?= htmlspecialchars($site['tracking_id']) ?>" src="<?= htmlspecialchars($trackingUrl) ?>"&gt;&lt;/script&gt;</pre>
            </div>
            
            <div class="alert-info">
                <strong>🔒 Privacy & GDPR Compliance</strong>
                This tracking script is cookieless and GDPR-compliant. It:
                <ul style="margin-top: 8px; margin-left: 20px;">
                    <li>Does not use cookies or local storage</li>
                    <li>Does not track personal information</li>
                    <li>Creates anonymous visitor hashes using IP + User Agent (rotated daily)</li>
                    <li>Only tracks page views and referrers</li>
                    <li>Respects Do Not Track browser settings</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function copyCode() {
            const code = document.getElementById('tracking-code').textContent;
            navigator.clipboard.writeText(code).then(() => {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
    </script>
</body>
</html>
