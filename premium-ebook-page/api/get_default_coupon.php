<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

try {
    $stmt = $pdo->prepare("
        SELECT code, discount_amount, expires_at 
        FROM coupons 
        WHERE is_default = 1 
          AND is_active = 1 
          AND current_uses < max_uses 
          AND (expires_at IS NULL OR expires_at > datetime('now', 'localtime'))
        LIMIT 1
    ");
    $stmt->execute();
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        echo json_encode([
            'success' => true,
            'code' => $coupon['code'],
            'discount' => $coupon['discount_amount']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
