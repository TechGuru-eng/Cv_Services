<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/order-pricing.php';
class WorkerPricingPDO extends PDO { public function __construct() {} }
$pdo = new WorkerPricingPDO();
$rates = [1 => 2500, 2 => 3000, 3 => 3500, 4 => 3500, 5 => 5000, 6 => 6000, 7 => 6500, 8 => 8000, 9 => 9000, 30 => 30000];
foreach ($rates as $years => $price) {
    $quote = order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => (string)$years, 'amount' => (string)$price]);
    if ($quote['amount'] !== $price || !str_contains($quote['name'], 'Word + PDF')) throw new RuntimeException('Incorrect worker quote');
}
foreach ([null, '0', '31', '1.5', 'invalid'] as $years) {
    try {
        order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => $years, 'amount' => '2500']);
    } catch (InvalidArgumentException $e) { continue; }
    throw new RuntimeException('Invalid experience accepted');
}
try {
    order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => '8', 'amount' => '2500']);
    throw new RuntimeException('Manipulated price accepted');
} catch (InvalidArgumentException $e) {}
$quote = order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => '8', 'amount' => '10000', 'delivery_time' => '1000', 'upsell_letter' => '1']);
if ($quote['amount'] !== 10000) throw new RuntimeException('Worker extras incorrect');
$quote = order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => '1', 'amount' => '2500', 'promo_code' => 'ANY-PROMO']);
if ($quote['amount'] !== 2500 || $quote['discount'] !== 0 || $quote['promo'] !== null) throw new RuntimeException('Worker promo exclusion incorrect');
try {
    order_quote($pdo, ['pack_id' => 'travailleur', 'experience_years' => '1', 'amount' => '1250', 'promo_code' => 'ANY-PROMO']);
    throw new RuntimeException('Discounted worker price accepted');
} catch (InvalidArgumentException $e) {}
echo "19 worker pricing checks passed.\n";
