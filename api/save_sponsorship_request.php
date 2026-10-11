<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
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
