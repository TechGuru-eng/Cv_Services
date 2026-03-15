<?php
// api/get_exit_popup.php
require_once __DIR__ . '/../admin/config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("SELECT * FROM exit_popups WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $popup = $stmt->fetch();

    if ($popup) {
        // Decode slices JSON if it exists as a string
        if (!empty($popup['slices']) && is_string($popup['slices'])) {
            $popup['slices'] = json_decode($popup['slices']);
        }



        echo json_encode([
            'success' => true,
            'data' => $popup
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No active popup'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
