<?php
// admin/sql/update_schema.php
require_once __DIR__ . '/../config/db.php';

function columnExists($pdo, $table, $column) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $stmt->rowCount() > 0;
}

try {
    echo "Starting robust migration...<br>\n";

    // --- Create Tables if not exist ---
    
    // Withdrawals Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `withdrawals` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `affiliate_id` INT NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `method` VARCHAR(50) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `processed_at` TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (`affiliate_id`) REFERENCES `affiliates`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Checked/Created withdrawals table<br>\n";

    // --- Orders Table ---
    if (columnExists($pdo, 'orders', 'client_name')) {
        $pdo->exec("ALTER TABLE `orders` CHANGE COLUMN `client_name` `name` VARCHAR(255)");
        echo "Renamed client_name to name<br>\n";
    }
    if (columnExists($pdo, 'orders', 'transaction_id')) {
        $pdo->exec("ALTER TABLE `orders` CHANGE COLUMN `transaction_id` `transaction_number` VARCHAR(100)");
        echo "Renamed transaction_id to transaction_number<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'original_amount')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `original_amount` DECIMAL(10,2) DEFAULT 0");
        echo "Added original_amount<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'discount_amount')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0");
        echo "Added discount_amount<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'promo_code_used')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `promo_code_used` VARCHAR(50) DEFAULT NULL");
        echo "Added promo_code_used<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'affiliate_code')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `affiliate_code` VARCHAR(20) DEFAULT NULL");
        echo "Added affiliate_code<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'payment_proof')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `payment_proof` VARCHAR(255) DEFAULT NULL AFTER `transaction_number`");
        echo "Added payment_proof<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'city')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `city` VARCHAR(255) DEFAULT NULL AFTER `country`");
        echo "Added city<br>\n";
    }
    if (!columnExists($pdo, 'orders', 'has_recruiter_list')) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `has_recruiter_list` TINYINT(1) DEFAULT 0");
        echo "Added has_recruiter_list<br>\n";
    }

    // --- Promo Codes Table ---
    if (!columnExists($pdo, 'promo_codes', 'owner_name')) {
        $pdo->exec("ALTER TABLE `promo_codes` ADD COLUMN `owner_name` VARCHAR(255) DEFAULT 'Admin'");
        echo "Added owner_name<br>\n";
    }
    if (!columnExists($pdo, 'promo_codes', 'discount_type')) {
        $pdo->exec("ALTER TABLE `promo_codes` ADD COLUMN `discount_type` ENUM('percent', 'fixed') DEFAULT 'percent'");
        echo "Added discount_type<br>\n";
    }
    if (columnExists($pdo, 'promo_codes', 'discount_percent')) {
        if (columnExists($pdo, 'promo_codes', 'discount_value')) {
            $pdo->exec("ALTER TABLE `promo_codes` DROP COLUMN `discount_percent`");
            echo "Dropped redundant discount_percent<br>\n";
        } else {
            $pdo->exec("ALTER TABLE `promo_codes` CHANGE COLUMN `discount_percent` `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0");
            echo "Renamed discount_percent to discount_value<br>\n";
        }
    }
    if (!columnExists($pdo, 'promo_codes', 'products')) {
        $pdo->exec("ALTER TABLE `promo_codes` ADD COLUMN `products` TEXT DEFAULT NULL");
        echo "Added products<br>\n";
    }
    if (!columnExists($pdo, 'promo_codes', 'usage_limit')) {
        $pdo->exec("ALTER TABLE `promo_codes` ADD COLUMN `usage_limit` INT DEFAULT NULL");
        echo "Added usage_limit<br>\n";
    }
    if (!columnExists($pdo, 'promo_codes', 'is_default')) {
        $pdo->exec("ALTER TABLE `promo_codes` ADD COLUMN `is_default` TINYINT(1) DEFAULT 0");
        echo "Added is_default<br>\n";
    }

    // Initialize original_amount
    $pdo->exec("UPDATE orders SET original_amount = amount WHERE original_amount = 0 OR original_amount IS NULL");
    echo "Initialized 'original_amount' for existing orders.<br>\n";

    echo "Migration finished successfully!";
} catch (Exception $e) {
    die("Fatal Error: " . $e->getMessage());
}
?>
