<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../admin/config/db.php';

try {
    // 1. Fetch all available designs
    $stmt = $pdo->query("SELECT * FROM cv_designs ORDER BY id ASC");
    $designs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($designs)) {
        throw new Exception("No designs found in database");
    }

    // 2. Calculate the 12-hour slot index
    // user wants change "after every 5 hours"
    // 5 hours in seconds = 5 * 3600 = 18000
    $interval = 18000; 
    $currentTime = time();
    $slotIndex = floor($currentTime / $interval);

    // 3. Select design based on slot index
    // The modulus operator ensuring we loop through avail designs
    $totalDesigns = count($designs);
    $selectedDesignIndex = $slotIndex % $totalDesigns;
    $selectedDesign = $designs[$selectedDesignIndex];

    // 4. Return the result
    echo json_encode([
        'success' => true,
        'image_path' => $selectedDesign['image_path'], // supposedly relative to root, e.g. "uploads/designs/foo.png"
        'title' => $selectedDesign['title'],
        'slot_changed' => date('Y-m-d H:i:s', $slotIndex * $interval),
        'next_change' => date('Y-m-d H:i:s', ($slotIndex + 1) * $interval)
    ]);

} catch (Exception $e) {
    // Fallback or error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'image_path' => 'assets/cv-after-professional.png' // Fallback to default
    ]);
}
