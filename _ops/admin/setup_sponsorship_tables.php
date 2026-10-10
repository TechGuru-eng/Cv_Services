<?php
require_once 'config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sponsorship_pool` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `donor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Anonyme',
            `package_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
            `credits_total` int(11) NOT NULL DEFAULT '1',
            `credits_remaining` int(11) NOT NULL DEFAULT '1',
            `message` text COLLATE utf8mb4_unicode_ci,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'sponsorship_pool' created or already exists.<br>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sponsorship_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `whatsapp` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
            `motivation` text COLLATE utf8mb4_unicode_ci NOT NULL,
            `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
            `allocated_pool_id` int(11) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'sponsorship_requests' created or already exists.<br>";

    echo "✅ Database schema updated for Sponsorship Programme.";

} catch (PDOException $e) {
    echo "❌ Error updating database: " . $e->getMessage();
}
?>
