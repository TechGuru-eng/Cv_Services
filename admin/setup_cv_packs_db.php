<?php
require_once __DIR__ . '/config/db.php';

$sqlFile = __DIR__ . '/sql/cv_packs.sql';
$sql = file_get_contents($sqlFile);

try {
    $pdo->exec($sql);
    echo "<h1>CV Packs Table Ready</h1>";
} catch (PDOException $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
