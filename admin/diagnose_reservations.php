<?php
// admin/diagnose_reservations.php
require_once 'config/db.php';

echo "<h1>Reservation System Diagnosis</h1>";

// 1. Check Table Structure
echo "<h2>1. Table 'orders' Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE orders");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Default</th></tr>";
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";

    if (!in_array('order_type', $columns)) {
        echo "<p style='color:red'>CRITICAL: Column 'order_type' is MISSING!</p>";
    } else {
        echo "<p style='color:green'>SUCCESS: Column 'order_type' exists.</p>";
    }

    if (!in_array('due_date', $columns)) {
        echo "<p style='color:red'>CRITICAL: Column 'due_date' is MISSING!</p>";
    } else {
        echo "<p style='color:green'>SUCCESS: Column 'due_date' exists.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Error describing 'orders': " . $e->getMessage() . "</p>";
}

// 2. Check Row Counts
echo "<h2>2. Row Counts by Order Type</h2>";
try {
    if (in_array('order_type', $columns)) {
        $stmt = $pdo->query("SELECT order_type, COUNT(*) as count FROM orders GROUP BY order_type");
        echo "<table border='1'><tr><th>Order Type</th><th>Count</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>" . ($row['order_type'] ?: 'NULL') . "</td><td>{$row['count']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error counting row types: " . $e->getMessage() . "</p>";
}

// 3. Test Query & Prefix Check
echo "<h2>3. Testing 'advance' Filter & Prefix Check</h2>";
try {
    if (in_array('order_type', $columns)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_type = 'advance'");
        $count = $stmt->fetchColumn();
        echo "<p>Rows matching <code>order_type = 'advance'</code>: <strong>$count</strong></p>";

        $stmtPrefix = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_ref LIKE 'RSV-%'");
        $prefixCount = $stmtPrefix->fetchColumn();
        echo "<p>Rows with <code>order_ref</code> starting with 'RSV-': <strong>$prefixCount</strong></p>";

        if ($prefixCount > $count) {
            echo "<p style='color:orange'>WARNING: You have " . ($prefixCount - $count) . " orders starting with 'RSV-' that are NOT marked as 'advance'. These are likely reservations saved with the wrong type.</p>";
            
            echo "<h3>Sample Misclassified Orders:</h3>";
            $stmtSample = $pdo->query("SELECT order_ref, order_type, name FROM orders WHERE order_ref LIKE 'RSV-%' AND order_type != 'advance' LIMIT 5");
            echo "<ul>";
            while($s = $stmtSample->fetch()) {
                echo "<li>{$s['order_ref']} - {$s['name']} (Type: {$s['order_type']})</li>";
            }
            echo "</ul>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error running filter query: " . $e->getMessage() . "</p>";
}

// 4. Show Last 5 Orders
echo "<h2>4. Last 5 Orders in Database</h2>";
try {
    $stmt = $pdo->query("SELECT id, order_ref, name, order_type, created_at FROM orders ORDER BY id DESC LIMIT 5");
    echo "<table border='1'><tr><th>ID</th><th>Ref</th><th>Name</th><th>Type</th><th>Date</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['order_ref']}</td><td>{$row['name']}</td><td>{$row['order_type']}</td><td>{$row['created_at']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error listing orders: " . $e->getMessage() . "</p>";
}

// 5. Deep Table Check
echo "<h2>5. Table Structure Depth Check</h2>";
function checkTableStructure($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        echo "<h3>Table: $tableName</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
}
checkTableStructure($pdo, 'cv_details');

// 6. Folder Permissions
echo "<h2>6. Folder Permissions Check</h2>";
$uploadsDir = '../uploads';
if (is_dir($uploadsDir)) {
    echo "<p>Directory <code>uploads/</code> exists.</p>";
    if (is_writable($uploadsDir)) {
        echo "<p style='color:green'>SUCCESS: Directory <code>uploads/</code> is WRITABLE.</p>";
    } else {
        echo "<p style='color:red'>CRITICAL: Directory <code>uploads/</code> is NOT WRITABLE.</p>";
    }
} else {
    echo "<p style='color:orange'>WARNING: Directory <code>uploads/</code> does not exist.</p>";
    // Try to create it to see if we have permissions
    if (@mkdir($uploadsDir, 0777, true)) {
        echo "<p style='color:green'>SUCCESS: Created <code>uploads/</code> directory successfully.</p>";
    } else {
        echo "<p style='color:red'>FAILURE: Could not create <code>uploads/</code> directory.</p>";
    }
}

// 7. Repair & Diagnostic Tools
echo "<h2>7. Repair & Diagnostic Tools</h2>";

// Rollback Action
if (isset($_GET['rollback'])) {
    try {
        $pdo->exec("UPDATE orders SET order_type = 'standard' WHERE order_ref LIKE 'CMD-%'");
        $pdo->exec("UPDATE orders SET order_type = 'advance' WHERE order_ref LIKE 'RSV-%'");
        echo "<p style='color:green; font-weight:bold;'>SUCCESS: Order types rolled back based on prefixes (CMD=standard, RSV=advance).</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Error rolling back: " . $e->getMessage() . "</p>";
    }
}

// Full Simulation Action
if (isset($_GET['simulate'])) {
    echo "<h3>Full Simulation Result:</h3>";
    try {
        $pdo->beginTransaction();
        
        $testRef = 'RSV-TEST-' . time();
        $sql = "INSERT INTO orders (
            order_ref, name, email, whatsapp, country, city, package_name, 
            payment_status, amount, order_type, due_date, created_at
        ) VALUES (:ref, 'Test Simulation', 'test@example.com', '000000', '', 'Test City', 'Standard', 'pending', 2000, 'advance', '2026-12-31', NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ref' => $testRef]);
        $orderId = $pdo->lastInsertId();
        echo "<p style='color:green'>Step 1: Order created. ID: $orderId</p>";
        
        $jsonTest = json_encode(['test' => 'data'], JSON_UNESCAPED_UNICODE);
        $sqlCv = "INSERT INTO cv_details (order_id, personal_info, education, experience, skills, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmtCv = $pdo->prepare($sqlCv);
        $stmtCv->execute([$orderId, $jsonTest, $jsonTest, $jsonTest, $jsonTest]);
        echo "<p style='color:green'>Step 2: CV details created with JSON.</p>";
        
        $pdo->commit();
        echo "<p style='color:green; font-weight:bold;'>FULL SUCCESS: Transaction committed.</p>";
        
        // Clean up test
        $pdo->exec("DELETE FROM orders WHERE id = $orderId");
        echo "<p style='color:blue'>Note: Test records were cleaned up.</p>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<div style='background:#fee; border:1px solid red; padding:10px;'>";
        echo "<p style='color:red; font-weight:bold;'>FAILURE detected!</p>";
        echo "<p>Error Message: <strong>" . $e->getMessage() . "</strong></p>";
        echo "</div>";
    }
}

echo "<div style='display:flex; gap:10px;'>";
echo "<a href='?rollback=1' style='display:inline-block; padding:10px 20px; background:orange; color:white; text-decoration:none; border-radius:5px;'>Rollback Order Types</a>";
echo "<a href='?simulate=1' style='display:inline-block; padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px;'>Full Simulation (Test)</a>";
echo "</div>";

echo "<hr><p>1. Run <strong>Rollback</strong> if needed.<br>2. Run <strong>Full Simulation</strong> to check for transaction failures.</p>";
?>
