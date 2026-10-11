<?php
// api/log_refund_search.php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['identifier']) || empty(trim($data['identifier']))) {
    echo json_encode(['success' => false]);
    exit;
}

$identifier = trim($data['identifier']);

try {
    // Only insert if not already there or update if we want to refresh timestamp
    // For simplicity, we just insert ignore or insert new record if previous was completed
    
    // Check if there is an active abandoned record for this identifier
    $stmt = $pdo->prepare("SELECT id FROM refund_abandoned WHERE identifier = ? AND status = 'abandoned' LIMIT 1");
    $stmt->execute([$identifier]);
    $existing = $stmt->fetch();
    
    if (!$existing) {
        $insert = $pdo->prepare("INSERT INTO refund_abandoned (identifier, status) VALUES (?, 'abandoned')");
        $insert->execute([$identifier]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
?>
