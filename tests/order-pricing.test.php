<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../includes/order-pricing.php';
function quote_check(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
}
function rejects_quote(PDO $pdo, array $input, string $label): void {
    try { order_quote($pdo, $input); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException('Accepted invalid quote: ' . $label);
}
$pdo->beginTransaction();
try {
    $code = 'TEST-' . strtoupper(bin2hex(random_bytes(5)));
    $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, status, start_date, end_date, usage_count, usage_limit, products) VALUES (?, 'percent', 50, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 0, 1, 'pro')");
    $stmt->execute([$code]);
    $base = ['pack_id' => 'pro', 'amount' => '12000'];
    quote_check(order_quote($pdo, $base)['amount'] === 12000, 'Base catalog price');
    rejects_quote($pdo, array_replace($base, ['amount' => '1']), 'Manipulated price');
    $discounted = array_replace($base, ['promo_code' => $code, 'amount' => '8999', 'delivery_time' => '1000', 'upsell_letter' => '1', 'upsellEbook2' => '1']);
    $q = order_quote($pdo, $discounted);
    quote_check($q['amount'] === 8999.0 && $q['discount'] === 6000.0 && $q['original'] === 14999 && $q['promo'] === $code, 'Discount only the base; preserve add-on charges');
    rejects_quote($pdo, array_replace($discounted, ['amount' => '6000']), 'Missing add-on charge');
    rejects_quote($pdo, array_replace($base, ['promo_code' => 'NOT-A-REAL-CODE']), 'Unknown promo');
    $restricted = order_quote($pdo, ['pack_id' => 'etudiant', 'amount' => '5000', 'promo_code' => $code]);
    quote_check($restricted['promo'] === null && $restricted['discount'] === 0, 'Product restriction');
    $pdo->prepare('UPDATE promo_codes SET usage_count = 1 WHERE code = ?')->execute([$code]);
    rejects_quote($pdo, $discounted, 'Exhausted promo');
    rejects_quote($pdo, array_replace($base, ['delivery_time' => '-1000']), 'Invalid delivery');
    rejects_quote($pdo, ['pack_id' => 'unknown', 'amount' => '1'], 'Unknown product');
    $sponsor = ['package_name' => 'Pack Pro', 'amount' => '6400', 'is_sponsorship' => '1', 'sponsorship_qty' => '2'];
    quote_check(order_quote($pdo, $sponsor)['qty'] === 2, 'Sponsorship unit pricing');
    rejects_quote($pdo, array_replace($sponsor, ['sponsorship_qty' => '100']), 'Inflated sponsorship credits');
    rejects_quote($pdo, ['package_name' => 'RAMADAN Pro', 'amount' => '3000'], 'Expired campaign');
    quote_check(order_quote($pdo, ['package_name' => 'CV Premium', 'amount' => '2500'])['amount'] === 2500, 'Premium catalog');
    rejects_quote($pdo, ['package_name' => 'CV Premium', 'amount' => '2500', 'upsell_letter' => '1'], 'Unpaid legacy add-on');
    echo "14 pricing, promo, delivery, legacy and sponsorship checks passed (fixtures rolled back).\n";
} finally { $pdo->rollBack(); }
