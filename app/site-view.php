<?php
require_once 'config.php';

if (needsSetup()) {
    header('Location: /app/install.php');
    exit;
}

requireLogin();

$user = getCurrentUser();
$siteId = $_GET['id'] ?? 0;
$period = $_GET['period'] ?? '7d'; // 7d, 30d, 90d, custom
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

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

// Calculate date range
if ($period === 'custom' && !empty($customStart) && !empty($customEnd)) {
    $startDate = date('Y-m-d 00:00:00', strtotime($customStart));
    $endDate = date('Y-m-d 23:59:59', strtotime($customEnd));
} else {
    $days = 7;
    if ($period === '30d') $days = 30;
    if ($period === '90d') $days = 90;
    
    $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
    $endDate = date('Y-m-d H:i:s');
}

// Get total pageviews
$stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM pageviews 
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
");
$stmt->execute([$siteId, $startDate, $endDate]);
$totalViews = $stmt->fetch()['total'];

// Get unique visitors
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT visitor_hash) as total 
    FROM pageviews 
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
");
$stmt->execute([$siteId, $startDate, $endDate]);
$uniqueVisitors = $stmt->fetch()['total'];

// Get bounce rate (sessions with only 1 pageview)
$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN pv_count = 1 THEN 1 END) * 100.0 / COUNT(*) as bounce_rate
    FROM (
        SELECT session_hash, COUNT(*) as pv_count
        FROM pageviews
        WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
        GROUP BY session_hash
    )
");
$stmt->execute([$siteId, $startDate, $endDate]);
$bounceRate = round($stmt->fetch()['bounce_rate'] ?? 0, 1);

// Get average session duration (in seconds)
$stmt = $db->prepare("
    SELECT AVG(duration) as avg_duration
    FROM (
        SELECT 
            session_hash,
            (JULIANDAY(MAX(timestamp)) - JULIANDAY(MIN(timestamp))) * 86400 as duration
        FROM pageviews
        WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
        GROUP BY session_hash
        HAVING COUNT(*) > 1
    )
");
$stmt->execute([$siteId, $startDate, $endDate]);
$avgDuration = round($stmt->fetch()['avg_duration'] ?? 0);

// Get top pages
$stmt = $db->prepare("
    SELECT path, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY path
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$siteId, $startDate, $endDate]);
$topPages = $stmt->fetchAll();

// Get top referrers
$stmt = $db->prepare("
    SELECT 
        CASE 
            WHEN referrer = '' THEN 'Direct / None'
            ELSE referrer
        END as referrer,
        COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY referrer
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$siteId, $startDate, $endDate]);
$topReferrers = $stmt->fetchAll();

