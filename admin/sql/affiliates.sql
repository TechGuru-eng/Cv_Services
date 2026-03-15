-- Affiliate Ambassador System Tables
CREATE TABLE IF NOT EXISTS `affiliates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(45) NOT NULL,
    `referral_code` VARCHAR(20) NOT NULL UNIQUE,
    `balance` FLOAT DEFAULT 0,
    `total_earned` FLOAT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `affiliate_referrals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `affiliate_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `visitor_ip` VARCHAR(45) DEFAULT NULL,
    `commission_amount` FLOAT DEFAULT 500,
    `status` ENUM('pending', 'verified', 'paid') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add affiliate_code to orders table if not exists
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `affiliate_code` VARCHAR(20) DEFAULT NULL;
