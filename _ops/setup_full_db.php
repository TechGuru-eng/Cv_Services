<?php
// setup_full_db.php
require_once 'admin/config/db.php';

try {
    echo "Starting Database Migration...<br>";

    // Get current columns in 'orders'
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Current columns: " . implode(', ', $columns) . "<br>";

    // 1. Handle 'client_name' -> 'name'
    if (in_array('client_name', $columns)) {
        $pdo->exec("ALTER TABLE orders CHANGE COLUMN client_name name VARCHAR(255)");
        echo "Renamed 'client_name' to 'name'.<br>";
    } elseif (!in_array('name', $columns)) {
        // Should not happen if table exists, but purely defensive
        $pdo->exec("ALTER TABLE orders ADD COLUMN name VARCHAR(255)");
        echo "Added 'name' column.<br>";
    }

    // 2. Handle 'transaction_id' -> 'transaction_number'
    if (in_array('transaction_id', $columns)) {
        $pdo->exec("ALTER TABLE orders CHANGE COLUMN transaction_id transaction_number VARCHAR(100)");
        echo "Renamed 'transaction_id' to 'transaction_number'.<br>";
    } elseif (!in_array('transaction_number', $columns)) {
        // If neither exists, just add the new one
        $pdo->exec("ALTER TABLE orders ADD COLUMN transaction_number VARCHAR(100)");
        echo "Added 'transaction_number' column.<br>";
    }

    // Refresh columns list after renames
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Add 'country'
    if (!in_array('country', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN country VARCHAR(100) AFTER whatsapp");
        echo "Added 'country' column.<br>";
    }

    // 4. Add 'payment_proof'
    if (!in_array('payment_proof', $columns)) {
        // Try to place it after transaction_number if it exists, else just add it
        if (in_array('transaction_number', $columns)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(255) AFTER transaction_number");
        } else {
            $pdo->exec("ALTER TABLE orders ADD COLUMN payment_proof VARCHAR(255)");
        }
        echo "Added 'payment_proof' column.<br>";
    }

    // 5. Ensure 'amount' exists (It should, but let's be safe)
    if (!in_array('amount', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN amount DECIMAL(10,2) DEFAULT 0");
        echo "Added 'amount' column.<br>";
    }


    // 6. Create CV_DETAILS table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cv_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        personal_info JSON,
        education JSON,
        experience JSON,
        skills JSON,
        linkedin VARCHAR(255),
        uploaded_cv_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    echo "Created/Verified 'cv_details' table.<br>";

    // 7. Create REVENUE table
    $pdo->exec("CREATE TABLE IF NOT EXISTS revenue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        amount DECIMAL(10,2),
        payment_method VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");
    echo "Created/Verified 'revenue' table.<br>";

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>