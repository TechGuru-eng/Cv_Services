<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

require 'db.php';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Also fetch the default coupon for the magic link feature
    $stmtCoupon = $pdo->query("SELECT code FROM coupons WHERE is_default = 1 AND is_active = 1 LIMIT 1");
    $defaultCoupon = $stmtCoupon->fetchColumn();
    if ($defaultCoupon) {
        $settings['default_coupon'] = $defaultCoupon;
    }

    echo json_encode($settings);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
