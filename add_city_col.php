<?php
require 'admin/config/db.php';
try {
    $pdo->exec("ALTER TABLE prospects ADD COLUMN city VARCHAR(100) AFTER email");
    echo "City column added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
