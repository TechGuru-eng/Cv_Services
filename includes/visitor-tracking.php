<?php
function site_visitor_is_bot(string $userAgent): bool {
    if ($userAgent === '') return false;
    if (preg_match('/HeadlessChrome|bot|crawler|spider|slurp|lighthouse|embedly|quora link preview|vkShare|W3C_Validator|WhatsApp|Flipboard|tumblr|SkypeShell|developers\.google\.com|python-requests|node-fetch|axios|curl|Go-http-client|postman/i', $userAgent)) return true;
    return strlen($userAgent) < 30 && !preg_match('/Mobile|Safari|Firefox|Chrome/i', $userAgent);
}

// One record per PHP session per calendar day. Heartbeats only refresh that record.
function record_site_visit(PDO $pdo, array $visitor): int {
    $visitId = (int)($_SESSION['visit_id'] ?? 0);
    if ($visitId) {
        $stmt = $pdo->prepare('SELECT id FROM stats_visits WHERE id = ? AND created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY');
        $stmt->execute([$visitId]);
        if (!$stmt->fetchColumn()) $visitId = 0;
    }
    if ($visitId) {
        $stmt = $pdo->prepare('UPDATE stats_visits SET page_url = ?, updated_at = NOW(), browser = ? WHERE id = ?');
        $stmt->execute([$visitor['page'], $visitor['browser'], $visitId]);
    } else {
        $stmt = $pdo->prepare('SELECT id FROM stats_visits WHERE ip_address = ? AND created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY AND is_bot = ? LIMIT 1');
        $stmt->execute([$visitor['ip'], $visitor['is_bot']]);
        $unique = $stmt->fetchColumn() ? 0 : 1;
        $stmt = $pdo->prepare('INSERT INTO stats_visits (ip_address, country, page_url, user_agent, browser, is_unique, utm_source, utm_medium, utm_campaign, is_bot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$visitor['ip'], $visitor['country'], $visitor['page'], $visitor['user_agent'], $visitor['browser'], $unique,
            $visitor['utm_source'] ?? null, $visitor['utm_medium'] ?? null, $visitor['utm_campaign'] ?? null, $visitor['is_bot']]);
        $visitId = (int)$pdo->lastInsertId();
    }
    $_SESSION['visit_id'] = $visitId;
    return $visitId;
}

function site_visits_today(PDO $pdo): int {
    // Also includes sessions created before this fix that were active today.
    return (int)$pdo->query('SELECT COUNT(*) FROM stats_visits WHERE COALESCE(is_bot, 0) = 0 AND
        ((created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY) OR
         (updated_at >= CURDATE() AND updated_at < CURDATE() + INTERVAL 1 DAY))')->fetchColumn();
}
