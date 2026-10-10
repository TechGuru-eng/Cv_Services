<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT * FROM exit_popups LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($row);
echo "</pre>";
