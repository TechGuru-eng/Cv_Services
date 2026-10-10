<?php
require_once 'config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `reviews` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `stars` int(1) NOT NULL DEFAULT '5',
            `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
            `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✅ Table 'reviews' created or already exists.<br>";
} catch (PDOException $e) {
    echo "❌ Error creating table: " . $e->getMessage();
}
?>
