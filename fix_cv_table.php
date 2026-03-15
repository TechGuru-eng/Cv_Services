<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting DB Fix...\n";

require_once 'admin/config/db.php';

echo "Connected successfully.\n";
// $dbname is available from the included file if it's in scope, but let's check PDO
$stmt = $pdo->query("SELECT DATABASE()");
$currentDb = $stmt->fetchColumn();
echo "Current Database: " . $currentDb . "\n";

try {
    $sql = "CREATE TABLE IF NOT EXISTS cv_designs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "SQL Executed. Checking if table exists...\n";

    $stmt = $pdo->query("SHOW TABLES LIKE 'cv_designs'");
    if ($stmt->rowCount() > 0) {
        echo "SUCCESS: Table 'cv_designs' exists!\n";
    } else {
        echo "FAILURE: Table 'cv_designs' was NOT found after creation attempt.\n";
    }

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
