<?php
require_once 'admin/config/db.php';

echo "Orders Schema:\n";
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $c) echo $c['Field']."\n";
} catch(Exception $e) { echo $e->getMessage(); }

echo "\nSponsorship Requests Schema:\n";
try {
    $stmt = $pdo->query("DESCRIBE sponsorship_requests");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $c) echo $c['Field']."\n";
} catch(Exception $e) { echo $e->getMessage(); }

echo "\nSponsorship Pool Schema:\n";
try {
    $stmt = $pdo->query("DESCRIBE sponsorship_pool");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $c) echo $c['Field']."\n";
} catch(Exception $e) { echo $e->getMessage(); }
?>
