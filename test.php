<?php
// Simplest possible test - NO dependencies
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "✅ PHP is working!<br><br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>PDO:</strong> " . (extension_loaded('pdo') ? '✅ YES' : '❌ NO') . "<br>";
echo "<strong>PDO SQLite:</strong> " . (extension_loaded('pdo_sqlite') ? '✅ YES' : '❌ NO') . "<br>";
echo "<strong>Current directory:</strong> " . __DIR__ . "<br>";
echo "<strong>Data directory exists:</strong> " . (is_dir(__DIR__ . '/data') ? '✅ YES' : '⚠️ NO (will be created)') . "<br>";
echo "<strong>Parent writable:</strong> " . (is_writable(__DIR__) ? '✅ YES' : '❌ NO') . "<br>";

echo "<br><hr><br>";
echo "If you see this message, PHP is working correctly.<br><br>";
echo "Next steps:<br>";
echo "1. Visit <a href='debug.php'>debug.php</a> for detailed diagnostics<br>";
echo "2. Visit <a href='health.php'>health.php</a> to check requirements<br>";
echo "3. Visit <a href='index.php'>index.php</a> to start installation<br>";
