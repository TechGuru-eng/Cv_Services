<?php
// Authoritative XAF catalog. Browser amounts are used only to detect stale/tampered quotes.
function order_quote(PDO $pdo, array $input): array
{
    foreach ($input as $value) {
        if (is_array($value) || is_object($value)) throw new InvalidArgumentException('Données de commande invalides.');
    }
    $catalog = [
        'etudiant' => ['Pack Étudiant', 5000], 'pro' => ['Pack Pro', 12000],
        'senior' => ['Pack Senior', 30000], 'expert' => ['Pack Expert', 90000],
        'templates40' => ['Pack 40 Modèles CV', 3999],
        'travailleur' => ['Pack Travailleur', 2500],
    ];
    $name = trim((string)($input['package_name'] ?? ''));
    $id = (string)($input['pack_id'] ?? '');
    $sponsor = ($input['is_sponsorship'] ?? '') === '1';
    $qty = 0;
    if ($id === '') {
        foreach ($catalog as $key => $product) {
            if ($name === $product[0]) { $id = $key; break; }
        }
    }
    if ($sponsor) {
        $rates = ['etudiant' => 1500, 'pro' => 3200, 'senior' => 9000, 'expert' => 27000];
        $qty = filter_var($input['sponsorship_qty'] ?? null, FILTER_VALIDATE_INT);
        if (!isset($rates[$id]) || !$qty || $qty < 1 || $qty > 1000) {
            throw new InvalidArgumentException('Pack ou quantité de parrainage invalide.');
        }
        $base = $rates[$id] * $qty;
        $name = $catalog[$id][0];
    } elseif ($id === 'travailleur') {
        $years = filter_var($input['experience_years'] ?? null, FILTER_VALIDATE_INT);
        if ($years === false || $years < 1 || $years > 30) {
            throw new InvalidArgumentException('Choisissez entre 1 et 30 années d’expérience.');
        }
        $rates = [1 => 2500, 2 => 3000, 3 => 3500, 4 => 3500, 5 => 5000, 6 => 6000, 7 => 6500, 8 => 8000];
        $base = $rates[$years] ?? (8000 + ($years - 8) * 1000);
        $name = 'Pack Travailleur — ' . $years . ($years === 1 ? ' an' : ' ans') . ' d’expérience — Word + PDF';
    } elseif (isset($catalog[$id])) {
        [$name, $base] = $catalog[$id];
    } elseif ($id !== '') {
        throw new InvalidArgumentException('Pack inconnu.');
    } else {
        $fixed = ['Pack 300 Lettres' => 1300, 'CV Premium' => 2500, 'Pack Ultime' => 3000,
            'Lettre de Motivation' => 700, 'Pack Candidature Camtel' => 6000];
        if (isset($fixed[$name])) {
            $base = $fixed[$name];
        } elseif (str_starts_with($name, 'Pack Neptune Oil Expert - ')) {
            $base = 5000;
        } elseif (preg_match('/^(RAMADAN|8MARS|20MAI|VALENTIN) /', $name)) {
            // These dated campaigns have ended; direct POSTs must not revive their prices.
            throw new InvalidArgumentException('Cette offre est terminée. Choisissez une offre actuelle.');
        } elseif (str_ends_with($name, ' (FLASH)')) {
            $settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('flash_status', 'flash_end_time', 'flash_access_token')")->fetchAll(PDO::FETCH_KEY_PAIR);
            if (($settings['flash_status'] ?? '') !== 'OPEN' ||
                (!empty($settings['flash_end_time']) && time() > strtotime($settings['flash_end_time'])) ||
                (!empty($settings['flash_access_token']) && !hash_equals($settings['flash_access_token'], (string)($input['flash_access'] ?? '')))) {
                throw new InvalidArgumentException('La vente flash est fermée.');
            }
            $stmt = $pdo->prepare('SELECT min_price FROM flash_products WHERE name = ? AND is_active = 1 AND sold_count < max_stock LIMIT 1');
            $stmt->execute([substr($name, 0, -8)]);
            $minimum = $stmt->fetchColumn();
            $base = filter_var($input['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($minimum === false || $base === false || !is_finite($base) || $base < (float)$minimum || $base > 10000000) {
                throw new InvalidArgumentException('Montant de vente flash invalide.');
            }
        } else {
            throw new InvalidArgumentException('Pack inconnu. Choisissez une offre actuelle.');
        }
    }
    $extras = 0;
    if (!$sponsor && isset($catalog[$id])) {
        $delivery = (string)($input['delivery_time'] ?? '0');
        if (!in_array($delivery, ['0', '1000', '2000'], true)) {
            throw new InvalidArgumentException('Délai de livraison invalide.');
        }
        $extras += (int)$delivery;
        $prices = ['upsell_letter' => 1000, 'upsellEbook1' => 5000, 'upsellEbook2' => 999,
            'upsellEbook3' => 1500, 'upsellEbook4' => 650, 'upsellEbook5' => 2000,
            'upsellEbook6' => 600, 'upsellEbook7' => 1000];
        foreach ($prices as $key => $price) {
            if (!in_array((string)($input[$key] ?? '0'), ['0', '1'], true)) {
                throw new InvalidArgumentException('Option de commande invalide.');
            }
            if (($input[$key] ?? '') === '1') $extras += $price;
        }
    } else {
        foreach (['delivery_time', 'upsell_letter', 'upsellEbook1', 'upsellEbook2', 'upsellEbook3', 'upsellEbook4', 'upsellEbook5', 'upsellEbook6', 'upsellEbook7'] as $key) {
            if (!in_array((string)($input[$key] ?? '0'), ['0', ''], true)) {
                throw new InvalidArgumentException('Cette offre ne permet pas ces options.');
            }
        }
    }
    $discount = 0;
    $code = strtoupper(trim((string)($input['promo_code'] ?? '')));
    if ($code !== '' && !$sponsor && $id !== 'travailleur' && isset($catalog[$id])) {
        $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit IS NULL OR usage_count < usage_limit) FOR UPDATE");
        $stmt->execute([$code]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$promo) throw new InvalidArgumentException('Code promo invalide, expiré ou épuisé.');
        $products = array_filter(array_map('trim', explode(',', $promo['products'] ?? '')));
        if (!$products || in_array($id, $products, true)) {
            $value = (float)$promo['discount_value'];
            if ($value < 0 || !is_finite($value) || !in_array($promo['discount_type'], ['percent', 'fixed'], true)) {
                throw new InvalidArgumentException('Configuration du code promo invalide.');
            }
            $discount = $promo['discount_type'] === 'percent' ? $base - round($base * (1 - min($value, 100) / 100)) : min($base, $value);
        } else {
            $code = '';
        }
    } else {
        $code = '';
    }
    $original = $base + $extras;
    $total = $original - $discount;
    $submitted = filter_var($input['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($submitted === false || !is_finite($submitted) || abs($submitted - $total) > 0.001 || $total <= 0) {
        throw new InvalidArgumentException('Le montant ne correspond pas au pack et aux options. Revenez à la page de commande pour actualiser le prix.');
    }
    return ['name' => $name, 'amount' => $total, 'original' => $original,
        'discount' => $discount, 'promo' => $code ?: null, 'qty' => $qty];
}
