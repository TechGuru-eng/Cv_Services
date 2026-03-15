<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';

echo "<h1>Analytics Diagnostics</h1>";

function checkTable($pdo, $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "<p style='color:green'>✔ Table '$table' exists.</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Table '$table' MISSING: " . $e->getMessage() . "</p>";
        return false;
    }
}

function checkColumn($pdo, $table, $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✔ Column '$table.$column' exists.</p>";
        } else {
            echo "<p style='color:red'>❌ Column '$table.$column' MISSING.</p>";
            
            // Show what IS there
            echo "<strong>Available columns in '$table':</strong> ";
            $all = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            echo implode(', ', $all) . "<br>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Error checking column: " . $e->getMessage() . "</p>";
    }
}

// 1. Check Tables
echo "<h2>1. Checking Tables</h2>";
checkTable($pdo, 'stats_visits');
checkTable($pdo, 'stats_events');
checkTable($pdo, 'orders');

// 2. Check Key Columns
echo "<h2>2. Checking Critical Columns</h2>";
checkColumn($pdo, 'orders', 'package_name');
checkColumn($pdo, 'orders', 'package');
checkColumn($pdo, 'orders', 'payment_status');

// 3. Test Failing Query
echo "<h2>3. Testing Queries</h2>";
try {
    $dbName = 'Pro';
    // This is the query causing issues
    $query = "SELECT COUNT(*) FROM orders WHERE payment_status = 'verified' AND package_name LIKE '%$dbName%'";
    echo "Running: <code>$query</code><br>";
    $res = $pdo->query($query)->fetchColumn();
    echo "<p style='color:green'>✔ Query success! Result: $res</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Query FAILED: " . $e->getMessage() . "</p>";
}

?>
