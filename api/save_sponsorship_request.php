<?php
// api/save_sponsorship_request.php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST Request required']);
    exit;
}

$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');
$motivation = htmlspecialchars($_POST['motivation'] ?? '');

if (empty($name) || empty($whatsapp) || empty($motivation)) {
    echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO sponsorship_requests (name, email, whatsapp, motivation, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$name, $email, $whatsapp, $motivation]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
