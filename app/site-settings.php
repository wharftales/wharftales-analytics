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
    </style>
</head>
<body>
    <div class="header">
        <a href="/index.php" class="logo">📊 Analytics</a>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h1><?= htmlspecialchars($site['name']) ?></h1>
            <p class="subtitle">Site configuration and tracking setup</p>
            
            <div class="info-row">
                <div class="info-label">Domain:</div>
                <div class="info-value"><?= htmlspecialchars($site['domain']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tracking ID:</div>
                <div class="info-value"><?= htmlspecialchars($site['tracking_id']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Created:</div>
                <div class="info-value"><?= date('F j, Y', strtotime($site['created_at'])) ?></div>
            </div>
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
        
        <div style="margin-top: 20px;">
            <a href="/site/<?= $site['id'] ?>/" class="btn btn-primary">View Analytics</a>
            <a href="/index.php" class="btn btn-secondary">Back to Dashboard</a>
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
