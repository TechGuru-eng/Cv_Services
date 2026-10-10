<?php
require_once 'config/db.php';

echo "<h1>Applying Database Fixes...</h1>";

try {
    // 1. Create activities table
    $sql1 = "CREATE TABLE IF NOT EXISTS activities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        user_email VARCHAR(255),
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "<p style='color:green'>Success: 'activities' table created or already exists.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error creating 'activities': " . $e->getMessage() . "</p>";
}

try {
    // 2. Add position to prospects
    // Check if exists first to avoid error
    $check = $pdo->query("SHOW COLUMNS FROM prospects LIKE 'position'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE prospects ADD COLUMN position INT DEFAULT 0");
        echo "<p style='color:green'>Success: 'position' column added to 'prospects'.</p>";
    } else {
        echo "<p style='color:orange'>Skipped: 'position' column already exists in 'prospects'.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error adding 'position' to 'prospects': " . $e->getMessage() . "</p>";
}

echo "<h2>Done.</h2>";
?>
