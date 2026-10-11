<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../includes/visitor-tracking.php';
function visitor_check(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
}
$pdo->beginTransaction();
$_SESSION = [];
try {
    $baseline = site_visits_today($pdo);
    $visitor = ['ip' => '198.51.100.249', 'country' => 'Cameroun', 'page' => '/index.html',
        'user_agent' => 'Mozilla/5.0 Chrome/130.0 Safari/537.36 Regression', 'browser' => 'Chrome', 'is_bot' => 0];
    $first = record_site_visit($pdo, $visitor);
    visitor_check(site_visits_today($pdo) === $baseline + 1, 'New visit counted by dashboard');
    visitor_check(record_site_visit($pdo, $visitor) === $first && site_visits_today($pdo) === $baseline + 1, 'Refresh and heartbeat do not inflate visits');
    $visitor['page'] = '/blog.php';
    visitor_check(record_site_visit($pdo, $visitor) === $first, 'Server and pixel trackers share a visit');
    $pdo->prepare('UPDATE stats_visits SET created_at = DATE_SUB(CURDATE(), INTERVAL 1 DAY), updated_at = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE id = ?')->execute([$first]);
    $next = record_site_visit($pdo, $visitor);
    visitor_check($next !== $first && site_visits_today($pdo) === $baseline + 1, 'Returning session counted on a new day');
    $_SESSION = [];
    $visitor['is_bot'] = 1;
    $bot = record_site_visit($pdo, $visitor);
    visitor_check(site_visits_today($pdo) === $baseline + 1, 'Bots excluded from dashboard');
    $_SESSION = ['visit_id' => PHP_INT_MAX];
    $visitor['is_bot'] = 0;
    visitor_check(record_site_visit($pdo, $visitor) !== PHP_INT_MAX && site_visits_today($pdo) === $baseline + 2, 'Deleted session visit recovers');
    visitor_check(site_visitor_is_bot('Googlebot') && !site_visitor_is_bot($visitor['user_agent']), 'Bot classifier');
    echo "7 visitor tracking checks passed; all fixture visits rolled back.\n";
} finally { $pdo->rollBack(); }
