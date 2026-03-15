<?php
// add_reservation_columns.php
require_once 'admin/config/db.php';

try {
    echo "Updating orders table...<br>";

    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('due_date', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN due_date DATE AFTER payment_proof");
        echo "Added 'due_date' column.<br>";
    }

    if (!in_array('order_type', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_type VARCHAR(20) DEFAULT 'standard' AFTER due_date");
        echo "Added 'order_type' column.<br>";
    }

    echo "Database update completed.";

} catch (PDOException $e) {
    die("Update failed: " . $e->getMessage());
}
?>
