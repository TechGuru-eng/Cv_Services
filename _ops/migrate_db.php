<?php
// migrate_db.php - Diagnostic version
$host = 'localhost';
$dbname = 'hireme_db';
$username = 'root';
$password = '';

echo "Attempting connection to $dbname at $host...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
} catch (PDOException $e) {
    echo "First attempt failed: " . $e->getMessage() . "\n";
    $dbname = 'u158764452_hireme_db';
    echo "Attempting connection to $dbname at $host...\n";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    } catch (PDOException $e2) {
        echo "Second attempt failed: " . $e2->getMessage() . "\n";
        
        echo "Attempting with 127.0.0.1...\n";
        $host = '127.0.0.1';
        $dbname = 'hireme_db';
        try {
             $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        } catch (PDOException $e3) {
            echo "Third attempt failed: " . $e3->getMessage() . "\n";
            exit(1);
        }
    }
}

echo "Connection successful!\n";

try {
    // Add is_bot column if it doesn't exist
    $pdo->exec("ALTER TABLE stats_visits ADD COLUMN IF NOT EXISTS is_bot TINYINT(1) DEFAULT 0");
    
    // Add index for filtering
    try {
        $pdo->exec("CREATE INDEX idx_is_bot ON stats_visits(is_bot)");
    } catch (Exception $e) {
        echo "Index step: " . $e->getMessage() . "\n";
    }

    echo "Database updated successfully.\n";
    
    // Show structure
    $stmt = $pdo->query("DESCRIBE stats_visits");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Execution error: " . $e->getMessage() . "\n";
}
