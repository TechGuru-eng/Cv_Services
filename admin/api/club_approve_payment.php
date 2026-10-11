<?php
require_once '../config/db.php';
require_once __DIR__ . '/../../includes/club-payment.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'POST requis.']));
}

$data = json_decode(file_get_contents('php://input'), true);
$payId = $data['payId'] ?? null;
$memberId = $data['memberId'] ?? null;
$action = $data['action'] ?? '';

if (!$payId) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

try {
    if ($action === 'approve' && $memberId) {
        $pdo->beginTransaction();
        
        approve_club_payment($pdo, (int)$payId, (int)$memberId);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE club_payments SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$payId]);
        if ($stmt->rowCount() !== 1) throw new InvalidArgumentException('Paiement introuvable ou déjà traité.');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la validation du paiement.']);
}
