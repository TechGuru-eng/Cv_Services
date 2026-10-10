<?php
require_once 'admin/config/db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    echo "Total admins: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT id, email FROM admins");
    while($row = $stmt->fetch()) {
        echo "ID: " . $row['id'] . " | Email: " . $row['email'] . "\n";
    }
} catch (Exception $e) {
    echo "Error checking admins: " . $e->getMessage() . "\n";
}
?>
