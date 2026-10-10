<?php
require_once 'admin/config/db.php';

echo "<h3>HireMe Sponsorship Tables Setup</h3>";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create sponsorship_pool
    $sqlPool = "CREATE TABLE IF NOT EXISTS `sponsorship_pool` (
      `id` int NOT NULL AUTO_INCREMENT,
      `order_id` int NOT NULL,
      `donor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Anonymous',
      `package_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
      `credits_total` int NOT NULL,
      `credits_remaining` int NOT NULL,
      `message` text COLLATE utf8mb4_unicode_ci,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `order_id` (`order_id`),
      CONSTRAINT `sponsorship_pool_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sqlPool);
    echo "<p style='color:green'>✅ Checked/Created 'sponsorship_pool' table.</p>";

    // 2. Create sponsorship_requests
    $sqlRequests = "CREATE TABLE IF NOT EXISTS `sponsorship_requests` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `whatsapp` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
      `motivation` text COLLATE utf8mb4_unicode_ci,
      `status` enum('pending','approved','rejected','granted','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
      `allocated_pool_id` int DEFAULT NULL,
      `allocated_sponsorship_id` int DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `allocated_sponsorship_id` (`allocated_sponsorship_id`),
      KEY `allocated_pool_id` (`allocated_pool_id`),
      CONSTRAINT `sponsorship_requests_ibfk_1` FOREIGN KEY (`allocated_sponsorship_id`) REFERENCES `sponsorship_pool` (`id`) ON DELETE SET NULL,
      CONSTRAINT `sponsorship_requests_ibfk_2` FOREIGN KEY (`allocated_pool_id`) REFERENCES `sponsorship_pool` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sqlRequests);
    echo "<p style='color:green'>✅ Checked/Created 'sponsorship_requests' table.</p>";
    
    echo "<p><strong>Tables are ready!</strong> Now delete this file and run `fix_stats_online.php`.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
