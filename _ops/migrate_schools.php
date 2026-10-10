<?php
require_once 'admin/config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS school_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_name VARCHAR(255) NOT NULL,
        city VARCHAR(100) NOT NULL,
        students_count INT NOT NULL,
        contact_name VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        email VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "Table 'school_leads' created successfully.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
