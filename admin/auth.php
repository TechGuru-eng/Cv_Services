<?php
// admin/auth.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/activity_helper.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'linkedin' ? '../login.php' : 'login.php'));
    exit;
}
?>
