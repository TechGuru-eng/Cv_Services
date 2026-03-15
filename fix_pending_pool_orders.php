<?php
require_once 'admin/config/db.php';

try {
    // Update any order to 'verified' if it has a sponsorship_pool entry but is currently pending
    $sql = "UPDATE orders o 
            JOIN sponsorship_pool p ON o.id = p.order_id 
            SET o.payment_status = 'verified' 
            WHERE o.payment_status = 'pending'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "Fixed " . $stmt->rowCount() . " orders (moved from pending to verified because they have pool entries).<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
