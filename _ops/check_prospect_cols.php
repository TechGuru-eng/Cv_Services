<?php
require 'admin/config/db.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM prospects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('prospect_cols.json', json_encode($columns, JSON_PRETTY_PRINT));
    echo "Columns written to prospect_cols.json";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
