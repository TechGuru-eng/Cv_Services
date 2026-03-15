<?php
require_once 'admin/config/db.php';

echo "<h3>Recent Verified/Paid Orders</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, payment_status, amount, created_at FROM orders ORDER BY id DESC LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($orders)) {
        echo "No orders found.<br>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Status</th><th>Amount</th><th>Date</th></tr>";
        foreach ($orders as $o) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($o['id']) . "</td>";
            echo "<td>" . htmlspecialchars($o['name']) . "</td>";
            echo "<td>'" . htmlspecialchars($o['payment_status']) . "'</td>"; // Quote status to see whitespace
            echo "<td>" . htmlspecialchars($o['amount']) . "</td>";
            echo "<td>" . htmlspecialchars($o['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error fetching orders: " . $e->getMessage() . "<br>";
}

echo "<h3>Sponsorship Pool Content</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM sponsorship_pool LIMIT 10");
    $pool = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($pool)) {
        echo "Sponsorship Pool is EMPTY.<br>";
    } else {
        echo "<pre>";
        print_r($pool);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "Error fetching pool: " . $e->getMessage() . "<br>";
}
?>
