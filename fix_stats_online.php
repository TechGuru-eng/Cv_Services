<?php
require_once 'admin/config/db.php';

echo "<h3>HireMe Sponsorship Stats Fix</h3>";

try {
    // 1. Update orders that have a pool entry but are marked as 'pending'
    $sql = "UPDATE orders o 
            JOIN sponsorship_pool p ON o.id = p.order_id 
            SET o.payment_status = 'verified' 
            WHERE o.payment_status = 'pending'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $count = $stmt->rowCount();
    
    if ($count > 0) {
        echo "<p style='color:green'>✅ Success! Fixed $count orders. Your stats should now appear correctly.</p>";
    } else {
        echo "<p style='color:blue'>ℹ️ No pending orders found in the pool. Your data is likely already correct.</p>";
    }
    
    // 2. Clear verify cache just in case (optional, conceptually)
    echo "<p>You can now delete this file from your server.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
