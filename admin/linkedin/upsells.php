<?php
// admin/linkedin/upsells.php
// Admin Console for Upsell Catalog, Bundle Pricing & AOV Analytics

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$message = '';
$error = '';

// Handle Product Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $prodId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    $promoPrice = !empty($_POST['promo_price']) ? (float)$_POST['promo_price'] : null;
    $ctaText = trim($_POST['cta_text'] ?? 'Ajouter');
    $highlightBadge = trim($_POST['highlight_badge'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isDigital = isset($_POST['is_digital']) ? 1 : 0;

    $locationsArr = isset($_POST['locations']) ? (array)$_POST['locations'] : ['CHECKOUT'];
    $locationsStr = implode(',', $locationsArr);

    $objectivesArr = isset($_POST['objectives']) ? (array)$_POST['objectives'] : [];
    $objectivesJson = json_encode($objectivesArr);

    // Handle digital deliverable file upload if provided
    $digitalFilePath = null;
    if (isset($_FILES['digital_file']) && $_FILES['digital_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['digital_file'];
        $uploadDir = __DIR__ . '/../../uploads/linkedin/digital/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = 'DIG_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $slug) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
            $digitalFilePath = 'uploads/linkedin/digital/' . $safeName;
        }
    }

    if ($prodId > 0) {
        // Update
        $sql = "UPDATE linkedin_products SET name = ?, slug = ?, description = ?, price = ?, promo_price = ?, cta_text = ?, highlight_badge = ?, display_locations = ?, target_objectives_json = ?, is_active = ?, is_digital = ?";
        $params = [$name, $slug, $description, $price, $promoPrice, $ctaText, $highlightBadge, $locationsStr, $objectivesJson, $isActive, $isDigital];
        if ($digitalFilePath) {
            $sql .= ", digital_file_path = ?";
            $params[] = $digitalFilePath;
        }
        $sql .= " WHERE id = ?";
        $params[] = $prodId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $message = "Produit '$name' mis à jour.";
    } else {
        // Create
        if (empty($slug)) {
            $slug = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($name));
        }
        $stmt = $pdo->prepare("INSERT INTO linkedin_products (
            slug, name, description, price, promo_price, cta_text, highlight_badge, display_locations, target_objectives_json, is_active, is_digital, digital_file_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$slug, $name, $description, $price, $promoPrice, $ctaText, $highlightBadge, $locationsStr, $objectivesJson, $isActive, $isDigital, $digitalFilePath]);
        $message = "Nouveau produit '$name' créé avec succès.";
    }
}

// Update Bundle Price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_bundle') {
    $bundlePrice = (float)$_POST['bundle_price'];
    $stmtB = $pdo->prepare("UPDATE linkedin_bundles SET bundle_price = ? WHERE slug = 'pack-visibilite-pro'");
    $stmtB->execute([$bundlePrice]);
    $message = "Prix du Pack Visibilité Pro mis à jour.";
}

// Fetch All Products
$stmtProd = $pdo->query("SELECT * FROM linkedin_products ORDER BY sort_order ASC, id DESC");
$products = $stmtProd->fetchAll();

// Fetch Bundle
$bundle = $pdo->query("SELECT * FROM linkedin_bundles WHERE slug = 'pack-visibilite-pro'")->fetch();

// ANALYTICS CALCULATIONS
$paidOrdersCount = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE payment_status = 'CONFIRMED'")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT SUM(total_amount) FROM linkedin_orders WHERE payment_status = 'CONFIRMED'")->fetchColumn();
$aov = $paidOrdersCount > 0 ? ($totalRevenue / $paidOrdersCount) : 0.00;

