ALTER TABLE `orders` ADD COLUMN `order_type` VARCHAR(20) DEFAULT 'standard' AFTER `order_ref`;
ALTER TABLE `orders` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `created_at`;
