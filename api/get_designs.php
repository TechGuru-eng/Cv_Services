<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM cv_designs ORDER BY created_at DESC");
    $designs = $stmt->fetchAll();
    
    // Process paths to ensure they are full URLs or correct relative paths
    foreach ($designs as &$design) {
        // Paths are stored as 'uploads/designs/filename.png'
        // We ensure they are clean
        $design['image_path_clean'] = $design['image_path'];
    }
    
    echo json_encode(['success' => true, 'designs' => $designs]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
