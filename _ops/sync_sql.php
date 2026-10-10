<?php
// Consolidated final schema.sql generator
require_once 'admin/config/db.php';

function get_create($table) {
    global $pdo;
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $res = $stmt->fetch(PDO::FETCH_NUM);
    return $res[1] . ";\n\n";
}

$tables = ['admins', 'orders', 'promo_codes', 'cv_details', 'revenue', 'affiliates', 'affiliate_referrals', 'digital_products'];
$sql = "-- Full Database Export - Generated for Sync\n\n";

foreach ($tables as $t) {
    try {
        $sql .= get_create($t);
    } catch (Exception $e) {
        $sql .= "-- Table $t not found or error: " . $e->getMessage() . "\n\n";
    }
}

file_put_contents('c:/laragon/www/MonCv/admin/sql/full_schema_sync.sql', $sql);
echo "Full schema synced to admin/sql/full_schema_sync.sql\n";
?>
