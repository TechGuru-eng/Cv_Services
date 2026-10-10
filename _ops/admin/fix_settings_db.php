<?php
require_once 'config/db.php';

try {
    // Create Settings Table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    )";
    $pdo->exec($sql);
    echo "✅ Table 'settings' created successfully.<br>";

    // Insert Default Values
    $sqlInsert = "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
        ('flash_status', 'CLOSED'),
        ('flash_end_time', '')";
    $pdo->exec($sqlInsert);
    echo "✅ Default settings inserted.<br>";

    echo "<hr>🎉 You can now access <a href='settings.php'>Settings Page</a>.";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
