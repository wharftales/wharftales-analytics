<?php
// Simple diagnostic script to check tracking data
require_once __DIR__ . '/app/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Data Check</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        h2 { color: #333; margin-top: 30px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-info {
            background: #e3f2fd;
            color: #1565c0;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 16px;
            border-radius: 6px;
            margin: 20px 0;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Monaco', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Analytics Data Check</h1>
        
        <?php
        try {
            $db = getDb();
            
            // Check sites
            echo "<h2>Sites</h2>";
            $sites = $db->query("SELECT * FROM sites")->fetchAll();
            
            if (empty($sites)) {
                echo "<div class='info'>No sites configured yet.</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>Domain</th><th>Tracking ID</th><th>Created</th></tr>";
                foreach ($sites as $site) {
                    echo "<tr>";
                    echo "<td>{$site['id']}</td>";
                    echo "<td>" . htmlspecialchars($site['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($site['domain']) . "</td>";
                    echo "<td><code>" . htmlspecialchars($site['tracking_id']) . "</code></td>";
                    echo "<td>" . date('Y-m-d H:i', strtotime($site['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Check pageviews
            echo "<h2>Pageviews</h2>";
            $totalViews = $db->query("SELECT COUNT(*) as count FROM pageviews")->fetch()['count'];
            
            echo "<div class='info'>";
            echo "<strong>Total Pageviews:</strong> <span class='badge badge-info'>{$totalViews}</span>";
            echo "</div>";
            
            if ($totalViews > 0) {
                echo "<h3>Recent Pageviews (Last 20)</h3>";
                $recentViews = $db->query("
                    SELECT 
                        p.*,
                        s.name as site_name
                    FROM pageviews p
                    LEFT JOIN sites s ON p.site_id = s.id
                    ORDER BY p.timestamp DESC
                    LIMIT 20
                ")->fetchAll();
                
                echo "<table>";
                echo "<tr><th>Time</th><th>Site</th><th>Path</th><th>Browser</th><th>OS</th><th>Device</th></tr>";
                foreach ($recentViews as $view) {
                    echo "<tr>";
                    echo "<td>" . date('Y-m-d H:i:s', strtotime($view['timestamp'])) . "</td>";
                    echo "<td>" . htmlspecialchars($view['site_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($view['path']) . "</td>";
                    echo "<td>" . htmlspecialchars($view['browser']) . "</td>";
                    echo "<td>" . htmlspecialchars($view['os']) . "</td>";
                    echo "<td>" . htmlspecialchars($view['device_type']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Stats by site
                echo "<h3>Stats by Site</h3>";
                $statsBySite = $db->query("
                    SELECT 
                        s.name,
                        COUNT(*) as views,
                        COUNT(DISTINCT p.visitor_hash) as visitors
                    FROM pageviews p
                    LEFT JOIN sites s ON p.site_id = s.id
                    GROUP BY s.id
                ")->fetchAll();
                
                echo "<table>";
                echo "<tr><th>Site</th><th>Views</th><th>Unique Visitors</th></tr>";
                foreach ($statsBySite as $stat) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($stat['name']) . "</td>";
                    echo "<td><span class='badge badge-success'>{$stat['views']}</span></td>";
                    echo "<td><span class='badge badge-info'>{$stat['visitors']}</span></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='info'>";
                echo "<strong>No pageviews yet!</strong><br><br>";
                echo "To start tracking:<br>";
                echo "1. Make sure the tracking script is installed on your website<br>";
                echo "2. Visit your website<br>";
                echo "3. Check browser console for any errors<br>";
                echo "4. Refresh this page to see data<br><br>";
                echo "Tracking script format:<br>";
                if (!empty($sites)) {
                    echo "<code>&lt;script data-site-id=\"{$sites[0]['tracking_id']}\" src=\"https://yourdomain.com/analytics/track.js\"&gt;&lt;/script&gt;</code>";
                }
                echo "</div>";
            }
            
            // Check users
            echo "<h2>Users</h2>";
            $users = $db->query("SELECT id, name, email, is_admin, created_at FROM users")->fetchAll();
            
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>";
                if ($user['is_admin']) {
                    echo "<span class='badge badge-warning'>ADMIN</span>";
                } else {
                    echo "<span class='badge badge-info'>USER</span>";
                }
                echo "</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($user['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div class='info' style='margin-top: 40px;'>";
            echo "<strong>✅ Database connection successful!</strong><br>";
            echo "Database path: <code>" . DB_PATH . "</code>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #fee; border: 1px solid #fcc; padding: 16px; border-radius: 6px;'>";
            echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>
        
        <div style="margin-top: 40px; text-align: center;">
            <a href="/index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
