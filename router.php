<?php
// Simple PHP router - handles clean URLs like /site/1/

// Get the request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Remove query string
$path = strtok($requestUri, '?');

// Remove leading/trailing slashes
$path = trim($path, '/');

// Remove base path if running in subdirectory
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/') {
    $path = str_replace(trim($scriptName, '/') . '/', '', $path);
}

// Route: /site/{id} or /site/{id}/
if (preg_match('#^site/(\d+)/?$#', $path, $matches)) {
    $_GET['id'] = $matches[1];
    if (!isset($_GET['period'])) {
        $_GET['period'] = '7d'; // Default period
    }
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

// No route matched
return false;
