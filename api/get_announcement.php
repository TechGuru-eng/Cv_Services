<?php
// api/get_announcement.php
require_once __DIR__ . '/../admin/config/db.php';

header('Content-Type: application/json');

try {
    // Check for active announcement
    // Logic: is_active = 1
    // AND (start_date IS NULL OR start_date <= NOW())
    // AND (end_date IS NULL OR end_date >= NOW())
    // Order by ID DESC (latest created takes precedence)
    
    // Relaxed Timezone Check: Allow announcements starting up to 12 hours in the future (relative to DB time) 
    // to account for UTC vs Local Time mismatches.
    $stmt = $pdo->prepare("
        SELECT * FROM announcements 
        WHERE is_active = 1 
        AND (start_date IS NULL OR start_date <= DATE_ADD(NOW(), INTERVAL 12 HOUR)) 
        AND (end_date IS NULL OR end_date >= DATE_SUB(NOW(), INTERVAL 12 HOUR)) 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $announcement = $stmt->fetch();

    if ($announcement) {
        echo json_encode([
            'success' => true,
            'data' => $announcement
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active announcement'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
