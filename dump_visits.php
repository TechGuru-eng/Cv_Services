<?php
require_once 'admin/config/db.php';
$t = 'stats_visits';
echo "--- $t ---\n";
$stmt = $pdo->query("DESCRIBE $t");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . " ";
echo "\n";
?>
