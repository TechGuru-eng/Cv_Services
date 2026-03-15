<?php
// setup_sponsorship_db.php
require_once 'admin/config/db.php';

try {
    echo "Starting Sponsorship Database Migration...<br>";

    // 1. Create sponsorship_pool table
    $pdo->exec("CREATE TABLE IF NOT EXISTS sponsorship_pool (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        donor_name VARCHAR(255) DEFAULT 'Anonymous',
        package_name VARCHAR(100) NOT NULL,
        credits_total INT NOT NULL,
        credits_remaining INT NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    echo "Created/Verified 'sponsorship_pool' table.<br>";

    // 2. Create sponsorship_requests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS sponsorship_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        whatsapp VARCHAR(50) NOT NULL,
        motivation TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        allocated_sponsorship_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (allocated_sponsorship_id) REFERENCES sponsorship_pool(id) ON DELETE SET NULL
    )");
    echo "Created/Verified 'sponsorship_requests' table.<br>";

    echo "Sponsorship Database setup completed successfully.";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
