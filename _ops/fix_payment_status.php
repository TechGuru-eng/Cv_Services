<?php
require_once 'admin/config/db.php';

try {
    // 1. Update 'Paid', 'paid', 'Confirmed', 'confirmed' to 'verified'
    $sql = "UPDATE orders SET payment_status = 'verified' WHERE payment_status IN ('Paid', 'paid', 'Confirmed', 'confirmed', 'Verified', 'COMPLETED', 'completed')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    echo "Updated " . $stmt->rowCount() . " orders to 'verified'.<br>";
    
    // 2. Check resulting statuses
    $stmt = $pdo->query("SELECT payment_status, COUNT(*) as count FROM orders GROUP BY payment_status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Current Order Statuses:</h3>";
    echo "<pre>";
    print_r($statuses);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
