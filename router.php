<?php
// Simple PHP router - no .htaccess needed
// This handles clean URLs like /site/1/ without Apache rewrite rules

$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remove script name from URI to get the path
$basePath = str_replace(basename($scriptName), '', $scriptName);
$path = str_replace($basePath, '', $requestUri);

// Remove query string
$path = strtok($path, '?');

// Remove leading/trailing slashes
$path = trim($path, '/');

// Route: /site/{id} or /site/{id}/
if (preg_match('#^site/(\d+)/?$#', $path, $matches)) {
    $_GET['id'] = $matches[1];
    require __DIR__ . '/app/site-view.php';
    exit;
}

// Route: /site/{id}/{period}
if (preg_match('#^site/(\d+)/(\d+d)/?$#', $path, $matches)) {
    $_GET['id'] = $matches[1];
    $_GET['period'] = $matches[2];
    require __DIR__ . '/app/site-view.php';
    exit;
}

// No route matched - return false to let normal processing continue
return false;
