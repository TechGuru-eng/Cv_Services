<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/admin/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE orders");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
