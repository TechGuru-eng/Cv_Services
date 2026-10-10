<?php
require_once 'admin/config/db.php';

try {
    $tables = ['sponsorship_pool', 'sponsorship_requests'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<h3>$table</h3>";
        echo "<pre>" . htmlspecialchars($result['Create Table']) . ";</pre>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
