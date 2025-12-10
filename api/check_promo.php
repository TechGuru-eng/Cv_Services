<?php
// api/check_promo.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database Connection (Path adapted for API folder)
require_once '../admin/config/db.php';

// 1. Check for Active Promo (for Banner)
if (isset($_GET['action']) && $_GET['action'] == 'get_active') {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE status = 'active' AND start_date <= ? AND end_date >= ? ORDER BY discount_percent DESC LIMIT 1");
    $stmt->execute([$today, $today]);
    $promo = $stmt->fetch();

    if ($promo) {
        echo json_encode(['active' => true, 'code' => $promo['code'], 'percent' => $promo['discount_percent']]);
    } else {
        echo json_encode(['active' => false]);
    }
    exit;
}

// 2. Validate Specific Code (for Checkout)
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND start_date <= ? AND end_date >= ?");
    $stmt->execute([$code, $today, $today]);
    $promo = $stmt->fetch();

    if ($promo) {
        echo json_encode(['valid' => true, 'percent' => $promo['discount_percent']]);
    } else {
        echo json_encode(['valid' => false, 'message' => "Code invalide ou expiré"]);
    }
    exit;
}
?>