<?php
// api/get_active_popup.php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM exit_popups WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
    $popup = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($popup) {
        echo json_encode([
            'success' => true,
            'popup' => $popup
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active popup'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
