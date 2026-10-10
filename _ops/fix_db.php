<?php
// fix_db.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin/config/db.php';

try {
    echo "Checking columns in 'orders' table...<br>";
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('due_date', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN due_date DATE AFTER payment_proof");
        echo "✅ Added 'due_date' column.<br>";
    } else {
        echo "ℹ️ 'due_date' already exists.<br>";
    }

    if (!in_array('order_type', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_type VARCHAR(20) DEFAULT 'standard' AFTER due_date");
        echo "✅ Added 'order_type' column.<br>";
    } else {
        echo "ℹ️ 'order_type' already exists.<br>";
    }

    echo "Done!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
