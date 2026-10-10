<?php
require_once 'admin/config/db.php';

try {
    echo "Checking exit_popups table structure...\n";

    // Add popup_type column
    try {
        $pdo->query("SELECT popup_type FROM exit_popups LIMIT 1");
        echo "Column 'popup_type' already exists.\n";
    } catch (PDOException $e) {
        echo "Adding 'popup_type' column...\n";
        $pdo->exec("ALTER TABLE exit_popups ADD COLUMN popup_type VARCHAR(50) DEFAULT 'spin_wheel' AFTER title");
    }

    // Add slices column
    try {
        $pdo->query("SELECT slices FROM exit_popups LIMIT 1");
        echo "Column 'slices' already exists.\n";
    } catch (PDOException $e) {
        echo "Adding 'slices' column...\n";
        $pdo->exec("ALTER TABLE exit_popups ADD COLUMN slices JSON DEFAULT NULL AFTER message");
    }

    // Verify
    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
