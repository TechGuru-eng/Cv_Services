<?php
require 'admin/config/db.php';
$stmt = $pdo->query('DESCRIBE orders');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}
?>
