<?php
// admin/linkedin/packages.php
// Package and Addon Price & Configuration Manager

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$message = '';
$error = '';

// Update Package
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_package') {
    $pkgId = (int)$_POST['package_id'];
    $name = trim($_POST['name']);
    $tagline = trim($_POST['tagline']);
    $price = (float)$_POST['price'];
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;
    $deliverablesText = trim($_POST['deliverables_text']);

    // Convert deliverables lines to JSON array
    $lines = array_filter(array_map('trim', explode("\n", $deliverablesText)));
    $jsonDeliverables = json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("UPDATE linkedin_packages SET name = ?, tagline = ?, price = ?, is_popular = ?, deliverables_json = ? WHERE id = ?");
    $stmt->execute([$name, $tagline, $price, $isPopular, $jsonDeliverables, $pkgId]);
    $message = "Forfait '$name' mis à jour avec succès.";
}

// Update Addon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_addon') {
    $addonId = (int)$_POST['addon_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE linkedin_addons SET name = ?, description = ?, price = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$name, $description, $price, $isActive, $addonId]);
    $message = "Option '$name' mise à jour.";
}

// Fetch Packages
$stmtPkg = $pdo->query("SELECT * FROM linkedin_packages ORDER BY sort_order ASC");
$packages = $stmtPkg->fetchAll();

// Fetch Addons
$stmtAdd = $pdo->query("SELECT * FROM linkedin_addons ORDER BY sort_order ASC");
$addons = $stmtAdd->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tarifs & Options — LinkedIn Admin | HireMe</title>
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
                <h1 class="text-2xl font-black text-slate-900">Configuration des Forfaits & Options</h1>
                <p class="text-xs text-slate-500">Modifiez les tarifs, descriptions et fonctionnalités applicables en temps réel sur le site public.</p>
            </div>

            <!-- Packages Section -->
            <div class="space-y-6">
                <h2 class="text-lg font-extrabold text-amber-700 flex items-center gap-2">
                    <i class="fa-solid fa-cubes"></i> Forfaits Principaux (3 Formules)
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <?php foreach ($packages as $pkg): 
                        $delivList = json_decode($pkg['deliverables_json'], true) ?: [];
                        $delivText = implode("\n", $delivList);
                    ?>
                    <form action="packages.php" method="POST" class="bg-white border border-slate-200 p-6 rounded-2xl space-y-4 shadow-xl"><?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_package">
                        <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nom du forfait</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($pkg['name']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-yellow-400 font-bold">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pari / Tagline</label>
                            <input type="text" name="tagline" value="<?= htmlspecialchars($pkg['tagline']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-700 focus:outline-none focus:border-yellow-400">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Prix (FCFA)</label>
                            <input type="number" step="500" name="price" value="<?= $pkg['price'] ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-amber-700 font-black focus:outline-none focus:border-yellow-400">
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_popular" value="1" id="pop_<?= $pkg['id'] ?>" <?= $pkg['is_popular'] ? 'checked' : '' ?> class="accent-yellow-400">
                            <label for="pop_<?= $pkg['id'] ?>" class="text-xs font-bold text-slate-700 cursor-pointer">Badge "Le plus choisi"</label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Livrables (Un par ligne)</label>
                            <textarea name="deliverables_text" rows="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-700 focus:outline-none focus:border-yellow-400"><?= htmlspecialchars($delivText) ?></textarea>
                        </div>

                        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black text-xs py-3 rounded-xl shadow">
                            Enregistrer le Forfait
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Addons Section -->
            <div class="space-y-6 pt-6 border-t border-slate-200">
                <h2 class="text-lg font-extrabold text-amber-700 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle"></i> Services Optionnels (Add-ons)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($addons as $ad): ?>
                    <form action="packages.php" method="POST" class="bg-white border border-slate-200 p-5 rounded-2xl space-y-3 shadow-xl"><?php csrf_field(); ?>
                        <input type="hidden" name="action" value="update_addon">
                        <input type="hidden" name="addon_id" value="<?= $ad['id'] ?>">

                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Nom de l'option</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($ad['name']) ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-yellow-400 font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Prix (FCFA)</label>
                                <input type="number" step="500" name="price" value="<?= $ad['price'] ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-amber-700 font-bold focus:outline-none focus:border-yellow-400">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Description courte</label>
                            <input type="text" name="description" value="<?= htmlspecialchars($ad['description']) ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:outline-none focus:border-yellow-400">
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" <?= $ad['is_active'] ? 'checked' : '' ?> class="accent-yellow-400">
                                <span>Actif sur le site</span>
                            </label>
                            <button type="submit" class="bg-slate-100 hover:bg-gray-700 text-amber-700 font-bold text-xs px-4 py-2 rounded-xl border border-slate-300">
                                Mettre à jour
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
