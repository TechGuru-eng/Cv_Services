<?php
// api/cancel_queue.php
// Clears pending and failed emails from the queue, effectively halting any active campaign.
// Supported methods: POST, OPTIONS

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

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

// 2. Security Token Verification
$expectedToken = getenv('MONCV_MAILBATCH_TOKEN') ?: '';
$token = '';

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
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
}

if (empty($token) || $token !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid security token']);
    exit;
}

try {
    // 3. Clear Table or Pending elements
    // We can clear pending items or just delete all items in queue to reset completely
    $stmt = $pdo->prepare("DELETE FROM mailbatch_queue WHERE status = 'pending'");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'Automated campaign successfully cancelled and pending queue cleared.',
        'cancelled_count' => $deletedCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error cancelling campaign: ' . $e->getMessage()]);
}
?>
