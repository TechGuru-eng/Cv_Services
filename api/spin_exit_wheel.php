<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../includes/wheel-promos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !is_string($input['token'] ?? null) || empty($_SESSION['exit_wheel_token']) || !hash_equals($_SESSION['exit_wheel_token'], $input['token']) || !is_string($input['browser'] ?? null) || !preg_match('/^[a-f0-9]{32}$/', $input['browser'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Rechargez la page pour jouer.']);
    exit;
}
if (!empty($_SESSION['exit_wheel_order_placed'])) {
    echo json_encode(['success' => false, 'message' => 'Votre commande est déjà enregistrée.']);
    exit;
}
try {
    $pdo->beginTransaction();
    $popup = $pdo->query("SELECT * FROM exit_popups WHERE is_active = 1 AND popup_type = 'spin_wheel' ORDER BY id DESC LIMIT 1 FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    $rewards = $popup ? wheel_rewards(json_decode($popup['slices'] ?? '[]', true) ?: [], wheel_promos($pdo)) : [];
    if (count($rewards) !== 4) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'La roue est indisponible pour le moment.']);
        exit;
    }
    array_unshift($rewards, ['text' => 'Pas de réduction', 'value' => 0, 'code' => null, 'color' => '#64748b']);
    $winner = $rewards[random_int(0, 4)];
    $stmt = $pdo->prepare('INSERT INTO exit_wheel_plays (browser_hash, ip_hash, reward_percent, promo_code) VALUES (?, ?, ?, ?)');
    $stmt->execute([hash('sha256', $input['browser']), hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''), $winner['value'], $winner['code']]);
    $pdo->commit();
    echo json_encode(['success' => true, 'reward' => $winner]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'played' => true, 'message' => 'Vous avez déjà joué. Une seule participation par navigateur et adresse IP.']);
    } else {
        error_log('Exit wheel: ' . $e->getMessage());
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'La roue est indisponible pour le moment.']);
    }
}
