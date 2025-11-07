<?php
require_once 'config.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    
    if (empty($name)) {
        $error = "Site name is required";
    } elseif (empty($domain)) {
        $error = "Domain is required";
    } else {
        try {
            $db = getDb();
            
            // Generate unique tracking ID
            $trackingId = 'site_' . bin2hex(random_bytes(16));
            
            $stmt = $db->prepare("INSERT INTO sites (name, domain, tracking_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $domain, $trackingId]);
            
            $siteId = $db->lastInsertId();
            
            // Grant access to current user (if not admin, they get auto-access)
            if (!$user['is_admin']) {
                $stmt = $db->prepare("INSERT INTO user_sites (user_id, site_id) VALUES (?, ?)");
                $stmt->execute([$user['id'], $siteId]);
            }
            
            $_SESSION['success'] = "Site added successfully!";
            header('Location: /app/site-settings.php?id=' . $siteId);
            exit;
        } catch (Exception $e) {
            $error = "Failed to add site: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Site - Analytics Platform</title>
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
            max-width: 600px;
            margin: 40px auto;
            padding: 0 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .help-text {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 12px 24px;
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
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
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
        .actions {
            margin-top: 30px;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="/index.php" class="logo">📊 Analytics</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h1>Add New Site</h1>
            <p class="subtitle">Start tracking analytics for a new website</p>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Site Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                           placeholder="My Awesome Website" required autofocus>
                    <div class="help-text">A friendly name to identify your site</div>
                </div>
                
                <div class="form-group">
                    <label>Domain</label>
                    <input type="text" name="domain" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>" 
                           placeholder="example.com" required>
                    <div class="help-text">
                        The domain where your site is hosted (without http:// or https://). 
                        Only requests from this domain will be tracked.
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Add Site</button>
                    <a href="/index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
