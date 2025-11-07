<?php
// Simple debug page - doesn't require config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Info</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .good { color: green; }
        .bad { color: red; }
        h2 { margin-top: 0; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Debug Information</h1>
    
    <div class="section">
        <h2>PHP Info</h2>
        <strong>PHP Version:</strong> <?= phpversion() ?><br>
        <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?><br>
        <strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?><br>
        <strong>Script Path:</strong> <?= __DIR__ ?><br>
    </div>
    
    <div class="section">
        <h2>Extensions</h2>
        <strong>PDO:</strong> <span class="<?= extension_loaded('pdo') ? 'good' : 'bad' ?>"><?= extension_loaded('pdo') ? '✓ Loaded' : '✗ Missing' ?></span><br>
        <strong>PDO SQLite:</strong> <span class="<?= extension_loaded('pdo_sqlite') ? 'good' : 'bad' ?>"><?= extension_loaded('pdo_sqlite') ? '✓ Loaded' : '✗ Missing' ?></span><br>
        <strong>Session:</strong> <span class="<?= function_exists('session_start') ? 'good' : 'bad' ?>"><?= function_exists('session_start') ? '✓ Available' : '✗ Missing' ?></span><br>
    </div>
    
    <div class="section">
        <h2>File System</h2>
        <?php
        $dataDir = __DIR__ . '/data';
        $dbPath = $dataDir . '/analytics.db';
        $configPath = __DIR__ . '/app/config.php';
        $htaccessPath = __DIR__ . '/.htaccess';
        ?>
        <strong>Data Directory:</strong> 
        <?php if (is_dir($dataDir)): ?>
            <span class="good">✓ Exists</span>
            (Writable: <?= is_writable($dataDir) ? '<span class="good">Yes</span>' : '<span class="bad">No</span>' ?>)
        <?php else: ?>
            <span class="bad">✗ Does not exist</span>
            (Parent writable: <?= is_writable(__DIR__) ? '<span class="good">Yes</span>' : '<span class="bad">No</span>' ?>)
        <?php endif; ?>
        <br>
        
        <strong>Database File:</strong> 
        <?php if (file_exists($dbPath)): ?>
            <span class="good">✓ Exists</span> (<?= filesize($dbPath) ?> bytes)
        <?php else: ?>
            <span class="bad">✗ Does not exist</span>
        <?php endif; ?>
        <br>
        
        <strong>Config File:</strong> 
        <span class="<?= file_exists($configPath) ? 'good' : 'bad' ?>"><?= file_exists($configPath) ? '✓ Exists' : '✗ Missing' ?></span>
        <br>
        
        <strong>.htaccess File:</strong> 
        <span class="<?= file_exists($htaccessPath) ? 'good' : 'bad' ?>"><?= file_exists($htaccessPath) ? '✓ Exists' : '✗ Missing' ?></span>
        <br>
    </div>
    
    <div class="section">
        <h2>Permissions</h2>
        <strong>Current directory:</strong> <?= __DIR__ ?><br>
        <strong>Permissions:</strong> <?= substr(sprintf('%o', fileperms(__DIR__)), -4) ?><br>
        <strong>Owner:</strong> <?= function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(__DIR__))['name'] : 'Unknown' ?><br>
        <strong>PHP User:</strong> <?= function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user() ?><br>
    </div>
    
    <div class="section">
        <h2>Test Config Load</h2>
        <?php
        if (file_exists($configPath)) {
            try {
                ob_start();
                require_once $configPath;
                $output = ob_get_clean();
                echo '<span class="good">✓ Config loaded successfully</span><br>';
                if (!empty($output)) {
                    echo '<strong>Output:</strong><pre>' . htmlspecialchars($output) . '</pre>';
                }
                echo '<strong>DB_PATH:</strong> ' . (defined('DB_PATH') ? DB_PATH : 'Not defined') . '<br>';
                echo '<strong>SALT:</strong> ' . (defined('SALT') ? '***hidden***' : 'Not defined') . '<br>';
            } catch (Exception $e) {
                echo '<span class="bad">✗ Error loading config:</span><br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
        } else {
            echo '<span class="bad">✗ Config file not found</span>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Test Database Connection</h2>
        <?php
        if (file_exists($dbPath)) {
            try {
                $testDb = new PDO('sqlite:' . $dbPath);
                echo '<span class="good">✓ Database connection successful</span><br>';
                
                // Try to query
                $tables = $testDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
                echo '<strong>Tables:</strong> ';
                if (empty($tables)) {
                    echo '<span class="bad">None (needs setup)</span>';
                } else {
                    echo '<span class="good">' . implode(', ', array_column($tables, 'name')) . '</span>';
                }
            } catch (Exception $e) {
                echo '<span class="bad">✗ Database error:</span><br>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            }
        } else {
            echo '<span class="bad">✗ Database file does not exist (will be created during setup)</span>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Error Log</h2>
        <?php
        $errorLog = ini_get('error_log');
        if ($errorLog && file_exists($errorLog) && is_readable($errorLog)) {
            echo '<strong>Log file:</strong> ' . $errorLog . '<br>';
            echo '<strong>Last 20 lines:</strong><pre>';
            $lines = file($errorLog);
            echo htmlspecialchars(implode('', array_slice($lines, -20)));
            echo '</pre>';
        } else {
            echo 'Error log not accessible or not configured<br>';
            echo '<strong>error_log setting:</strong> ' . ($errorLog ?: 'not set');
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        <?php if (extension_loaded('pdo_sqlite') && (is_writable(__DIR__) || is_writable($dataDir))): ?>
            <p class="good">✓ System looks ready for installation</p>
            <a href="/app/install.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">Go to Installation</a>
        <?php else: ?>
            <p class="bad">✗ Please fix the issues above before proceeding</p>
        <?php endif; ?>
    </div>
</body>
</html>
