<?php
header('Content-Type: application/json');
try {
    require_once 'admin/config/db.php';
    // If we get here, DB connection worked (otherwise db.php would die)
    echo json_encode(['success' => true, 'message' => 'DB CONNECTION SUCCESSFUL']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
