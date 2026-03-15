<?php
// db_check.php
require_once 'admin/config/db.php';
echo "Connected to Database: " . $dbname . "<br>";
$stmt = $pdo->query("SHOW COLUMNS FROM orders");
echo "Columns in 'orders':<br><ul>";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<li>" . $row['Field'] . "</li>";
}
echo "</ul>";
?>
