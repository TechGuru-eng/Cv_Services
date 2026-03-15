<?php
require_once 'config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS abandoned_carts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100),
        whatsapp VARCHAR(50),
        source_page VARCHAR(50) DEFAULT 'Unknown',
        cart_details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('new', 'contacted', 'recovered') DEFAULT 'new'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Abandoned Carts table created.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
