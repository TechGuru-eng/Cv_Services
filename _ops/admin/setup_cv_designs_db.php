<?php
require_once 'config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS cv_designs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'cv_designs' created successfully (or already exists).";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
