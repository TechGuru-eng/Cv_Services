<?php
require_once 'config/db.php';

try {
    $sql = "ALTER TABLE `orders` ADD COLUMN `order_type` VARCHAR(20) DEFAULT 'standard' AFTER `order_ref`";
    $pdo->exec($sql);
    echo "Column 'order_type' added successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column") !== false) {
        echo "Column 'order_type' already exists.<br>";
    } else {
        echo "Error adding 'order_type': " . $e->getMessage() . "<br>";
    }
}

try {
    $sql = "ALTER TABLE `orders` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `created_at`";
    $pdo->exec($sql);
    echo "Column 'due_date' added successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column") !== false) {
        echo "Column 'due_date' already exists.<br>";
    } else {
        echo "Error adding 'due_date': " . $e->getMessage() . "<br>";
    }
}

echo "Database update completed. You can now access the reservation page.";
?>
