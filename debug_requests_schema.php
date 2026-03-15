<?php
require_once 'admin/config/db.php';
$stmt = $pdo->query("DESCRIBE sponsorship_requests");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) {
    echo $c['Field'] . "\n";
}
?>
