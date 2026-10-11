<?php
// api/linkedin/track_analytics.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$eventType = trim($_POST['event_type'] ?? '');
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;

$allowedEvents = ['impression', 'click', 'add', 'remove', 'purchase', 'bundle_selected'];

if ($productId <= 0 || !in_array($eventType, $allowedEvents)) {
    echo json_encode(['success' => false, 'message' => 'Invalid analytics payload']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO linkedin_upsell_analytics (product_id, event_type, order_id) VALUES (?, ?, ?)");
    $stmt->execute([$productId, $eventType, $orderId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
