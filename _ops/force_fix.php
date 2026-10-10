<?php
// force_fix.php
$host = '127.0.0.1';
$dbname = 'hireme_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to $dbname at $host\n";
    
    /* $pdo->exec("ALTER TABLE orders ADD COLUMN due_date DATE AFTER payment_proof"); */
    echo "Skipping due_date (already exists)\n";
    
    $pdo->exec("ALTER TABLE orders ADD COLUMN order_type VARCHAR(20) DEFAULT 'standard' AFTER due_date");
    echo "Added order_type\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
