<?php
// Tracking endpoint - no session needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once 'config.php';

try {
    // Get data from POST
    $data = [];
    
    // Handle both JSON and form data
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
    } else {
        $data = $_POST;
    }
    
    $siteId = $data['site_id'] ?? '';
    $path = $data['path'] ?? '/';
    $referrer = $data['referrer'] ?? '';
    
    if (empty($siteId)) {
        http_response_code(400);
        echo json_encode(['error' => 'site_id is required']);
        exit;
    }
    
    $db = getDb();
    
    // Verify site exists and get domain
    $stmt = $db->prepare("SELECT id, domain FROM sites WHERE tracking_id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode(['error' => 'Site not found']);
        exit;
    }
    
    // Verify origin matches domain (security check)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($origin)) {
        $originHost = parse_url($origin, PHP_URL_HOST);
        if ($originHost !== $site['domain'] && $originHost !== 'www.' . $site['domain']) {
            http_response_code(403);
            echo json_encode(['error' => 'Domain mismatch']);
            exit;
        }
    }
    
    // Get visitor information
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Create anonymous visitor hash (changes daily for privacy)
    $date = date('Y-m-d');
    $visitorHash = hash('sha256', $ip . $userAgent . $date . SALT);
    
    // Create session hash (persists for 30 minutes)
    $sessionTime = floor(time() / 1800); // 30-minute buckets
    $sessionHash = hash('sha256', $ip . $userAgent . $sessionTime . SALT);
    
    // Parse user agent for browser/OS/device
    $browser = 'Unknown';
    $os = 'Unknown';
    $deviceType = 'desktop';
    
    // Simple user agent parsing
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $userAgent)) {
        $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
    }
    
    if (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari\/[\d.]+/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge\/[\d.]+/', $userAgent)) {
        $browser = 'Edge';
    }
    
    if (preg_match('/Windows/', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac OS X/', $userAgent)) {
        $os = 'macOS';
    } elseif (preg_match('/Linux/', $userAgent)) {
        $os = 'Linux';
    } elseif (preg_match('/Android/', $userAgent)) {
        $os = 'Android';
    } elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
        $os = 'iOS';
    }
    
    // Insert pageview
    $stmt = $db->prepare("
        INSERT INTO pageviews 
        (site_id, path, referrer, user_agent, browser, os, device_type, visitor_hash, session_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $site['id'],
        $path,
        $referrer,
        $userAgent,
        $browser,
        $os,
        $deviceType,
        $visitorHash,
        $sessionHash
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
