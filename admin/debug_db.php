<?php
require_once 'config/db.php';

echo "<h1>Database Debug</h1>";

function describeTable($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "<h2>Table: $table</h2>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars($val) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<h2>Table: $table</h2><p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}

describeTable($pdo, 'activities');
describeTable($pdo, 'prospects');
describeTable($pdo, 'orders');
?>
