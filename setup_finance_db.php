<?php
require_once 'admin/config/db.php';

try {
    // 1. Create Expenses Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        category VARCHAR(50) NOT NULL,
        expense_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'expenses' created successfully.<br>";

    // 2. Create Targets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS targets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('revenue', 'sales') NOT NULL,
        period ENUM('monthly', 'yearly') NOT NULL,
        target_amount DECIMAL(10, 2) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'targets' created successfully.<br>";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
