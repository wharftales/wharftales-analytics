<?php
// Simplest possible test
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO SQLite: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Data directory exists: " . (is_dir(__DIR__ . '/data') ? 'YES' : 'NO') . "<br>";
echo "Parent writable: " . (is_writable(__DIR__) ? 'YES' : 'NO') . "<br>";
echo "<br>If you see this, PHP is working. Visit <a href='debug.php'>debug.php</a> for more info.";
