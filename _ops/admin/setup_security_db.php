<?php
require_once 'config/db.php';

try {
    // $pdo is already available from config/db.php


    // Create security_logs table
    $sql_logs = "CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(255),
        request_uri VARCHAR(255),
        reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (ip_address),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_logs);
    echo "Table 'security_logs' created or already exists.<br>";

    // Create banned_ips table
    $sql_bans = "CREATE TABLE IF NOT EXISTS banned_ips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        reason TEXT,
        banned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        banned_by VARCHAR(100) DEFAULT 'System',
        INDEX idx_ip_banned (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql_bans);
    echo "Table 'banned_ips' created or already exists.<br>";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
