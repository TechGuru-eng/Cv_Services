<?php
// api/check_promo.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/cors.php';

// Database Connection (Path adapted for API folder)
require_once '../admin/config/db.php';
require_once '../includes/upload_helper.php';
if (isset($_GET['code']) && !rate_limit_ok('check-promo', 30, 600)) {
    http_response_code(429);
    echo json_encode(['valid' => false, 'message' => 'Trop de tentatives.']);
    exit;
}

// 1. Check for Active Promo (for Banner/Auto-apply)
if (isset($_GET['action']) && $_GET['action'] == 'get_active') {
    $today = date('Y-m-d');
    require_once __DIR__ . '/../includes/wheel-promos.php';
    $wheelExclusion = wheel_promo_exclusion($pdo);
    
    // First, try to find the designated 'default' promo
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE status = 'active' AND is_default = 1 AND (start_date <= ? AND end_date >= ?) AND (usage_limit IS NULL OR usage_count < usage_limit)" . $wheelExclusion . " LIMIT 1");
    $stmt->execute([$today, $today]);
    $promo = $stmt->fetch();

    // If no explicit default, just find the best active one (highest discount)
    if (!$promo) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE status = 'active' AND (start_date <= ? AND end_date >= ?) AND (usage_limit IS NULL OR usage_count < usage_limit)" . $wheelExclusion . " ORDER BY discount_value DESC LIMIT 1");
        $stmt->execute([$today, $today]);
        $promo = $stmt->fetch();
    }

    if ($promo) {
        echo json_encode([
            'active' => true, 
            'code' => $promo['code'], 
            'discount_type' => $promo['discount_type'],
            'discount_value' => (float)$promo['discount_value'],
            'products' => $promo['products'] ? explode(',', $promo['products']) : [],
            'message' => 'Offre spéciale appliquée !'
        ]);
    } else {
        echo json_encode(['active' => false]);
    }
    exit;
}

// 2. Validate Specific Code (for Checkout/Manual entry)
if (isset($_GET['code'])) {
    $code = strtoupper(trim($_GET['code']));
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND (start_date <= ? AND end_date >= ?)");
    $stmt->execute([$code, $today, $today]);
    $promo = $stmt->fetch();

    if ($promo) {
        // Check usage limit
        if ($promo['usage_limit'] !== null && $promo['usage_count'] >= $promo['usage_limit']) {
            echo json_encode(['valid' => false, 'message' => "Ce code a atteint sa limite d'utilisation."]);
            exit;
        }

        echo json_encode([
            'valid' => true, 
            'code' => $promo['code'],
            'discount_type' => $promo['discount_type'],
            'discount_value' => (float)$promo['discount_value'],
            'products' => $promo['products'] ? explode(',', $promo['products']) : [],
            'message' => "Code promo appliqué avec succès !"
        ]);
    } else {
        echo json_encode(['valid' => false, 'message' => "Code invalide ou expiré."]);
    }
    exit;
}
?>
