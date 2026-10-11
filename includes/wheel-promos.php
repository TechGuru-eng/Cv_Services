<?php
function wheel_promo_exclusion(PDO $pdo): string
{
    $ids = [];
    foreach ($pdo->query("SELECT slices FROM exit_popups WHERE popup_type = 'spin_wheel'")->fetchAll(PDO::FETCH_COLUMN) as $json) {
        foreach (json_decode($json ?? '[]', true) ?: [] as $slice) {
            if (is_array($slice) && (int)($slice['promo_id'] ?? 0) > 0) $ids[] = (int)$slice['promo_id'];
        }
    }
    return $ids ? ' AND id NOT IN (' . implode(',', array_unique($ids)) . ')' : '';
}

function wheel_promos(PDO $pdo): array
{
    $rows = $pdo->query("SELECT id, code, discount_value, products FROM promo_codes WHERE status = 'active' AND discount_type = 'percent' AND discount_value IN (10,20,30,40) AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit IS NULL OR usage_count < usage_limit) ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    return array_values(array_filter($rows, function ($row) {
        $products = array_unique(array_filter(array_map('trim', explode(',', $row['products'] ?? ''))));
        sort($products);
        return $products === ['etudiant', 'pro', 'senior'];
    }));
}

function wheel_rewards(array $slices, array $promos): array
{
    $rewards = [];
    foreach ([10, 20, 30, 40] as $percent) {
        $selection = null;
        foreach ($slices as $slice) {
            if (is_array($slice) && (int)($slice['value'] ?? 0) === $percent) $selection = $slice;
        }
        foreach ($promos as $promo) {
            if ($selection && (int)$promo['id'] === (int)($selection['promo_id'] ?? 0) && (int)$promo['discount_value'] === $percent) {
                $rewards[] = ['text' => "-$percent%", 'value' => $percent, 'code' => $promo['code'], 'color' => $percent % 20 === 0 ? '#fbbf24' : '#ef4444'];
                break;
            }
        }
    }
    return $rewards;
}
