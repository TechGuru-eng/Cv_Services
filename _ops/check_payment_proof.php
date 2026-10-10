<?php
require_once 'admin/config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_proof'");
$col = $stmt->fetch();
if ($col) {
    echo "Column payment_proof EXISTS.\n";
} else {
    echo "Column payment_proof MISSING.\n";
}
?>
