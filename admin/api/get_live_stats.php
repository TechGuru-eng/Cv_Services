<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
require_once '../config/db.php';

// Parameters
$hide_bots = isset($_GET['hide_bots']) && $_GET['hide_bots'] == '1';
$bot_clause = $hide_bots ? " AND is_bot = 0 " : "";

// 1. Active Now (Last 10 minutes)
$activeNow = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM stats_visits WHERE updated_at >= NOW() - INTERVAL 10 MINUTE $bot_clause")->fetchColumn();

// 2. Recent Active Visitors
$stmt = $pdo->query("SELECT * FROM stats_visits WHERE 1=1 $bot_clause ORDER BY updated_at DESC LIMIT 15");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. High Intent Events (The "Adrenaline" Feed)
$stmtEvents = $pdo->query("SELECT e.*, v.country, v.page_url, v.ip_address 
                           FROM stats_events e 
                           JOIN stats_visits v ON e.visit_id = v.id 
                           WHERE e.event_name IN ('pack_selected', 'payment_initiate', 'funnel_step_2_details', 'click_whatsapp_float') 
                           ORDER BY e.created_at DESC LIMIT 8");
$highIntent = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// 4. Sales Flash (Last 24h revenue)
$todayRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND created_at >= CURDATE()")->fetchColumn() ?: 0;

header('Content-Type: application/json');
echo json_encode([
    'active_now' => $activeNow,
    'visitors' => $visitors,
    'high_intent' => $highIntent,
    'today_revenue' => $todayRevenue,
    'timestamp' => date('Y-m-d H:i:s'),
    'server_now_ms' => round(microtime(true) * 1000)
]);
