<?php
/**
 * Helper to log admin and system activities
 */
function log_activity($action, $details = null, $user_id = null, $user_email = null) {
    global $pdo;
    
    // Auto-detect admin if not provided
    if ($user_id === null && isset($_SESSION['admin_id'])) {
        $user_id = $_SESSION['admin_id'];
    }
    if ($user_email === null && isset($_SESSION['admin_email'])) {
        $user_email = $_SESSION['admin_email'];
    } elseif ($user_email === null && isset($_SESSION['admin'])) {
        $user_email = $_SESSION['admin']; // Fallback to email in 'admin' key
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, user_email, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $user_email, $action, $details, $ip]);
    } catch (Exception $e) {
        // Silently fail to not break the main app flow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
