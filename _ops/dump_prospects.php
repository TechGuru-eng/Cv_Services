<?php
require_once 'admin/config/db.php';
$t = 'prospects';
echo "--- $t ---\n";
$stmt = $pdo->query("DESCRIBE $t");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) echo $r['Field'] . " ";
echo "\n";
?>
