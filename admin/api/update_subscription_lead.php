<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? null;

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'ID et statut requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE subscription_leads SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['success' => true, 'message' => 'Mise à jour réussie']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
