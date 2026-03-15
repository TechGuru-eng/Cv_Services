<?php
require 'admin/config/db.php';
$stmt = $pdo->query('DESCRIBE prospects');
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Columns: " . implode(', ', $cols);
?>
