<?php
require_once 'admin/config/db.php';

echo "<h2>Orders (Last 5)</h2>";
$stmt = $pdo->query("SELECT id, client_name, payment_status, total_amount FROM orders ORDER BY id DESC LIMIT 5");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h2>Sponsorship Pool</h2>";
$stmt = $pdo->query("SELECT * FROM sponsorship_pool");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";

echo "<h2>Sponsorship Requests</h2>";
// Guessing the table name, if it fails I'll know.
try {
    $stmt = $pdo->query("SELECT * FROM sponsorship_requests LIMIT 5");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "Table sponsorship_requests not found or error: " . $e->getMessage();
}

?>
