<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

$lead_id = intval($_GET['lead_id'] ?? 0);

if (!$lead_id) {
    echo json_encode(['success' => false, 'message' => 'ID lead requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM school_lead_activities WHERE lead_id = ? ORDER BY created_at DESC");
    $stmt->execute([$lead_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'activities' => $activities]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
