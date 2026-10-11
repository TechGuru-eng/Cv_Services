<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// Parameters
$hide_bots = isset($_GET['hide_bots']) && $_GET['hide_bots'] == '1';
$bot_clause = $hide_bots ? " AND is_bot = 0 " : "";

try {
    // 1. Active Now (Last 20 minutes)
    $activeWindow = date('Y-m-d H:i:s', strtotime('-20 minutes'));
    $activeNowStmt = $pdo->prepare("SELECT COUNT(*) FROM stats_visits WHERE updated_at >= ? $bot_clause");
    $activeNowStmt->execute([$activeWindow]);
    $activeNow = $activeNowStmt->fetchColumn() ?: 0;

    // 2. Recent Active Visitors
    $stmt = $pdo->prepare("SELECT * FROM stats_visits WHERE updated_at >= ? $bot_clause ORDER BY updated_at DESC");
    $stmt->execute([$activeWindow]);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 3. High Intent Events
    $hideEventBots = $hide_bots ? "v.is_bot = 0 AND" : "";
    $stmtEvents = $pdo->query("SELECT e.*, v.country, v.page_url, v.ip_address
                               FROM stats_events e
                               JOIN stats_visits v ON e.visit_id = v.id
                               WHERE {$hideEventBots} e.event_name IN ('pack_selected', 'payment_initiate', 'funnel_step_2_details', 'click_whatsapp_float')
                               AND v.updated_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE) ORDER BY e.created_at DESC LIMIT 10");
    $highIntent = $stmtEvents->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 4. Sales Flash
    $today = date('Y-m-d');
    $todayRevenueStmt = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND DATE(created_at) = ?");
    $todayRevenueStmt->execute([$today]);
    $todayRevenue = $todayRevenueStmt->fetchColumn() ?: 0;

    // 5. Last Recorded Visit (For debugging connection)
    $lastVisit = $pdo->query("SELECT updated_at FROM stats_visits ORDER BY updated_at DESC LIMIT 1")->fetchColumn() ?: null;

    echo json_encode([
        'success' => true,
        'active_now' => (int)$activeNow,
        'visitors' => $visitors,
        'high_intent' => $highIntent,
        'today_revenue' => (float)$todayRevenue,
        'last_db_visit' => $lastVisit,
        'timestamp' => date('Y-m-d H:i:s'),
        'server_now_ms' => round(microtime(true) * 1000)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'active_now' => 0,
        'visitors' => [],
        'high_intent' => [],
        'today_revenue' => 0
    ]);
}
