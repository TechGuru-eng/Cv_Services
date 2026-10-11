<?php
// api/search_customer.php
header("Content-Type: application/json; charset=UTF-8");
session_start();
if (($_SESSION['admin_logged_in'] ?? false) !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

require_once '../admin/config/db.php';

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $parts = array_filter(explode(' ', trim($query)));
    
    $sql = "SELECT id, name, email, whatsapp FROM orders WHERE LOWER(payment_status) = 'verified'";
    $params = [];
    
    foreach ($parts as $p) {
        $sql .= " AND LOWER(name) LIKE ?";
        $params[] = '%' . strtolower($p) . '%';
    }
    
    $sql .= " LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => 'Erreur serveur.']);
}
?>
