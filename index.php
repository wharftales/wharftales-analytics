<?php
require_once __DIR__ . '/app/config.php';

// Try router first (handles clean URLs without .htaccess)
// Only if the URL looks like a routed path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/site/') !== false && file_exists(__DIR__ . '/router.php')) {
    require __DIR__ . '/router.php';
    // Router will exit if it handles the request
}

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();
$db = getDb();

// Get user's sites
if ($user['is_admin']) {
    $sites = $db->query("SELECT * FROM sites ORDER BY created_at DESC")->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT s.* FROM sites s
        INNER JOIN user_sites us ON s.id = us.site_id
        WHERE us.user_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $sites = $stmt->fetchAll();
}

// Get stats for each site (last 7 days)
$siteStats = [];
$sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
foreach ($sites as $site) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as views,
            COUNT(DISTINCT visitor_hash) as visitors
        FROM pageviews
        WHERE site_id = ? AND timestamp >= ?
    ");
    $stmt->execute([$site['id'], $sevenDaysAgo]);
    $siteStats[$site['id']] = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Analytics Platform</title>
    <script src="/app/theme.js"></script>
    <link rel="stylesheet" href="/app/common.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        .page-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .site-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px var(--shadow);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .site-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px var(--shadow-hover);
            border-color: var(--accent-primary);
        }
        .site-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .site-domain {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 16px;
        }
        .site-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--bg-tertiary);
            border-radius: 8px;
        }
        .stat-item {
            flex: 1;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent-primary);
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-weight: 500;
        }
        .site-actions {
            display: flex;
            gap: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: 0 2px 8px var(--shadow);
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 24px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/app/header.php'; ?>
    
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Your Sites</h1>
            <p>Manage and view analytics for your websites</p>
        </div>
        
        <div>
            <a href="/app/site-add.php" class="btn btn-primary">+ Add New Site</a>
        </div>
        
        <?php if (empty($sites)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🌐</div>
                <h2>No sites yet</h2>
                <p>Add your first site to start tracking analytics</p>
                <a href="/app/site-add.php" class="btn btn-primary">Add Your First Site</a>
            </div>
        <?php else: ?>
            <div class="sites-grid">
                <?php foreach ($sites as $site): 
                    $stats = $siteStats[$site['id']];
                ?>
                    <div class="site-card">
                        <div class="site-name"><?= htmlspecialchars($site['name']) ?></div>
                        <div class="site-domain"><?= htmlspecialchars($site['domain']) ?></div>
                        <div class="site-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($stats['views']) ?></div>
                                <div class="stat-label">Views (7d)</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($stats['visitors']) ?></div>
                                <div class="stat-label">Visitors (7d)</div>
                            </div>
                        </div>
                        <div class="site-actions">
                            <a href="/site/<?= $site['id'] ?>/" class="btn btn-primary">View Analytics</a>
                            <a href="/app/site-settings.php?id=<?= $site['id'] ?>" class="btn btn-secondary">Settings</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
