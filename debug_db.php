<?php
require_once 'admin/config/db.php';
try {
    $stmt = $pdo->query("DESCRIBE prospects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
