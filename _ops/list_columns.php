<?php
require 'admin/config/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM prospects');
echo "COLUMNS_START\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}
echo "COLUMNS_END\n";
?>
