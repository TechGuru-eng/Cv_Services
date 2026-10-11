<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../admin/config/db.php';
$pdo->exec("CREATE TABLE IF NOT EXISTS linkedin_checkout_leads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 token_hash CHAR(64) NOT NULL UNIQUE,
 name VARCHAR(200) NOT NULL DEFAULT '', first_name VARCHAR(200) NOT NULL DEFAULT '',
 email VARCHAR(254) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT '', country VARCHAR(100) NOT NULL DEFAULT '',
 package_id INT NOT NULL DEFAULT 0, last_step TINYINT NOT NULL DEFAULT 1,
 status VARCHAR(30) NOT NULL DEFAULT 'open', order_reference VARCHAR(50) DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(status, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "LinkedIn checkout lead table ready.\n";