// Get browser stats
$stmt = $db->prepare("
    SELECT browser, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY browser
    ORDER BY views DESC
    LIMIT 5
");
$stmt->execute([$siteId, $startDate, $endDate]);
$browsers = $stmt->fetchAll();

// Get OS stats
$stmt = $db->prepare("
    SELECT os, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY os
    ORDER BY views DESC
    LIMIT 5
");
$stmt->execute([$siteId, $startDate, $endDate]);
$operatingSystems = $stmt->fetchAll();

// Get device stats
$stmt = $db->prepare("
    SELECT device_type, COUNT(*) as views
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY device_type
    ORDER BY views DESC
");
$stmt->execute([$siteId, $startDate, $endDate]);
$devices = $stmt->fetchAll();

// Get daily views for chart
$stmt = $db->prepare("
    SELECT 
        DATE(timestamp) as date,
        COUNT(*) as views,
        COUNT(DISTINCT visitor_hash) as visitors
    FROM pageviews
    WHERE site_id = ? AND timestamp >= ? AND timestamp <= ?
    GROUP BY DATE(timestamp)
    ORDER BY date ASC
");
$stmt->execute([$siteId, $startDate, $endDate]);
$dailyStats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> - Analytics</title>
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
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info {
            font-size: 14px;
            color: #666;
        }
        .admin-badge {
            background: #667eea;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            font-size: 28px;
            color: #333;
        }
        .period-selector {
            display: flex;
            gap: 10px;
        }
        .period-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .period-btn:hover {
            border-color: #667eea;
        }
        .period-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #333;
        }
        .stat-unit {
            font-size: 14px;
            color: #999;
            margin-left: 4px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .data-table {
            width: 100%;
        }
        .data-table th {
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }
        .data-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .bar {
            height: 6px;
            background: #667eea;
            border-radius: 3px;
            margin-top: 6px;
        }
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        .chart-container {
            height: 300px;
            margin-top: 20px;
        }
        .date-range-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 20px;
            padding: 16px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); 
            margin-bottom: 20px;
        }
        .date-range-form label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        .date-range-form input[type="date"] {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        .date-range-form input[type="date"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .date-range-form button {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }
        .date-range-form button:hover {
            background: #5568d3;
        }
        @media (max-width: 768px) {
            .two-col {
                grid-template-columns: 1fr;
            }
            .date-range-form {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($site['name']) ?></h1>
                <p style="color: #666; margin-top: 4px;"><?= htmlspecialchars($site['domain']) ?></p>
            </div>
            <div class="period-selector">
                <a href="/site/<?= $siteId ?>/7d" class="period-btn <?= $period === '7d' ? 'active' : '' ?>">7 Days</a>
                <a href="/site/<?= $siteId ?>/30d" class="period-btn <?= $period === '30d' ? 'active' : '' ?>">30 Days</a>
                <a href="/site/<?= $siteId ?>/90d" class="period-btn <?= $period === '90d' ? 'active' : '' ?>">90 Days</a>
            </div>
        </div>
        
        <form class="date-range-form" method="GET" action="/site/<?= $siteId ?>/">
            <input type="hidden" name="period" value="custom">
            <label>Custom Range:</label>
            <input type="date" name="start" value="<?= $customStart ?>" required>
            <label>to</label>
            <input type="date" name="end" value="<?= $customEnd ?>" required>
            <button type="submit">Apply</button>
            <?php if ($period === 'custom'): ?>
                <a href="/site/<?= $siteId ?>/" style="color: #667eea; text-decoration: none; font-size: 14px;">Clear</a>
            <?php endif; ?>
        </form>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Pageviews</div>
                <div class="stat-value"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Unique Visitors</div>
                <div class="stat-value"><?= number_format($uniqueVisitors) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Bounce Rate</div>
                <div class="stat-value"><?= $bounceRate ?><span class="stat-unit">%</span></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg. Session Duration</div>
                <div class="stat-value"><?= gmdate('i:s', $avgDuration) ?><span class="stat-unit">min</span></div>
            </div>
        </div>
        
        <div class="card">
            <h2>📈 Daily Traffic</h2>
            <?php if (empty($dailyStats)): ?>
                <p style="color: #666; text-align: center; padding: 40px;">No data available yet. Install the tracking script to start collecting data.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Pageviews</th>
                            <th>Visitors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dailyStats as $stat): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($stat['date'])) ?></td>
                                <td><?= number_format($stat['views']) ?></td>
                                <td><?= number_format($stat['visitors']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="two-col">
            <div class="card">
                <h2>📄 Top Pages</h2>
                <?php if (empty($topPages)): ?>
                    <p style="color: #666;">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Page</th>
                                <th style="text-align: right;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxViews = $topPages[0]['views'];
                            foreach ($topPages as $page): 
                                $percentage = ($page['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($page['path']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($page['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>🔗 Top Referrers</h2>
                <?php if (empty($topReferrers)): ?>
                    <p style="color: #666;">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th style="text-align: right;">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxViews = $topReferrers[0]['views'];
                            foreach ($topReferrers as $ref): 
                                $percentage = ($ref['views'] / $maxViews) * 100;
                                $display = $ref['referrer'];
                                if ($display !== 'Direct / None') {
                                    $parsed = parse_url($display);
                                    $display = $parsed['host'] ?? $display;
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($display) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($ref['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="two-col">
            <div class="card">
                <h2>🌐 Browsers</h2>
                <?php if (empty($browsers)): ?>
                    <p style="color: #666;">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <tbody>
                            <?php 
                            $maxViews = $browsers[0]['views'];
                            foreach ($browsers as $browser): 
                                $percentage = ($browser['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($browser['browser']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($browser['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>💻 Operating Systems</h2>
                <?php if (empty($operatingSystems)): ?>
                    <p style="color: #666;">No data yet</p>
                <?php else: ?>
                    <table class="data-table">
                        <tbody>
                            <?php 
                            $maxViews = $operatingSystems[0]['views'];
                            foreach ($operatingSystems as $os): 
                                $percentage = ($os['views'] / $maxViews) * 100;
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($os['os']) ?></div>
                                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                                    </td>
                                    <td style="text-align: right; font-weight: 600;">
                                        <?= number_format($os['views']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>📱 Device Types</h2>
            <?php if (empty($devices)): ?>
                <p style="color: #666;">No data yet</p>
            <?php else: ?>
                <table class="data-table">
                    <tbody>
                        <?php 
                        $maxViews = $devices[0]['views'];
                        foreach ($devices as $device): 
                            $percentage = ($device['views'] / $maxViews) * 100;
                        ?>
                            <tr>
                                <td style="width: 200px;">
                                    <?= htmlspecialchars(ucfirst($device['device_type'])) ?>
                                </td>
                                <td>
                                    <div class="bar" style="width: <?= $percentage ?>%"></div>
                                </td>
                                <td style="text-align: right; font-weight: 600; width: 100px;">
                                    <?= number_format($device['views']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="/app/site-settings.php?id=<?= $siteId ?>" class="btn btn-secondary">⚙️ Settings</a>
            <a href="/index.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
