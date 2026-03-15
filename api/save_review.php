<?php
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
