<?php
// admin/api/manage_litige.php
session_start();
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
require_once '../config/db.php';
require_once '../includes/security_helper.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$orderId = intval($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID']);
    exit;
}

// Fetch order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

try {
    switch ($action) {
        case 'set_non_payer':
            $status = intval($_POST['status'] ?? 0);
            $deletionDate = $status ? date('Y-m-d H:i:s', strtotime('+7 days')) : null;
            
            $stmt = $pdo->prepare("UPDATE orders SET is_non_payer = ?, deletion_scheduled_at = ? WHERE id = ?");
            $stmt->execute([$status, $deletionDate, $orderId]);
            
            echo json_encode(['success' => true, 'new_status' => $status, 'deletion_date' => $deletionDate]);
            break;

        case 'increment_reminder':
            $stmt = $pdo->prepare("UPDATE orders SET reminder_count = reminder_count + 1 WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // Get updated count
            $stmtCount = $pdo->prepare("SELECT reminder_count FROM orders WHERE id = ?");
            $stmtCount->execute([$orderId]);
            $count = $stmtCount->fetchColumn();
            
            echo json_encode(['success' => true, 'new_count' => $count]);
            break;

        case 'ban_client':
            $reason = $_POST['reason'] ?? 'Non-paiement après travail effectué.';
            banClient($order['whatsapp'], $order['email'], $reason, $pdo);
            
            // Mark order as failed/cancelled
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed', is_non_payer = 1 WHERE id = ?");
            $stmt->execute([$orderId]);
            
            echo json_encode(['success' => true, 'message' => 'Client banni avec succès.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
