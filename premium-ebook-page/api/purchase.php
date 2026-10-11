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

if (!isset($data['fullName']) || !isset($data['email']) || !isset($data['whatsapp']) || !isset($data['city']) || !isset($data['profession'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Veuillez remplir tous les champs obligatoires']);
    exit(1);
}

$basePrice = 10000;
$upsellPrice = 3000;
$hasUpsell = isset($data['upsell_cv']) && $data['upsell_cv'] ? 1 : 0;
$finalPrice = $basePrice + ($hasUpsell ? $upsellPrice : 0);
$couponCode = null;

try {
    $pdo->beginTransaction();

    // Validate Coupon if provided
    if (isset($data['coupon']) && !empty(trim($data['coupon']))) {
        $code = strtoupper(trim($data['coupon']));
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon && $coupon['current_uses'] < $coupon['max_uses']) {
            if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
                // Invalid or exhausted coupon
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Ce code promo a expiré']);
                exit(1);
            }
            
            $discountedBase = max(0, $basePrice - $coupon['discount_amount']);
            $finalPrice = $discountedBase + ($hasUpsell ? $upsellPrice : 0);
            $couponCode = $code;
            
            // Increment coupon usage
            $updateStmt = $pdo->prepare("UPDATE coupons SET current_uses = current_uses + 1 WHERE id = ?");
            $updateStmt->execute([$coupon['id']]);
        } else {
            // Invalid or exhausted coupon
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Code promo invalide ou expiré']);
            exit(1);
        }
    }

    // Insert Purchase
    $stmt = $pdo->prepare("INSERT INTO purchases (full_name, email, whatsapp, city, profession, price_paid, coupon_used, upsell_cv) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['fullName'],
        $data['email'],
        $data['whatsapp'],
        $data['city'],
        $data['profession'],
        $finalPrice,
        $couponCode,
        $hasUpsell
    ]);

    $purchaseId = $pdo->lastInsertId();

    // Update Visitor as Converted (similar to subscribe.php)
    if (isset($data['sessionId'])) {
        $visitorStmt = $pdo->prepare("UPDATE visitors SET is_converted = 1, email = ?, whatsapp = ? WHERE session_id = ?");
        $visitorStmt->execute([$data['email'], $data['whatsapp'], $data['sessionId']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'finalPrice' => $finalPrice,
        'purchaseId' => $purchaseId
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur est survenue lors du traitement']);
}
?>
