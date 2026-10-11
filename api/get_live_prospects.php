<?php
// api/get_live_prospects.php
// Secure API endpoint for MailBatch Pro to fetch live retargeting prospects.
// Supported methods: GET

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Establish DB connection
$dbPath = __DIR__ . '/../admin/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../config/db.php';
}
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection helper not found']);
    exit;
}
require_once $dbPath;

// 2. Security Check (Validate pre-shared secret key)
$expectedToken = getenv('MONCV_MAILBATCH_TOKEN') ?: '';
$token = '';

// Check Authorization Header
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
}

$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
} else {
    $token = $_GET['token'] ?? '';
}

if (empty($token) || $token !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid security token']);
    exit;
}

try {
    $prospects = [];
    try {
        // Fetch prospects with pending or lead_audit status
        $stmtProspects = $pdo->query("
            SELECT id, name, email, whatsapp, city, package_name, total_amount, status, created_at, cv_score, target_job, 'prospect' as source_table 
            FROM prospects 
            WHERE status IN ('pending', 'lead_audit') 
            ORDER BY id DESC LIMIT 100
        ");
        if ($stmtProspects) {
            $prospects = $stmtProspects->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Silent catch if table/column query fails
    }

    $orders = [];
    try {
        // Fetch orders that are pending payment
        $stmtOrders = $pdo->query("
            SELECT id, name, email, whatsapp, '' as city, package_name, amount as total_amount, payment_status as status, created_at, NULL as cv_score, '' as target_job, 'order' as source_table 
            FROM orders 
            WHERE payment_status = 'pending' 
            ORDER BY id DESC LIMIT 100
        ");
        if ($stmtOrders) {
            $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Silent catch if table/column query fails
    }

    // Combine and sort by date descending
    $allLeads = array_merge($prospects, $orders);
    usort($allLeads, function ($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    echo json_encode([
        'success' => true,
        'count' => count($allLeads),
        'data' => $allLeads
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching prospects: ' . $e->getMessage()]);
}
?>
