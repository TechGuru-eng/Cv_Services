<?php
require_once 'config/db.php';

echo "<h2>Sponsorship Debug</h2>";

// Check if table exists
try {
    $pdo->query("SELECT 1 FROM sponsorship_pool LIMIT 1");
    echo "✅ Table `sponsorship_pool` exists.<br>";
} catch (Exception $e) {
    echo "❌ Table `sponsorship_pool` DOES NOT exist.<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Create it if missing
    $sql = "CREATE TABLE IF NOT EXISTS `sponsorship_pool` (
      `id` int NOT NULL AUTO_INCREMENT,
      `order_id` int NOT NULL,
      `donor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
      `package_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
      `credits_total` int NOT NULL DEFAULT '1',
      `credits_remaining` int NOT NULL DEFAULT '1',
      `message` text COLLATE utf8mb4_unicode_ci,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $pdo->exec($sql);
        echo "✅ Table `sponsorship_pool` created successfully.<br>";
    } catch (Exception $ex) {
         echo "❌ Failed to create table: " . $ex->getMessage() . "<br>";
    }
}

// Show recent attempts
try {
    $stmt = $pdo->query("SELECT * FROM sponsorship_pool ORDER BY created_at DESC LIMIT 5");
    $pools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($pools) {
        echo "<h3>Recent Pools:</h3><pre>";
        print_r($pools);
        echo "</pre>";
    } else {
        echo "<h3>No pools found.</h3>";
    }
} catch (Exception $e) {
    echo "Error fetching pools: " . $e->getMessage();
}

// Check recent orders to see if they were flagged
try {
    $stmt = $pdo->query("SELECT id, name, amount, package_name, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
    echo "<h3>Recent Orders:</h3><pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (Exception $e) {
    echo "Error fetching orders: " . $e->getMessage();
}
?>
