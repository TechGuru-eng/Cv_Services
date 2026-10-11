<?php
// api/track.php
// Advanced visitor tracking system (UTMs + Events)
// Bot Detection Included

session_start();

require_once __DIR__ . '/../admin/config/db.php';
header('Cache-Control: no-store');

$input = json_decode(file_get_contents('php://input'), true);
$isEvent = is_array($input) && isset($input['event_name']);
if ($isEvent) {
    header('Content-Type: application/json');
} else {
    header('Content-Type: image/gif');
}

// 1. Get basic info
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$page_path = parse_url($referrer, PHP_URL_PATH) ?: '/';

// Better Browser Detection
$browser = 'Unknown';
if (preg_match('/Opera|OPR/i', $user_agent)) $browser = 'Opera';
elseif (preg_match('/Edge/i', $user_agent)) $browser = 'Edge';
elseif (preg_match('/Chrome/i', $user_agent)) $browser = 'Chrome';
elseif (preg_match('/Safari/i', $user_agent)) $browser = 'Safari';
elseif (preg_match('/Firefox/i', $user_agent)) $browser = 'Firefox';
elseif (preg_match('/MSIE|Trident/i', $user_agent)) $browser = 'IE';

require_once __DIR__ . '/../includes/visitor-tracking.php';
$is_bot = site_visitor_is_bot($user_agent) ? 1 : 0;

// 2. Parse UTMs from Referrer
$utm_source = null;
$utm_medium = null;
$utm_campaign = null;

$query_str = parse_url($referrer, PHP_URL_QUERY);
if ($query_str) {
    parse_str($query_str, $params);
    $utm_source = $params['utm_source'] ?? null;
    $utm_medium = $params['utm_medium'] ?? null;
    $utm_campaign = $params['utm_campaign'] ?? null;
    
    // Capture Affiliate Referral
    $ref = $params['ref'] ?? null;
    if ($ref) {
        $_SESSION['affiliate_code'] = $ref;
    }
}

// 3. Identify Country (Geoloc)
require_once __DIR__ . '/includes/geo_helper.php';
$country = initVisitorSession();

// 4. Save/Update Visit & Store ID in session
$visit_id = $_SESSION['visit_id'] ?? null;

try {
    $visit_id = record_site_visit($pdo, [
        'ip' => $ip, 'country' => $country, 'page' => $page_path,
        'user_agent' => $user_agent, 'browser' => $browser, 'is_bot' => $is_bot,
        'utm_source' => $utm_source, 'utm_medium' => $utm_medium, 'utm_campaign' => $utm_campaign,
    ]);
    if ($isEvent) {
        $pdo->prepare("INSERT INTO stats_events (visit_id, event_name, event_data) VALUES (?, ?, ?)")
            ->execute([$visit_id, substr((string)$input['event_name'], 0, 100), json_encode($input['event_data'] ?? [])]);
        echo json_encode(['success' => true]);
    } else {
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
} catch (Exception $e) {
    if ($isEvent) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Tracking unavailable']); }
    error_log("Tracking Error: " . $e->getMessage());
}
?>
