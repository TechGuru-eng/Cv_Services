<?php
require_once 'config/db.php';
echo "<h1>🔧 Master Database Repair Tool</h1>";

try {
    // 1. Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Table 'settings' OK.<br>";

    // 2. Flash Products Table (With Stock Columns)
    $pdo->exec("CREATE TABLE IF NOT EXISTS flash_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        reference_price DECIMAL(10,2) NOT NULL,
        min_price DECIMAL(10,2) NOT NULL,
        max_stock INT DEFAULT 10,
        sold_count INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Table 'flash_products' OK.<br>";

    // 2b. Check if 'max_stock' exists (in case table existed but was old)
    $cols = $pdo->query("DESCRIBE flash_products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('max_stock', $cols)) {
        $pdo->exec("ALTER TABLE flash_products ADD COLUMN max_stock INT DEFAULT 10");
        echo "➕ Column 'max_stock' added.<br>";
    }
    if (!in_array('sold_count', $cols)) {
        $pdo->exec("ALTER TABLE flash_products ADD COLUMN sold_count INT DEFAULT 0");
        echo "➕ Column 'sold_count' added.<br>";
    }

    // 2c. Seed Data if empty
    $count = $pdo->query("SELECT COUNT(*) FROM flash_products")->fetchColumn();
    if ($count == 0) {
        $products = [
            ['Pack Etudiant', 4000, 800, 10, 0],
            ['Pack Pro', 12000, 2500, 10, 0],
            ['Pack Senior', 30000, 12000, 10, 0],
            ['Pack Expert', 90000, 40000, 5, 0],
            ['Pack 300 Lettres', 1300, 500, 50, 0]
        ];
        $stmt = $pdo->prepare("INSERT INTO flash_products (name, reference_price, min_price, max_stock, sold_count) VALUES (?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $stmt->execute($p);
        }
        echo "🌱 Default Products Inserted.<br>";
    }

    // 3. Active Visitors Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS active_visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        page VARCHAR(50) DEFAULT 'flash',
        UNIQUE KEY unique_visitor (ip_address, page)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Table 'active_visitors' OK.<br>";

    // 4. Abandoned Carts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS abandoned_carts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_name VARCHAR(100),
        lead_whatsapp VARCHAR(20),
        lead_email VARCHAR(100),
        cart_pack VARCHAR(50),
        cart_price INT,
        source VARCHAR(50),
        ip_address VARCHAR(45),
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Table 'abandoned_carts' OK.<br>";

    echo "<hr><h2 style='color:green'>🎉 REPAIR COMPLETE!</h2>";
    echo "You can now go to <a href='flash-products.php'>Flash Products</a> or <a href='../flash-promo.php'>Flash Page</a>.";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Error</h2>" . $e->getMessage();
}
?>
