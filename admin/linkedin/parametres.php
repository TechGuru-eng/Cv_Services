<?php
// admin/linkedin/parametres.php
// Settings and Testimonials Approval Manager

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/csrf.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') verify_csrf();

$message = '';
$error = '';

// Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings = [
        'mtn_phone' => trim($_POST['mtn_phone'] ?? ''),
        'mtn_name' => trim($_POST['mtn_name'] ?? ''),
        'mtn_ussd' => trim($_POST['mtn_ussd'] ?? ''),
        'orange_phone' => trim($_POST['orange_phone'] ?? ''),
        'orange_name' => trim($_POST['orange_name'] ?? ''),
        'orange_ussd' => trim($_POST['orange_ussd'] ?? ''),
        'whatsapp_support' => trim($_POST['whatsapp_support'] ?? ''),
        'moncvpro_url' => trim($_POST['moncvpro_url'] ?? '')
    ];

    $stmtSet = $pdo->prepare("INSERT INTO linkedin_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $k => $v) {
        $stmtSet->execute([$k, $v]);
    }
    $message = "Paramètres mis à jour avec succès.";
}

// Add New Testimonial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_testimonial') {
    $name = trim($_POST['test_name']);
    $profession = trim($_POST['test_profession']);
    $comment = trim($_POST['test_comment']);
    $rating = (int)$_POST['test_rating'];

    if ($name && $comment) {
        $stmtT = $pdo->prepare("INSERT INTO linkedin_testimonials (name, profession, comment, rating, is_verified, is_approved) VALUES (?, ?, ?, ?, 1, 1)");
        $stmtT->execute([$name, $profession, $comment, $rating]);
        $message = "Témoignage ajouté et approuvé.";
    }
}

// Delete Testimonial
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test'])) {
    $tId = (int)$_POST['delete_test'];
    $stmtDel = $pdo->prepare("DELETE FROM linkedin_testimonials WHERE id = ?");
    $stmtDel->execute([$tId]);
    $message = "Témoignage supprimé.";
}

// Fetch Settings
$stmtSet = $pdo->query("SELECT setting_key, setting_value FROM linkedin_settings");
$settings = $stmtSet->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Testimonials
$stmtTest = $pdo->query("SELECT * FROM linkedin_testimonials ORDER BY id DESC");
$testimonials = $stmtTest->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres — LinkedIn Admin | HireMe</title>
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
                <h1 class="text-2xl font-black text-slate-900">Paramètres Globaux & Témoignages</h1>
                <p class="text-xs text-slate-500">Configurez les comptes de paiement Mobile Money et gérez la preuve sociale.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Payment Settings -->
                <form action="parametres.php" method="POST" class="bg-white border border-slate-200 p-6 rounded-2xl space-y-5 shadow-xl"><?php csrf_field(); ?>
                    <input type="hidden" name="action" value="save_settings">
                    
                    <h2 class="text-lg font-bold text-amber-700 flex items-center gap-2">
                        <i class="fa-solid fa-mobile-screen-button"></i> Paramètres Mobile Money (MTN & Orange)
                    </h2>

                    <!-- MTN -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <span class="text-xs font-bold text-amber-700 block">MTN Mobile Money Cameroun</span>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Numéro Marchand</label>
                                <input type="text" name="mtn_phone" value="<?= htmlspecialchars($settings['mtn_phone'] ?? '670000000') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900 font-bold">
                            </div>
                            <div>
                                <label for="mtn-merchant-name" class="block text-[11px] text-slate-500 mb-1">Nom du bénéficiaire</label>
                                <input id="mtn-merchant-name" type="text" name="mtn_name" value="<?= htmlspecialchars($settings['mtn_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Code USSD</label>
                                <input type="text" name="mtn_ussd" value="<?= htmlspecialchars($settings['mtn_ussd'] ?? '*126#') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900 font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Orange -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <span class="text-xs font-bold text-orange-400 block">Orange Money Cameroun</span>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Numéro Marchand</label>
                                <input type="text" name="orange_phone" value="<?= htmlspecialchars($settings['orange_phone'] ?? '690000000') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900 font-bold">
                            </div>
                            <div>
                                <label for="orange-merchant-name" class="block text-[11px] text-slate-500 mb-1">Nom du bénéficiaire</label>
                                <input id="orange-merchant-name" type="text" name="orange_name" value="<?= htmlspecialchars($settings['orange_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-500 mb-1">Code USSD</label>
                                <input type="text" name="orange_ussd" value="<?= htmlspecialchars($settings['orange_ussd'] ?? '*150#') ?>" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-900 font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Cross-sell & WhatsApp -->
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block text-[11px] text-slate-500 mb-1">Lien MonCVPro Cross-Sell</label>
                            <input type="text" name="moncvpro_url" value="<?= htmlspecialchars($settings['moncvpro_url'] ?? 'cv-builder.php') ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 font-bold">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black text-xs py-3 rounded-xl shadow">
                        Enregistrer les Paramètres
                    </button>
                </form>

                <!-- Testimonials Manager -->
                <div class="bg-white border border-slate-200 p-6 rounded-2xl space-y-6 shadow-xl">
                    <h2 class="text-lg font-bold text-amber-700 flex items-center gap-2">
                        <i class="fa-solid fa-comments"></i> Témoignages Clients
                    </h2>

                    <!-- Add Form -->
                    <form action="parametres.php" method="POST" class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs"><?php csrf_field(); ?>
                        <input type="hidden" name="action" value="add_testimonial">
                        <span class="font-bold text-slate-900 block">Ajouter un Témoignage Vérifié :</span>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" name="test_name" placeholder="Nom complet (ex: Carine M.)" required class="bg-white border border-slate-200 rounded-lg p-2.5 text-slate-900">
                            <input type="text" name="test_profession" placeholder="Profession (ex: Chef RH)" required class="bg-white border border-slate-200 rounded-lg p-2.5 text-slate-900">
                        </div>

                        <textarea name="test_comment" rows="2" placeholder="Avis client..." required class="w-full bg-white border border-slate-200 rounded-lg p-2.5 text-slate-900"></textarea>

                        <div class="flex justify-between items-center">
                            <select name="test_rating" class="bg-white border border-slate-200 rounded-lg p-2 text-slate-900">
                                <option value="5">5 Étoiles (Superb)</option>
                                <option value="4">4 Étoiles</option>
                            </select>
                            <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-bold px-4 py-2 rounded-lg">
                                Ajouter Témoignage
                            </button>
                        </div>
                    </form>

                    <!-- Testimonials List -->
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                        <?php foreach ($testimonials as $t): ?>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between items-start text-xs">
                            <div class="space-y-1">
                                <strong class="text-slate-900 block"><?= htmlspecialchars($t['name']) ?> <span class="text-gray-500 font-normal">-- <?= htmlspecialchars($t['profession']) ?></span></strong>
                                <p class="text-slate-700 italic">"<?= htmlspecialchars($t['comment']) ?>"</p>
                            </div>
                            <form action="parametres.php" method="POST" onsubmit="return confirm('Supprimer ce témoignage ?')"><?php csrf_field(); ?><input type="hidden" name="delete_test" value="<?= (int)$t['id'] ?>"><button type="submit" aria-label="Supprimer le témoignage" class="text-red-400 hover:text-red-300 font-bold p-1">
                                <i class="fa-solid fa-trash"></i>
                            </button></form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

        </main>
    </div>

</body>
</html>

