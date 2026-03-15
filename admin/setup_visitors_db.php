<?php
require_once 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS active_visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        page VARCHAR(50) DEFAULT 'flash',
        UNIQUE KEY unique_visitor (ip_address, page)
    )";
    $pdo->exec($sql);
    echo "Active Visitors table created successfully.";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>
