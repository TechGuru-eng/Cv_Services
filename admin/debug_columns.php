<?php
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in orders table:\n";
    foreach ($columns as $col) {
        echo "- " . $col . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
