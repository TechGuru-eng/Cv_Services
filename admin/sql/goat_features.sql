-- GOAT Features: Digital Upsells & Real Social Proof
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `has_recruiter_list` TINYINT(1) DEFAULT 0;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `prospects` ADD COLUMN IF NOT EXISTS `has_recruiter_list` TINYINT(1) DEFAULT 0;
ALTER TABLE `prospects` ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) DEFAULT NULL;

-- Optional: Create a table for these digital products if we want to add more later
CREATE TABLE IF NOT EXISTS `digital_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `price` FLOAT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the Recruiter List
INSERT INTO `digital_products` (name, filename, price) VALUES ('La Bible des Recruteurs (250+ Contacts)', 'recruteurs-cameroun-v1.pdf', 2500)
ON DUPLICATE KEY UPDATE price=2500;
