<?php
require_once 'admin/config/db.php';

$orderIds = [23, 25];
echo "<h3>Order Inspection</h3>";

foreach ($orderIds as $id) {
    echo "<h4>Order ID: $id</h4>";
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "Order not found.<br>";
        continue;
    }
    
    echo "Status Raw: '" . $order['payment_status'] . "'<br>";
    echo "Status Hex: ";
    for ($i = 0; $i < strlen($order['payment_status']); $i++) {
        echo dechex(ord($order['payment_status'][$i])) . " ";
    }
    echo "<br>";
    
    // Check match against UPPER check
    $statusUpper = strtoupper($order['payment_status']);
    echo "UPPER: '$statusUpper'<br>";
    
    if (in_array($statusUpper, ['VERIFIED', 'PAID', 'CONFIRMED', 'COMPLETED'])) {
        echo "MATCHES current filter.<br>";
    } else {
        echo "DOES NOT MATCH current filter.<br>";
    }
}

echo "<h3>Pool Inspection</h3>";
$stmt = $pdo->query("SELECT * FROM sponsorship_pool WHERE order_id IN (23, 25)");
$pool = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pool);
echo "</pre>";
?>
