<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
header('Content-Type: application/json');
require_once '../admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

$name = htmlspecialchars($_POST['name'] ?? '');
$stars = intval($_POST['stars'] ?? 5);
$comment = htmlspecialchars($_POST['comment'] ?? '');

if (empty($name) || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis.']);
    exit;
}

if ($stars < 1 || $stars > 5) $stars = 5;

try {
    $stmt = $pdo->prepare("INSERT INTO reviews (name, stars, comment, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$name, $stars, $comment]);
    echo json_encode(['success' => true, 'message' => 'Merci ! Votre avis est en cours de modération.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement.']);
}
?>
