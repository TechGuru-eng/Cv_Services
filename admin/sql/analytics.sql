-- Create stats_visits table for analytics
CREATE TABLE IF NOT EXISTS `stats_visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `country` VARCHAR(100) DEFAULT 'Unknown',
    `page_url` VARCHAR(255) NOT NULL,
    `referrer` VARCHAR(255) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `is_unique` TINYINT(1) DEFAULT 1,
    `utm_source` VARCHAR(100) DEFAULT NULL,
    `utm_medium` VARCHAR(100) DEFAULT NULL,
    `utm_campaign` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`created_at`),
    INDEX (`country`),
    INDEX (`page_url`),
    INDEX (`utm_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create stats_events table for granular action tracking
CREATE TABLE IF NOT EXISTS `stats_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `visit_id` INT NOT NULL,
    `event_name` VARCHAR(50) NOT NULL,
    `event_data` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`event_name`),
    INDEX (`visit_id`),
    FOREIGN KEY (`visit_id`) REFERENCES `stats_visits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
