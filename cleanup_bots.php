<?php
// cleanup_bots.php - One-time cleanup for existing bot entries
require_once 'admin/config/db.php';

// Patterns to detect bots in existing logs
$bot_patterns = [
    'HeadlessChrome', 'bot', 'crawler', 'spider', 'slurp', 'lighthouse', 
    'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'facebookexternalhit',
    'twitterbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link preview'
];

try {
    // 1. Ensure column exists (Double check)
    $pdo->exec("ALTER TABLE stats_visits ADD COLUMN IF NOT EXISTS is_bot TINYINT(1) DEFAULT 0");
    
    echo "Column is_bot ensured.\n";

    // 2. Build CASE statement for batch update
    $conditions = [];
    foreach ($bot_patterns as $p) {
        $conditions[] = "user_agent LIKE " . $pdo->quote("%$p%");
    }
    $where = implode(" OR ", $conditions);

    $count = $pdo->exec("UPDATE stats_visits SET is_bot = 1 WHERE is_bot = 0 AND ($where)");
    
    echo "Cleanup complete: Flagged $count existing visits as bots.\n";

    // 3. Verify structure
    $stmt = $pdo->query("DESCRIBE stats_visits");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        if ($col['Field'] == 'is_bot') {
            echo "Verification: is_bot column found.\n";
            break;
        }
    }

} catch (Exception $e) {
    echo "Cleanup Error: " . $e->getMessage() . "\n";
}