// Upsell Revenue calculation from linkedin_order_items where item_type IN ('product', 'addon')
$upsellRevenue = (float)$pdo->query("SELECT SUM(oi.final_price_snapshot) 
                                     FROM linkedin_order_items oi 
                                     JOIN linkedin_orders o ON oi.order_id = o.id 
                                     WHERE o.payment_status = 'CONFIRMED' AND oi.item_type IN ('product', 'addon')")->fetchColumn();

$coreRevenue = $totalRevenue - $upsellRevenue;

// Per-product performance metrics
$stmtPerf = $pdo->query("SELECT p.id, p.name, p.price,
    (SELECT COUNT(*) FROM linkedin_upsell_analytics WHERE product_id = p.id AND event_type = 'impression') as impressions,
    (SELECT COUNT(*) FROM linkedin_upsell_analytics WHERE product_id = p.id AND event_type = 'add') as adds,
    (SELECT COUNT(*) FROM linkedin_upsell_analytics WHERE product_id = p.id AND event_type = 'purchase') as purchases,
    (SELECT SUM(oi.final_price_snapshot) FROM linkedin_order_items oi JOIN linkedin_orders o ON oi.order_id = o.id WHERE oi.item_id = p.id AND oi.item_type = 'product' AND o.payment_status = 'CONFIRMED') as revenue
    FROM linkedin_products p ORDER BY p.sort_order ASC");
$performance = $stmtPerf->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Upsells & AOV — Admin HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/linkedin-admin.css?v=20261010">
    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen">

    <div class="flex">
        <!-- Sidebar -->
        <?php require __DIR__ . '/navigation.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:p-10 space-y-8 overflow-x-hidden">
            
            <?php if ($message): ?>
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 text-xs font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="border-b border-slate-200 pb-6">
                <h1 class="text-2xl font-black text-slate-900">Gestion des Upsells & Analytics AOV</h1>
                <p class="text-xs text-slate-500">Configurez le catalogue de vente croisée, gérez le Pack Visibilité Pro et suivez le panier moyen (AOV).</p>
            </div>

            <!-- Analytics Overview Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <span class="text-xs text-slate-500 block">Panier Moyen (AOV)</span>
                    <strong class="text-2xl font-black text-amber-700 block"><?= number_format($aov, 0, ',', ' ') ?> FCFA</strong>
                    <span class="text-[11px] text-gray-500 block">Sur <?= $paidOrdersCount ?> commande(s) payée(s)</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <span class="text-xs text-slate-500 block">Revenus Générés par Upsells</span>
                    <strong class="text-2xl font-black text-emerald-700 block"><?= number_format($upsellRevenue, 0, ',', ' ') ?> FCFA</strong>
                    <span class="text-[11px] text-gray-500 block">Ventes additionnelles</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <span class="text-xs text-slate-500 block">Revenus Forfaits LinkedIn</span>
                    <strong class="text-2xl font-black text-blue-700 block"><?= number_format($coreRevenue, 0, ',', ' ') ?> FCFA</strong>
                    <span class="text-[11px] text-gray-500 block">Services principaux</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <span class="text-xs text-slate-500 block">Revenu Total Brut</span>
                    <strong class="text-2xl font-black text-slate-900 block"><?= number_format($totalRevenue, 0, ',', ' ') ?> FCFA</strong>
                    <span class="text-[11px] text-gray-500 block">LinkedIn + Upsells</span>
                </div>

            </div>

            <!-- Bundle Configurator -->
            <?php if ($bundle): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xl">
                <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-box-open"></i> Configuration du Pack Visibilité Pro (Bundle Hero)
                </h2>

                <form action="upsells.php" method="POST" class="flex flex-col sm:flex-row items-end gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200"><?php csrf_field(); ?>
                    <input type="hidden" name="action" value="save_bundle">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Prix du Bundle (FCFA)</label>
                        <input type="number" name="bundle_price" value="<?= $bundle['bundle_price'] ?>" required class="w-full bg-white border border-slate-200 text-xs font-bold text-amber-700 px-4 py-2.5 rounded-xl focus:outline-none focus:border-yellow-400">
                    </div>
                    <button type="submit" class="bg-yellow-400 text-gray-950 font-black text-xs px-6 py-3 rounded-xl shadow">
                        Mettre à jour le prix du Bundle
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Performance Matrix Table -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xl">
                <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-chart-column"></i> Performance des Produits d'Upsell
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="p-3">Produit</th>
                                <th class="p-3">Prix</th>
                                <th class="p-3">Vues / Impressions</th>
                                <th class="p-3">Ajouts Panier</th>
                                <th class="p-3">Achats Confirmés</th>
                                <th class="p-3">Taux de Conv.</th>
                                <th class="p-3 text-right">Revenu Généré</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/80">
                            <?php foreach ($performance as $perf): 
                                $adds = (int)$perf['adds'];
                                $purchases = (int)$perf['purchases'];
                                $conv = $adds > 0 ? number_format(($purchases / $adds) * 100, 1) . '%' : '0.0%';
                                $rev = (float)($perf['revenue'] ?: 0);
                            ?>
                            <tr class="hover:bg-gray-800/50">
                                <td class="p-3 font-bold text-slate-900"><?= htmlspecialchars($perf['name']) ?></td>
                                <td class="p-3 font-bold text-amber-700"><?= number_format($perf['price'], 0, ',', ' ') ?> FCFA</td>
                                <td class="p-3"><?= (int)$perf['impressions'] ?></td>
                                <td class="p-3"><?= $adds ?></td>
                                <td class="p-3 font-bold text-emerald-700"><?= $purchases ?></td>
                                <td class="p-3"><?= $conv ?></td>
                                <td class="p-3 text-right font-bold text-slate-900"><?= number_format($rev, 0, ',', ' ') ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Product Catalog Manager -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-6 shadow-xl">
                <h2 class="text-sm font-bold text-amber-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-cube"></i> Catalogue des Produits d'Upsell (<?= count($products) ?>)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($products as $prod): 
                        $locs = explode(',', $prod['display_locations']);
                    ?>
                    <form action="upsells.php" method="POST" enctype="multipart/form-data" class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4"><?php csrf_field(); ?>
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">

                        <div class="flex justify-between items-start">
                            <div>
                                <input type="text" name="name" value="<?= htmlspecialchars($prod['name']) ?>" required class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs text-slate-900 font-bold focus:border-yellow-400">
                                <input type="text" name="slug" value="<?= htmlspecialchars($prod['slug']) ?>" required class="w-full bg-white border border-slate-200 rounded-xl px-3 py-1 text-[11px] text-slate-500 mt-1">
                            </div>
                            <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                                <input type="checkbox" name="is_active" value="1" <?= $prod['is_active'] ? 'checked' : '' ?> class="accent-yellow-400"> Actif
                            </label>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Description courte (1 phrase benefit)</label>
                            <textarea name="description" rows="2" class="w-full bg-white border border-slate-200 rounded-xl p-2.5 text-xs text-slate-700 focus:border-yellow-400"><?= htmlspecialchars($prod['description']) ?></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Prix (FCFA)</label>
                                <input type="number" name="price" value="<?= $prod['price'] ?>" required class="w-full bg-white border border-slate-200 rounded-xl p-2 text-xs font-bold text-amber-700">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Texte CTA</label>
                                <input type="text" name="cta_text" value="<?= htmlspecialchars($prod['cta_text']) ?>" class="w-full bg-white border border-slate-200 rounded-xl p-2 text-xs text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Badge</label>
                                <input type="text" name="highlight_badge" value="<?= htmlspecialchars($prod['highlight_badge'] ?? '') ?>" placeholder="ex: Idéal" class="w-full bg-white border border-slate-200 rounded-xl p-2 text-xs text-amber-700">
                            </div>
                        </div>

                        <div>
                            <span class="block text-[11px] font-bold text-slate-500 mb-1.5">Emplacements d'affichage :</span>
                            <div class="flex flex-wrap gap-3 text-[11px] text-slate-700">
                                <label><input type="checkbox" name="locations[]" value="CHECKOUT" <?= in_array('CHECKOUT', $locs) ? 'checked' : '' ?> class="accent-yellow-400"> CHECKOUT</label>
                                <label><input type="checkbox" name="locations[]" value="ORDER_BUMP" <?= in_array('ORDER_BUMP', $locs) ? 'checked' : '' ?> class="accent-yellow-400"> ORDER_BUMP</label>
                                <label><input type="checkbox" name="locations[]" value="POST_PURCHASE" <?= in_array('POST_PURCHASE', $locs) ? 'checked' : '' ?> class="accent-yellow-400"> POST_PURCHASE</label>
                                <label><input type="checkbox" name="locations[]" value="DASHBOARD" <?= in_array('DASHBOARD', $locs) ? 'checked' : '' ?> class="accent-yellow-400"> DASHBOARD</label>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-900 flex justify-between items-center">
                            <label class="flex items-center gap-1.5 text-xs text-slate-700">
                                <input type="checkbox" name="is_digital" value="1" <?= $prod['is_digital'] ? 'checked' : '' ?> class="accent-yellow-400"> Produit Digital (Ebook/PDF)
                            </label>
                            <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black text-xs px-4 py-2 rounded-xl">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
