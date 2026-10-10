<?php
require_once 'admin/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE orders");
    while ($row = $stmt->fetch()) {
        echo "Field: {$row['Field']} - Type: {$row['Type']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
