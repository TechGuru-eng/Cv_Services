<?php
header('Content-Type: application/json; charset=UTF-8');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
require_once __DIR__ . '/../../admin/config/db.php';
require_once __DIR__ . '/../../includes/order-notifications.php';
$ref=(string)($_POST['reference'] ?? '');
$token=(string)($_POST['token'] ?? '');
if (!preg_match('/^HM-LI-\d{6}$/',$ref) || !preg_match('/^[a-f0-9]{32}$/',$token)) { http_response_code(400); exit; }
try {
    $pdo->beginTransaction();
    $stmt=$pdo->prepare("SELECT o.* FROM linkedin_orders o WHERE reference=? AND access_token=? AND EXISTS (SELECT 1 FROM linkedin_payments p WHERE p.order_id=o.id AND p.payment_method='CHARIOW') FOR UPDATE");
    $stmt->execute([$ref,$token]); $order=$stmt->fetch();
    if (!$order) { $pdo->rollBack(); http_response_code(404); exit; }
    queue_chariow_return_alert($pdo,$order);
    $pdo->commit();
    send_order_notifications($pdo);
    echo json_encode(['success'=>true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(503); echo json_encode(['success'=>false]);
}
