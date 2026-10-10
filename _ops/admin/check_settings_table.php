<?php
require_once 'config/db.php';
try {
    $stmt = $pdo->query("SELECT 1 FROM settings LIMIT 1");
    echo "EXISTS";
} catch (PDOException $e) {
    echo "MISSING";
}
?>
