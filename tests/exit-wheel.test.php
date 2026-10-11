<?php
require __DIR__ . '/../admin/config/db.php';
require __DIR__ . '/../includes/wheel-promos.php';
require __DIR__ . '/../includes/order-pricing.php';
$pdo->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $insert = $pdo->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, start_date, end_date, products, usage_limit, status) VALUES (?, 'percent', ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), ?, 1, 'active')");
    $slices = [];
    foreach ([10,20,30,40] as $percent) {
        $insert->execute(["WT{$suffix}{$percent}", $percent, 'senior,etudiant,pro']);
        $slices[] = ['value' => $percent, 'promo_id' => (int)$pdo->lastInsertId()];
    }
    $rewards = wheel_rewards($slices, wheel_promos($pdo));
    if (count($rewards) !== 4 || array_column($rewards, 'value') !== [10,20,30,40]) throw new Exception('Mapping failed');
    foreach ($rewards as $reward) {
        foreach (['etudiant' => 5000, 'pro' => 12000, 'senior' => 30000, 'expert' => 90000] as $pack => $price) {
            $amount = $pack === 'expert' ? $price : $price * (1 - $reward['value'] / 100);
            $quote = order_quote($pdo, ['pack_id' => $pack, 'amount' => (string)$amount, 'promo_code' => $reward['code']]);
            if ($quote['amount'] != $amount || ($pack === 'expert' && $quote['discount'] != 0)) throw new Exception('Checkout reward mismatch');
        }
    }
    $id = $slices[0]['promo_id'];
    foreach (["products = 'etudiant,pro,senior,expert'", "usage_count = 1", "status = 'inactive'", "end_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"] as $invalid) {
        $pdo->exec("UPDATE promo_codes SET $invalid WHERE id = $id");
        if (count(wheel_rewards($slices, wheel_promos($pdo))) !== 3) throw new Exception('Invalid reward accepted: ' . $invalid);
        $pdo->exec("UPDATE promo_codes SET products = 'etudiant,pro,senior', usage_count = 0, status = 'active', end_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) WHERE id = $id");
    }
    $play = $pdo->prepare('INSERT INTO exit_wheel_plays (browser_hash, ip_hash, reward_percent) VALUES (?, ?, 0)');
    $browser = hash('sha256', 'browser' . $suffix); $ip = hash('sha256', 'ip' . $suffix);
    $play->execute([$browser,$ip]);
    foreach ([[$browser,hash('sha256', 'other-ip'.$suffix)], [hash('sha256','other-browser'.$suffix),$ip]] as $duplicate) {
        try { $play->execute($duplicate); throw new Exception('Repeat play accepted'); }
        catch (PDOException $e) { if ($e->getCode() !== '23000') throw $e; }
    }
    echo "PASS: reward mapping, pack restrictions, expiry, status, usage limits, unique browser and IP.\n";
} finally {
    $pdo->rollBack();
}
