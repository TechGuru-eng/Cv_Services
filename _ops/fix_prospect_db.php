<?php
require 'admin/config/db.php';
try {
    $pdo->exec("ALTER TABLE prospects ADD COLUMN has_recruiter_list TINYINT(1) DEFAULT 0 AFTER total_amount");
    echo "has_recruiter_list column added successfully to prospects table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
