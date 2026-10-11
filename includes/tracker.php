<?php
// Server-rendered public pages share the same daily visit records as api/track.php.
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pdo)) require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/visitor-tracking.php';
try {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    record_site_visit($pdo, [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'country' => $_SESSION['visitor_country'] ?? 'Cameroun',
        'page' => parse_url($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH) ?: '/',
        'user_agent' => $userAgent, 'browser' => 'Unknown',
        'is_bot' => site_visitor_is_bot($userAgent) ? 1 : 0,
    ]);
} catch (Throwable $e) {
    error_log('Visitor tracking error: ' . $e->getMessage());
}
