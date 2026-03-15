<?php
require 'admin/config/db.php';
try {
    $stmt = $pdo->prepare("UPDATE promo_codes SET products = NULL WHERE code = 'MONTOUR'");
    $stmt->execute();
    echo "Promo MONTOUR updated to apply to ALL products.\n";
    
    // Also ensure it is the default
    $pdo->exec("UPDATE promo_codes SET is_default = 0"); // Reset all
    $pdo->exec("UPDATE promo_codes SET is_default = 1 WHERE code = 'MONTOUR'");
    echo "Promo MONTOUR set as DEFAULT.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
