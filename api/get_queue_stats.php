<?php
// api/get_queue_stats.php
// Returns stats and logs for the active mailbatch_queue.
// Supported methods: GET, OPTIONS

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
$token = $_GET['token'] ?? '';

if (empty($token) || $token !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid security token']);
    exit;
}

try {
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mailbatch_queue'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode([
            'success' => true,
            'active_campaign' => false,
            'stats' => [
                'total' => 0,
                'pending' => 0,
                'sent' => 0,
                'failed' => 0,
                'percent' => 0
            ],
            'logs' => [],
            'next_send' => null
        ]);
        exit;
    }

    // 3. Count status
    $stmtCounts = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM mailbatch_queue
    ");
    $counts = $stmtCounts->fetch(PDO::FETCH_ASSOC);

    $total = intval($counts['total'] ?? 0);
    $pending = intval($counts['pending'] ?? 0);
    $sent = intval($counts['sent'] ?? 0);
    $failed = intval($counts['failed'] ?? 0);
    
    $percent = $total > 0 ? round(($sent / $total) * 100) : 0;

    // 4. Get active campaign ID
    $campaignId = '';
    if ($total > 0) {
        $stmtCamp = $pdo->query("SELECT campaign_id FROM mailbatch_queue ORDER BY id DESC LIMIT 1");
        $camp = $stmtCamp->fetch();
        $campaignId = $camp['campaign_id'] ?? '';
    }

    // 5. Next scheduled email
    $nextSend = null;
    if ($pending > 0) {
        $stmtNext = $pdo->query("
            SELECT scheduled_at FROM mailbatch_queue 
            WHERE status = 'pending' 
            ORDER BY scheduled_at ASC LIMIT 1
        ");
        $next = $stmtNext->fetch();
        if ($next) {
            $nextSend = $next['scheduled_at'];
        }
    }

    // 6. Recent Logs (last 15 processed or scheduled)
    $logs = [];
    $stmtLogs = $pdo->query("
        SELECT id, email, name, subject, version, status, scheduled_at, sent_at, error_message 
        FROM mailbatch_queue 
        ORDER BY scheduled_at DESC, id DESC LIMIT 20
    ");
    if ($stmtLogs) {
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'active_campaign' => $total > 0,
        'campaign_id' => $campaignId,
        'stats' => [
            'total' => $total,
            'pending' => $pending,
            'sent' => $sent,
            'failed' => $failed,
            'percent' => $percent
        ],
        'next_send' => $nextSend,
        'logs' => $logs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error querying queue stats: ' . $e->getMessage()]);
}
?>
