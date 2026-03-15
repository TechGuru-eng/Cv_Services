<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'admin/config/db.php';

echo "Payment Statuses in Orders:\n";
$stmt = $pdo->query("SELECT id, client_name, payment_status, total_amount FROM orders ORDER BY id DESC LIMIT 10");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($orders as $o) {
    echo "ID: {$o['id']} | Name: {$o['client_name']} | Status: '{$o['payment_status']}' | Amount: {$o['total_amount']}\n";
}

echo "\nSponsorship Pool:\n";
$stmt = $pdo->query("SELECT * FROM sponsorship_pool ORDER BY id DESC LIMIT 10");
$pool = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($pool as $p) {
    echo "Donor: {$p['donor_name']} | OrderID: {$p['order_id']} | Total: {$p['credits_total']} | Remaining: {$p['credits_remaining']}\n";
}

echo "\nSponsorship Requests Schema:\n";
$stmt = $pdo->query("DESCRIBE sponsorship_requests");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $c) {
    echo "Field: {$c['Field']} | Type: {$c['Type']}\n";
}
?>
