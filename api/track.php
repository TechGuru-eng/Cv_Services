// api/track.php
// Advanced visitor tracking system (UTMs + Events)
// Bot Detection Included

session_start();

require_once '../admin/config/db.php';

// Handle JSON POST requests for events
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['event_name'])) {
    $visit_id = $_SESSION['visit_id'] ?? null;
    
    if ($visit_id) {
        try {
            // Update activity timestamp even on events
            $pdo->prepare("UPDATE stats_visits SET updated_at = NOW() WHERE id = ?")->execute([$visit_id]);

            $stmt = $pdo->prepare("INSERT INTO stats_events (visit_id, event_name, event_data) VALUES (?, ?, ?)");
            $stmt->execute([$visit_id, $input['event_name'], json_encode($input['event_data'] ?? [])]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No session visit_id']);
    }
    exit;
}

// Otherwise, handle the standard visit logging (Pixel)
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Content-Type: image/gif");
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

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

// Bot Detection logic
function isBot($ua) {
    if (empty($ua)) return false;
    $bot_patterns = [
        'HeadlessChrome', 'bot', 'crawler', 'spider', 'slurp', 'lighthouse', 
        'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'facebookexternalhit',
        'twitterbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link preview',
        'showyoubot', 'outbrain', 'pinterest/0.', 'developers.google.com/+/web/snippet',
        'slackbot', 'vkShare', 'W3C_Validator', 'redditbot', 'Applebot', 'WhatsApp', 
        'Flipboard', 'tumblr', 'bitlybot', 'SkypeShell', 'msnbot', 'ZSTV', 'AhrefsBot',
        'python-requests', 'node-fetch', 'axios', 'curl', 'Go-http-client', 'postman'
    ];
    foreach ($bot_patterns as $pattern) {
        if (stripos($ua, $pattern) !== false) return true;
    }
    // Suspiciously short or generic user agents
    if (strlen($ua) < 30 && !preg_match('/Mobile|Safari|Firefox|Chrome/i', $ua)) return true;
    
    return false;
}

$is_bot = isBot($user_agent) ? 1 : 0;

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
require_once 'includes/geo_helper.php';
$country = initVisitorSession();

// 4. Save/Update Visit & Store ID in session
$visit_id = $_SESSION['visit_id'] ?? null;

try {
    if ($visit_id) {
        // Update existing visit
        $stmt = $pdo->prepare("UPDATE stats_visits SET page_url = ?, updated_at = NOW(), browser = ? WHERE id = ?");
        $stmt->execute([$page_path, $browser, $visit_id]);
    } else {
        // Unique visitor check (per day)
        $is_unique = 0;
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT id FROM stats_visits WHERE ip_address = ? AND DATE(created_at) = ? LIMIT 1");
        $stmt->execute([$ip, $today]);
        if (!$stmt->fetch()) {
            $is_unique = 1;
        }

        // New visit
        $stmt = $pdo->prepare("INSERT INTO stats_visits (ip_address, country, page_url, user_agent, browser, is_unique, utm_source, utm_medium, utm_campaign, is_bot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ip, $country, $page_path, $user_agent, $browser, $is_unique, $utm_source, $utm_medium, $utm_campaign, $is_bot]);
        $_SESSION['visit_id'] = $pdo->lastInsertId();
    }
} catch (Exception $e) {
    error_log("Tracking Error: " . $e->getMessage());
}
?>
