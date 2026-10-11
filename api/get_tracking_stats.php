<?php
// api/get_tracking_stats.php
// Secure endpoint for MailBatch Pro to fetch open/click tracking logs and conversions.
// Supported methods: GET, OPTIONS

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
    // 3. Fetch all tracking events
    $events = [];
    $stmt = $pdo->query("
        SELECT email, action_type, subject_version, campaign_id, created_at 
        FROM mailbatch_tracking 
        ORDER BY id DESC LIMIT 2000
    ");
    if ($stmt) {
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Determine current payment/conversion status for tracked emails
    $statusMap = [];
    $emails = array_unique(array_column($events, 'email'));

    if (!empty($emails)) {
        // Chunk emails array to avoid SQL placeholder limits if there are many emails
        $emailChunks = array_chunk($emails, 100);
        
        foreach ($emailChunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            
            // Check prospects table status
            try {
                $stmtP = $pdo->prepare("SELECT email, status FROM prospects WHERE email IN ($placeholders)");
                $stmtP->execute($chunk);
                $rowsP = $stmtP->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowsP as $row) {
                    $statusMap[strtolower(trim($row['email']))] = $row['status'];
                }
            } catch (Exception $e) {
                // Silent catch
            }

            // Check orders table payment status
            try {
                $stmtO = $pdo->prepare("SELECT email, payment_status FROM orders WHERE email IN ($placeholders)");
                $stmtO->execute($chunk);
                $rowsO = $stmtO->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rowsO as $row) {
                    $emailKey = strtolower(trim($row['email']));
                    // If order payment status is paid/completed, it overrides prospect status
                    $statusMap[$emailKey] = $row['payment_status'];
                }
            } catch (Exception $e) {
                // Silent catch
            }
        }
    }

    echo json_encode([
        'success' => true,
        'events' => $events,
        'statusMap' => $statusMap
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching tracking stats: ' . $e->getMessage()]);
}
?>
