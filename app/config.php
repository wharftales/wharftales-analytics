<?php
// Configuration file
define('DB_PATH', dirname(__DIR__) . '/data/analytics.db');
define('SALT', 'change_this_to_random_string_' . md5(__DIR__));

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// Database initialization
function getDb() {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $db;
}

// Check if setup is needed
function needsSetup() {
    if (!file_exists(DB_PATH)) {
        return true;
    }
    $db = getDb();
    $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
    return $result['count'] == 0;
}

// Authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /app/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['is_admin'] == 1;
}
