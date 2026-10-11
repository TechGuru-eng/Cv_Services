<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Veuillez entrer un code promo']);
    exit(1);
}

$code = strtoupper(trim($data['code']));

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            echo json_encode(['error' => 'Ce code promo a expiré']);
        } elseif ($coupon['current_uses'] < $coupon['max_uses']) {
            echo json_encode([
                'success' => true,
                'discount' => $coupon['discount_amount'],
                'message' => 'Code promo appliqué avec succès !'
            ]);
        } else {
            echo json_encode(['error' => 'Ce code promo a atteint sa limite d\'utilisation']);
        }
    } else {
        echo json_encode(['error' => 'Code promo invalide']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>
