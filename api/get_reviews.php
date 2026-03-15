<?php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

try {
    $stmt = $pdo->query("SELECT name, stars, comment FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10");
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $reviews]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
?>
