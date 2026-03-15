-- Prospects / Abandoned Carts Table
CREATE TABLE IF NOT EXISTS `prospects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `visit_id` INT DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `whatsapp` VARCHAR(45) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `package_name` VARCHAR(100) DEFAULT NULL,
    `total_amount` FLOAT DEFAULT 0,
    `status` ENUM('pending', 'converted', 'abandoned') DEFAULT 'pending',
    `followup_stage` INT DEFAULT 0,
    `last_email_sent` DATETIME DEFAULT NULL,
    `last_followup` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (`whatsapp`),
    INDEX (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
