<?php
// api/get_active_announcement.php
require_once __DIR__ . '/../admin/config/db.php';

header('Content-Type: application/json');

try {
    // Check for active announcement where current time is between start_date and end_date
    // AND is_active is true. Order by id DESC (latest created) logic or created_at
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        WHERE is_active = 1 
        AND start_date <= NOW() 
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($announcement) {
        echo json_encode(['success' => true, 'data' => $announcement]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No active announcement']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
