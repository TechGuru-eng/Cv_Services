<?php
require_once 'admin/config/db.php';
echo "Current Server Time: " . date('Y-m-d H:i:s') . "\n\n";

$stmt = $pdo->query("SELECT * FROM announcements");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "Table 'announcements' is EMPTY.\n";
} else {
    foreach ($rows as $r) {
        echo "ID: " . $r['id'] . "\n";
        echo "Active: " . $r['is_active'] . "\n";
        echo "Start: " . $r['start_date'] . "\n";
        echo "End: " . $r['end_date'] . "\n";
        echo "Type: " . $r['type'] . "\n";
        echo "-------------------\n";
    }
}
