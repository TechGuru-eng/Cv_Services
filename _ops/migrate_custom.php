<?php
// migrate_custom.php
// Script to be uploaded and run once on the online server

header('Content-Type: text/plain');

$host = 'localhost';
$dbname = 'u158764452_hireme_db'; // Online DB Name
$username = 'u158764452_root';    // Modify if needed
$password = '';                   // Modify if needed (or leave empty if using include)

// Try to load local config if available to avoid hardcoding credentials
if (file_exists('admin/config/db.php')) {
    require_once 'admin/config/db.php';
    echo "Configuration loaded from admin/config/db.php\n";
} else {
    // Fallback connection
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection Failed: " . $e->getMessage());
    }
}

echo "Connected to database.\n";

try {
    // 1. Add 'source' column to prospects if missing
    $cols = $pdo->query("SHOW COLUMNS FROM prospects LIKE 'source'")->fetchAll();
    if (count($cols) == 0) {
        $pdo->exec("ALTER TABLE prospects ADD COLUMN source VARCHAR(50) DEFAULT 'Direct' AFTER status");
        echo "[OK] Column 'source' added to table 'prospects'.\n";
    } else {
        echo "[SKIP] Column 'source' already exists in 'prospects'.\n";
    }

    // 2. Add 'is_bot' column to stats_visits if missing
    $cols = $pdo->query("SHOW COLUMNS FROM stats_visits LIKE 'is_bot'")->fetchAll();
    if (count($cols) == 0) {
        $pdo->exec("ALTER TABLE stats_visits ADD COLUMN is_bot TINYINT(1) DEFAULT 0");
        $pdo->exec("CREATE INDEX idx_is_bot ON stats_visits(is_bot)");
        echo "[OK] Column 'is_bot' added to table 'stats_visits'.\n";
    } else {
        echo "[SKIP] Column 'is_bot' already exists in 'stats_visits'.\n";
    }

    // 3. Ensure 'notes' column in prospects is TEXT (to avoid truncation)
    $pdo->exec("ALTER TABLE prospects MODIFY COLUMN notes TEXT");
    echo "[OK] Column 'notes' in 'prospects' updated to TEXT.\n";

    // 4. Update 'marketing.php' access in admin (Simulated by checking file existence)
    if (file_exists('admin/marketing.php')) {
        echo "[CHECK] admin/marketing.php exists. Please insure it is uploaded.\n";
    }

    echo "\n--- MIGRATION COMPLETE ---\n";
    echo "You can now access the Marketing section in your admin panel.\n";
    echo "Delete this file after use for security.";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage();
}
?>
