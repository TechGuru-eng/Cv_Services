<?php
require_once 'admin/config/db.php';
echo "--- Orders Countries ---\n";
$stmt = $pdo->query("SELECT DISTINCT country FROM orders");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo ($r['country'] ?: 'NULL') . "\n";

echo "\n--- Prospects Cities (Location) ---\n";
$stmt = $pdo->query("SELECT DISTINCT city FROM prospects");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo ($r['city'] ?: 'NULL') . "\n";

echo "\n--- Traffic Countries ---\n";
$stmt = $pdo->query("SELECT DISTINCT country FROM stats_visits");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo ($r['country'] ?: 'NULL') . "\n";
?>
