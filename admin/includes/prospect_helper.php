<?php
// Identity rules shared by conversion, cart capture and SMS audiences.
function prospectPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone ?? '');
    if (strpos($phone, '00') === 0) $phone = substr($phone, 2);
    if (preg_match('/^6\d{8}$/', $phone)) $phone = '237' . $phone;
    return $phone;
}

function prospectMatches($a, $b) {
    $phone = prospectPhone($a['whatsapp'] ?? '');
    $email = strtolower(trim($a['email'] ?? ''));
    return (strlen($phone) >= 8 && $phone === prospectPhone($b['whatsapp'] ?? ''))
        || ($email !== '' && $email === strtolower(trim($b['email'] ?? '')));
}

function prepareProspectTracking(PDO $pdo) {
    static $ready = false;
    if ($ready) return;
    $columns = $pdo->query('SHOW COLUMNS FROM prospects')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('manual_followup_stage', $columns)) {
        // Historical email levels remain in followup_stage; admin levels start separately.
        $pdo->exec('ALTER TABLE prospects ADD manual_followup_stage INT NOT NULL DEFAULT 0');
    }
    if (!in_array('abandoned_cart_id', $columns)) {
        $pdo->exec('ALTER TABLE prospects ADD abandoned_cart_id INT NULL UNIQUE');
    }
    $ready = true;
}

function importAbandonedProspects(PDO $pdo) {
    prepareProspectTracking($pdo);
    // Keep previously imported carts current when a visitor adds contact information later.
    $pdo->exec("UPDATE prospects p JOIN abandoned_carts c ON p.abandoned_cart_id = c.id
        SET p.name = COALESCE(NULLIF(c.name, ''), p.name),
            p.whatsapp = COALESCE(NULLIF(c.whatsapp, ''), p.whatsapp),
            p.email = COALESCE(NULLIF(c.email, ''), p.email), p.updated_at = c.updated_at
        WHERE c.updated_at > p.updated_at AND p.status IN ('pending', 'lead_audit')");
    $carts = $pdo->query("SELECT c.* FROM abandoned_carts c LEFT JOIN prospects p ON p.abandoned_cart_id = c.id WHERE p.id IS NULL AND c.status IN ('new', 'contacted') ORDER BY c.id")->fetchAll();
    if (!$carts) return;
    $prospects = $pdo->query('SELECT * FROM prospects')->fetchAll();
    $insert = $pdo->prepare("INSERT INTO prospects (name, whatsapp, email, package_name, source, total_amount, status, abandoned_cart_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?) ON DUPLICATE KEY UPDATE abandoned_cart_id = VALUES(abandoned_cart_id)");
    foreach ($carts as $cart) {
        $matched = false;
        foreach ($prospects as $prospect) {
            if (prospectMatches($cart, $prospect)) { $matched = true; break; }
        }
        if ($matched) continue;
        $details = json_decode($cart['cart_details'] ?? '', true) ?: [];
        $insert->execute([$cart['name'] ?: 'Visiteur', $cart['whatsapp'] ?? '', $cart['email'] ?? '',
            $details['package_name'] ?? $details['package'] ?? 'Commande non finalisée',
            $cart['source_page'], (float)($details['amount'] ?? $details['total_amount'] ?? 0),
            $cart['id'], $cart['created_at'], $cart['updated_at']]);
        $prospects[] = $cart;
    }
}

function prospectsWithoutOrders(PDO $pdo) {
    $prospects = $pdo->query("SELECT * FROM prospects WHERE status IN ('pending', 'lead_audit') ORDER BY updated_at DESC")->fetchAll();
    $orders = $pdo->query('SELECT whatsapp, email FROM orders')->fetchAll();
    return array_values(array_filter($prospects, function ($p) use ($orders) {
        foreach ($orders as $o) if (prospectMatches($p, $o)) return false;
        return true;
    }));
}
