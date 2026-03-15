<?php
require_once 'config/db.php';

try {
    // 1. Create Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Create Flash Products Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS flash_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        reference_price DECIMAL(10,2) NOT NULL,
        min_price DECIMAL(10,2) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Seed Initial Settings
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['flash_status', 'CLOSED']);

    // 4. Seed Initial Products (from User Request)
    $products = [
        ['Pack Etudiant', 4000, 800],
        ['Pack Pro', 12000, 2500],
        ['Pack Senior', 30000, 12000],
        ['Pack Expert', 90000, 40000],
        ['Pack 300 Lettres', 1300, 500]
    ];

    $stmtProd = $pdo->prepare("INSERT INTO flash_products (name, reference_price, min_price) VALUES (?, ?, ?)");
    foreach ($products as $prod) {
        // Check if exists to avoid dupes on re-run
        $check = $pdo->prepare("SELECT COUNT(*) FROM flash_products WHERE name = ?");
        $check->execute([$prod[0]]);
        if ($check->fetchColumn() == 0) {
            $stmtProd->execute($prod);
            echo "Inserted: {$prod[0]}<br>";
        }
    }

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
