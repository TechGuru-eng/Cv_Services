<?php
// api/linkedin/get_packages.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../admin/config/db.php';

try {
    // 1. Fetch active packages
    $stmtPkg = $pdo->query("SELECT id, slug, name, tagline, price, is_popular, deliverables_json, sort_order FROM linkedin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
    $packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);

    foreach ($packages as &$pkg) {
        $pkg['deliverables'] = json_decode($pkg['deliverables_json'], true) ?: [];
        unset($pkg['deliverables_json']);
        $pkg['price'] = (float)$pkg['price'];
        $pkg['is_popular'] = (bool)$pkg['is_popular'];
    }

    // 2. Fetch active add-ons
    $stmtAdd = $pdo->query("SELECT id, slug, name, description, price, sort_order FROM linkedin_addons WHERE is_active = 1 ORDER BY sort_order ASC");
    $addons = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);

    foreach ($addons as &$add) {
        $add['price'] = (float)$add['price'];
    }

    // 3. Fetch active upsell products
    $stmtProd = $pdo->query("SELECT id, slug, name, image_url, description, price, promo_price, is_digital, display_locations, target_objectives_json, cta_text, highlight_badge, sort_order FROM linkedin_products WHERE is_active = 1 ORDER BY sort_order ASC");
    $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as &$prod) {
        $prod['price'] = (float)$prod['price'];
        $prod['promo_price'] = $prod['promo_price'] !== null ? (float)$prod['promo_price'] : null;
        $prod['target_objectives'] = json_decode($prod['target_objectives_json'], true) ?: [];
        $prod['display_locations'] = explode(',', $prod['display_locations']);
        unset($prod['target_objectives_json']);
    }

    // 4. Fetch active bundles
    $stmtBundle = $pdo->query("SELECT b.id, b.slug, b.name, b.description, b.bundle_price 
                              FROM linkedin_bundles b WHERE b.is_active = 1");
    $bundles = $stmtBundle->fetchAll(PDO::FETCH_ASSOC);

    foreach ($bundles as &$b) {
        $b['bundle_price'] = (float)$b['bundle_price'];

        // Get bundle items
        $stmtItems = $pdo->prepare("SELECT bi.product_id, bi.is_linkedin_package, p.name as product_name, p.price as product_price 
                                    FROM linkedin_bundle_items bi 
                                    LEFT JOIN linkedin_products p ON bi.product_id = p.id 
                                    WHERE bi.bundle_id = ?");
        $stmtItems->execute([$b['id']]);
        $b['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Fetch payment settings
    $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM linkedin_settings");
    $rawSettings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        'packages' => $packages,
        'addons' => $addons,
        'products' => $products,
        'bundles' => $bundles,
        'settings' => [
            'mtn_phone' => $rawSettings['mtn_phone'] ?? '670000000',
            'mtn_name' => $rawSettings['mtn_name'] ?? 'HireMe Cameroon',
            'mtn_ussd' => $rawSettings['mtn_ussd'] ?? '*126#',
            'orange_phone' => $rawSettings['orange_phone'] ?? '690000000',
            'orange_name' => $rawSettings['orange_name'] ?? 'HireMe Cameroon',
            'orange_ussd' => $rawSettings['orange_ussd'] ?? '*150#',
            'moncvpro_url' => $rawSettings['moncvpro_url'] ?? 'cv-builder.php'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
