<?php
function cvPackOverview(PDO $pdo, string $search = '', string $filter = ''): array {
    // Associate recovery entries with the exact order; repeated purchases need their own pack.
    $rows = $pdo->query("SELECT o.id, o.order_ref, o.name, o.email, o.whatsapp, o.created_at,
        p.id AS pack_id, p.full_name, p.email AS pack_email, p.pack_link, p.status AS pack_status
        FROM orders o LEFT JOIN cv_packs p ON p.id=(SELECT MAX(cp.id) FROM cv_packs cp WHERE cp.order_id=o.id)
        WHERE o.payment_status='verified' ORDER BY o.created_at DESC, o.id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $clients = []; $addedOrders = 0;
    foreach ($rows as $row) {
        $email = mb_strtolower(trim($row['email'] ?? ''));
        $phone = preg_replace('/\D/', '', $row['whatsapp'] ?? '');
        $key = $email !== '' ? 'email:' . $email : ($phone !== '' ? 'phone:' . $phone : 'order:' . $row['id']);
        if (!isset($clients[$key])) $clients[$key] = true;
        if (!$row['pack_id']) $clients[$key] = false;
        else $addedOrders++;
    }
    $addedClients = count(array_filter($clients));
    $metrics = ['clients' => count($clients), 'added_clients' => $addedClients, 'missing_clients' => count($clients) - $addedClients,
        'orders' => count($rows), 'added_orders' => $addedOrders, 'missing_orders' => count($rows) - $addedOrders];
    $needle = mb_strtolower(trim($search));
    $digits = preg_replace('/\D/', '', $search);
    $rows = array_values(array_filter($rows, static function ($row) use ($needle, $digits, $search, $filter) {
        if ($filter === 'missing' && $row['pack_id']) return false;
        if ($filter === 'added' && !$row['pack_id']) return false;
        if ($needle === '') return true;
        foreach (['name', 'email', 'whatsapp', 'order_ref', 'full_name', 'pack_email'] as $field) {
            if (mb_strpos(mb_strtolower($row[$field] ?? ''), $needle) !== false) return true;
        }
        return strlen($digits) >= 6 && preg_match('/^[+0-9\s().-]+$/', $search)
            && str_contains(preg_replace('/\D/', '', $row['whatsapp'] ?? ''), $digits);
    }));
    return ['metrics' => $metrics, 'rows' => $rows];
}
