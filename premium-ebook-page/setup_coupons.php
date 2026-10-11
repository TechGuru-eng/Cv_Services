<?php
require 'api/db.php';

try {
    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_amount, max_uses, current_uses, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['PROMO50', 5000, 20, 0, 1]);
    echo "Coupon PROMO50 créé avec succès (Réduction de 5000 XAF, limité à 20 utilisations).\n";
} catch (Exception $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation (UNIQUE)
        echo "Le coupon PROMO50 existe déjà.\n";
    } else {
        echo "Erreur : " . $e->getMessage() . "\n";
    }
}
?>
