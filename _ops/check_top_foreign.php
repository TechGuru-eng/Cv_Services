<?php
require_once 'admin/config/db.php';
$stmt = $pdo->query("SELECT country, count(*) as count FROM stats_visits WHERE country NOT IN ('Cameroun', 'Cameroon', 'CM', 'Unknown') AND country IS NOT NULL GROUP BY country ORDER BY count DESC LIMIT 5");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['country']}: {$r['count']}\n";
}
?>
