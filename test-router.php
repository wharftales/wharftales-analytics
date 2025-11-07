<?php
// Test if router is working
echo "<h1>Router Test</h1>";

$_SERVER['REQUEST_URI'] = '/site/1/';
$_SERVER['SCRIPT_NAME'] = '/index.php';

echo "<p><strong>Testing URL:</strong> /site/1/</p>";

// Simulate router
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = strtok($requestUri, '?');
$path = trim($path, '/');

echo "<p><strong>Parsed path:</strong> $path</p>";

if (preg_match('#^site/(\d+)/?$#', $path, $matches)) {
    echo "<p style='color: green;'><strong>✅ Match found!</strong></p>";
    echo "<p>Site ID: " . $matches[1] . "</p>";
    echo "<p>Would load: /app/site-view.php?id=" . $matches[1] . "</p>";
} else {
    echo "<p style='color: red;'><strong>❌ No match</strong></p>";
}

echo "<hr>";
echo "<h2>Test Different URLs</h2>";

$testUrls = [
    '/site/1/',
    '/site/1',
    '/site/1/30d',
    '/site/5/7d',
    '/analytics/site/1/',
];

foreach ($testUrls as $testUrl) {
    $path = trim(strtok($testUrl, '?'), '/');
    
    // Remove base path
    $scriptName = dirname('/analytics/index.php');
    if ($scriptName !== '/') {
        $path = str_replace(trim($scriptName, '/') . '/', '', $path);
    }
    
    echo "<p><strong>$testUrl</strong> → ";
    
    if (preg_match('#^site/(\d+)/?$#', $path, $matches)) {
        echo "<span style='color: green;'>✅ /app/site-view.php?id={$matches[1]}</span>";
    } elseif (preg_match('#^site/(\d+)/(\d+d)/?$#', $path, $matches)) {
        echo "<span style='color: green;'>✅ /app/site-view.php?id={$matches[1]}&period={$matches[2]}</span>";
    } else {
        echo "<span style='color: red;'>❌ No match (path: $path)</span>";
    }
    
    echo "</p>";
}
