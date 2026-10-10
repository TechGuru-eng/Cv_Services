<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE flash_products ADD COLUMN max_stock INT DEFAULT 10 AFTER min_price");
    $pdo->exec("ALTER TABLE flash_products ADD COLUMN sold_count INT DEFAULT 0 AFTER max_stock");
    echo "Columns max_stock and sold_count added successfully.";
} catch(PDOException $e) {
    echo "Error (Maybe cols exist): " . $e->getMessage();
}
?>
