<?php
// api/get_announcement.php
require_once __DIR__ . '/../admin/config/db.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

try {
    $stmt = $pdo->prepare("SELECT id, message, type, start_date, end_date FROM announcements
        WHERE is_active = 1
        AND (start_date IS NULL OR start_date <= NOW())
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY id DESC LIMIT 1");
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
    error_log('Announcement lookup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Annonce indisponible.']);
}
