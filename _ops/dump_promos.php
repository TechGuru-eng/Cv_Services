<?php
require 'admin/config/db.php';
$stmt = $pdo->query('SELECT * FROM promo_codes');
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('promos_final.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Data written to promos_final.json";
?>
