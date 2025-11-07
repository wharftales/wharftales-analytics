<?php
// Health check page - helps diagnose installation issues
header('Content-Type: text/html; charset=utf-8');

$checks = [];
$allGood = true;

// Check 1: PHP Version
$phpVersion = phpversion();
$checks['PHP Version'] = [
    'status' => version_compare($phpVersion, '7.4.0', '>='),
    'message' => $phpVersion . (version_compare($phpVersion, '7.4.0', '>=') ? ' ✅' : ' ❌ (Need 7.4+)'),
];
if (!$checks['PHP Version']['status']) $allGood = false;

// Check 2: PDO SQLite
$checks['PDO SQLite'] = [
    'status' => extension_loaded('pdo_sqlite'),
    'message' => extension_loaded('pdo_sqlite') ? 'Enabled ✅' : 'Missing ❌',
];
if (!$checks['PDO SQLite']['status']) $allGood = false;

// Check 3: Data directory
$dataDir = __DIR__ . '/data';
$checks['Data Directory'] = [
    'status' => is_dir($dataDir) || is_writable(__DIR__),
    'message' => is_dir($dataDir) 
        ? 'Exists ✅' 
        : (is_writable(__DIR__) ? 'Will be created ✅' : 'Cannot create ❌'),
];
if (!$checks['Data Directory']['status']) $allGood = false;

// Check 4: Data directory writable
if (is_dir($dataDir)) {
    $checks['Data Writable'] = [
        'status' => is_writable($dataDir),
        'message' => is_writable($dataDir) ? 'Yes ✅' : 'No ❌',
    ];
    if (!$checks['Data Writable']['status']) $allGood = false;
}

// Check 5: Database file
$dbPath = $dataDir . '/analytics.db';
if (file_exists($dbPath)) {
    $checks['Database File'] = [
        'status' => true,
        'message' => 'Exists ✅ (' . filesize($dbPath) . ' bytes)',
    ];
    
    // Check 6: Database readable
    $checks['Database Readable'] = [
        'status' => is_readable($dbPath),
        'message' => is_readable($dbPath) ? 'Yes ✅' : 'No ❌',
    ];
    if (!$checks['Database Readable']['status']) $allGood = false;
} else {
    $checks['Database File'] = [
        'status' => true,
        'message' => 'Not created yet (will be created during setup) ⏳',
    ];
}

// Check 7: .htaccess
$htaccessPath = __DIR__ . '/.htaccess';
$checks['.htaccess File'] = [
    'status' => file_exists($htaccessPath),
    'message' => file_exists($htaccessPath) ? 'Exists ✅' : 'Missing ❌',
];
if (!$checks['.htaccess File']['status']) $allGood = false;

// Check 8: mod_rewrite (best effort)
$checks['URL Rewriting'] = [
    'status' => function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : null,
    'message' => function_exists('apache_get_modules') 
        ? (in_array('mod_rewrite', apache_get_modules()) ? 'Enabled ✅' : 'Disabled ❌')
        : 'Cannot detect (check manually) ⚠️',
];

// Check 9: Session support
$checks['Session Support'] = [
    'status' => function_exists('session_start'),
    'message' => function_exists('session_start') ? 'Enabled ✅' : 'Missing ❌',
];
if (!$checks['Session Support']['status']) $allGood = false;

// Check 10: Config file
$configPath = __DIR__ . '/app/config.php';
$checks['Config File'] = [
    'status' => file_exists($configPath),
    'message' => file_exists($configPath) ? 'Exists ✅' : 'Missing ❌',
];
if (!$checks['Config File']['status']) $allGood = false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Check - Analytics Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        .status.good {
            background: #d4edda;
            color: #155724;
        }
        .status.bad {
            background: #f8d7da;
            color: #721c24;
        }
        .check-item {
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .check-name {
            font-weight: 600;
            color: #333;
        }
        .check-message {
            color: #666;
            font-family: monospace;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 16px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .info-box h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #333;
        }
        .info-box li {
            margin: 8px 0;
        }
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 System Health Check</h1>
        <div class="status <?= $allGood ? 'good' : 'bad' ?>">
            <?= $allGood ? '✅ All Systems Operational' : '⚠️ Issues Detected' ?>
        </div>
        
        <div class="checks">
            <?php foreach ($checks as $name => $check): ?>
                <div class="check-item">
                    <span class="check-name"><?= htmlspecialchars($name) ?></span>
                    <span class="check-message"><?= $check['message'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($allGood): ?>
            <div class="info-box">
                <h3>✅ Ready to Install</h3>
                <p>All system requirements are met. You can proceed with the installation.</p>
            </div>
            
            <div class="actions">
                <a href="/app/install.php" class="btn btn-primary">Start Installation</a>
                <a href="/index.php" class="btn btn-secondary">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>⚠️ Action Required</h3>
                <p><strong>Please fix the issues above before installing:</strong></p>
                <ul>
                    <?php if (!$checks['PHP Version']['status']): ?>
                        <li>Upgrade PHP to version 7.4 or higher</li>
                    <?php endif; ?>
                    <?php if (!$checks['PDO SQLite']['status']): ?>
                        <li>Enable PDO SQLite extension in php.ini</li>
                    <?php endif; ?>
                    <?php if (!$checks['Data Directory']['status']): ?>
                        <li>Make parent directory writable: <code>chmod 755 <?= __DIR__ ?></code></li>
                    <?php endif; ?>
                    <?php if (isset($checks['Data Writable']) && !$checks['Data Writable']['status']): ?>
                        <li>Make data directory writable: <code>chmod 755 <?= $dataDir ?></code></li>
                    <?php endif; ?>
                    <?php if (!$checks['.htaccess File']['status']): ?>
                        <li>Upload the .htaccess file to the root directory</li>
                    <?php endif; ?>
                    <?php if (!$checks['Session Support']['status']): ?>
                        <li>Enable session support in PHP</li>
                    <?php endif; ?>
                    <?php if (!$checks['Config File']['status']): ?>
                        <li>Upload all application files including /app/config.php</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="actions">
                <a href="javascript:location.reload()" class="btn btn-primary">Recheck</a>
            </div>
        <?php endif; ?>
        
        <div class="info-box" style="margin-top: 20px; background: #fff3cd; border-color: #ffc107;">
            <h3>📋 System Information</h3>
            <ul style="list-style: none; margin-left: 0;">
                <li><strong>PHP Version:</strong> <?= phpversion() ?></li>
                <li><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></li>
                <li><strong>Document Root:</strong> <code><?= __DIR__ ?></code></li>
                <li><strong>Database Path:</strong> <code><?= $dbPath ?></code></li>
            </ul>
        </div>
    </div>
</body>
</html>
