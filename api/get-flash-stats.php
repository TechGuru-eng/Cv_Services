<?php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

try {
    $ip = $_SERVER['REMOTE_ADDR'];
    $page = $_GET['page'] ?? 'flash';

    // 1. Upsert current visitor
    $stmt = $pdo->prepare("INSERT INTO active_visitors (ip_address, page) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_seen = NOW()");
    $stmt->execute([$ip, $page]);

    // 2. Remove old visitors (> 5 minutes inactivity)
    $pdo->query("DELETE FROM active_visitors WHERE last_seen < (NOW() - INTERVAL 5 MINUTE)");

    // 3. Count active
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM active_visitors WHERE page = ?");
    $stmtCount->execute([$page]);
    $realCount = $stmtCount->fetchColumn();

    // 4. Fake boost (+10 as requested)
    $displayCount = $realCount + 10;

    echo json_encode(['count' => $displayCount]);
    
} catch (Exception $e) {
    echo json_encode(['count' => 11]); // Fallback safe value (1 + 10)
}
?>
