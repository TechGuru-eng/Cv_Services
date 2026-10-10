<?php
// admin/setup_delivery_db.php
require_once 'config/db.php';

try {
    echo "Checking orders table...<br>";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'completed_cv_path'");
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Adding 'completed_cv_path' column...<br>";
        $pdo->exec("ALTER TABLE orders ADD COLUMN completed_cv_path VARCHAR(255) DEFAULT NULL AFTER payment_proof");
        echo "SUCCESS: Column added.<br>";
    } else {
        echo "INFO: Column 'completed_cv_path' already exists.<br>";
    }

    // Create directory if not exists
    $uploadDir = '../uploads/completed_cvs';
    if (!is_dir($uploadDir)) {
        echo "Creating uploads/completed_cvs directory...<br>";
        mkdir($uploadDir, 0777, true);
        echo "SUCCESS: Directory created.<br>";
    } else {
        echo "INFO: Directory uploads/completed_cvs already exists.<br>";
    }

} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
