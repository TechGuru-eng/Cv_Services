<?php
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/sql/announcements.sql';
$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "<h1>Announcements Table Created Successfully</h1>";
    echo "<p>Table `announcements` is ready.</p>";
} catch (PDOException $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
