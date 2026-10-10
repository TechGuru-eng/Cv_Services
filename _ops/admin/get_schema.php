<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE prospects");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
