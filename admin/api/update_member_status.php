<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$memberId = (int)($data['memberId'] ?? 0);
$status   = $data['status'] ?? '';

$allowed = ['active', 'pending_payment', 'suspended', 'expired'];
if (!$memberId || !in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

try {
    $extra = '';
    $params = [$status, $memberId];

    // If activating, set expiry to +30 days if currently null
    if ($status === 'active') {
        $check = $pdo->prepare("SELECT expiry_date FROM club_members WHERE id = ?");
        $check->execute([$memberId]);
        $row = $check->fetch();
        if (empty($row['expiry_date'])) {
            $extra = ', expiry_date = DATE_ADD(NOW(), INTERVAL 30 DAY)';
        }
    }

    $stmt = $pdo->prepare("UPDATE club_members SET status = ? $extra WHERE id = ?");
    $stmt->execute($params);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
