<?php
require_once 'config/db.php';

echo "<h2>Affiliate Messages Debug</h2>";

// Check if table exists
try {
    $pdo->query("SELECT 1 FROM affiliate_messages LIMIT 1");
    echo "✅ Table `affiliate_messages` exists.<br>";
} catch (Exception $e) {
    echo "❌ Table `affiliate_messages` DOES NOT exist.<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Create it if missing
    $sql = "CREATE TABLE IF NOT EXISTS `affiliate_messages` (
      `id` int NOT NULL AUTO_INCREMENT,
      `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    try {
        $pdo->exec($sql);
        echo "✅ Table `affiliate_messages` created successfully.<br>";
    } catch (Exception $ex) {
         echo "❌ Failed to create table: " . $ex->getMessage() . "<br>";
    }
}

// Show existing messages
try {
    $stmt = $pdo->query("SELECT * FROM affiliate_messages ORDER BY created_at DESC");
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($msgs) {
        echo "<h3>Recent Messages:</h3><pre>";
        print_r($msgs);
        echo "</pre>";
    } else {
        echo "<h3>No messages found.</h3>";
    }
} catch (Exception $e) {
    echo "Error fetching messages: " . $e->getMessage();
}
?>
