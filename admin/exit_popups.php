<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/csrf.php';
require_once '../includes/wheel-promos.php';
$wheelPromos = wheel_promos($pdo);

$message = '';
$messageType = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = $_POST['id'];
    $title = $_POST['title'];
    $popup_message = $_POST['message'];
    $discount_code = $_POST['discount_code'];
    $discount_percent = $_POST['discount_percent'];
    $cta_text = $_POST['cta_text'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $popup_type = $_POST['popup_type'] ?? 'spin_wheel';
    $selections = [];
    foreach ([10, 20, 30, 40] as $percent) {
        $selections[] = ['value' => $percent, 'promo_id' => (int)($_POST['wheel_codes'][$percent] ?? 0)];
    }
    $slices = json_encode($selections);
    if ($popup_type === 'spin_wheel' && $is_active && count(wheel_rewards($selections, $wheelPromos)) !== 4) {
        $message = 'Choisissez un code actif pour chaque réduction (10%, 20%, 30%, 40%), limité aux packs Étudiant, Pro et Senior.';
        $messageType = 'error';
    } else {
    $pdo->beginTransaction();

    // Reset all others to inactive if this one is active (Single Active Logic)
    if ($is_active) {
        $pdo->query("UPDATE exit_popups SET is_active = 0");
    }

    $stmt = $pdo->prepare("UPDATE exit_popups SET title=?, message=?, discount_code=?, discount_percent=?, cta_text=?, is_active=?, popup_type=?, slices=? WHERE id=?");
    if ($stmt->execute([$title, $popup_message, $discount_code, $discount_percent, $cta_text, $is_active, $popup_type, $slices, $id])) {
        $pdo->commit();
        $message = "Configuration mise à jour !";
        $messageType = "success";
    } else {
        $pdo->rollBack();
        $message = "Erreur lors de la mise à jour.";
        $messageType = "error";
    }
}

}

// Fetch active or last created popup configuration (We will edit the first available one for simplicity in this version)
// Fetch active or last created popup configuration
try {
    $stmt = $pdo->query("SELECT * FROM exit_popups ORDER BY id DESC LIMIT 1");
    $popup = $stmt->fetch();
} catch (PDOException $e) {
    // Table likely missing, attempt to create it on the fly
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `exit_popups` (
          `id` int NOT NULL AUTO_INCREMENT,
          `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
          `discount_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `discount_percent` int NOT NULL,
          `cta_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'PROFITER DE L''OFFRE',
          `is_active` tinyint(1) DEFAULT '0',
          `popup_type` varchar(50) DEFAULT 'spin_wheel',
          `slices` text NULL,
          `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        
        // Seed
        $pdo->exec("INSERT INTO exit_popups (title, message, discount_code, discount_percent, cta_text, is_active) VALUES ('ATTENDEZ !', 'Bénéficiez d''une réduction flash.', 'MonTour', 50, 'PROFITER', 0)");
        
        // Refresh to load it
        header("Refresh:0");
        exit;
    } catch (Exception $ex) {
         die("Erreur critique de base de données : " . $ex->getMessage());
    }
}

if (!$popup) {
    // Should have been seeded, but just in case
    $pdo->exec("INSERT INTO exit_popups (title, message, discount_code, discount_percent, cta_text, is_active) VALUES ('Test', 'Test Msg', 'CODE', 10, 'Click', 0)");
    header("Refresh:0");
    exit;
}
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Configuration Popup de Sortie (Exit Intent)</h1>

            <?php if ($message): ?>
                <div class="p-4 mb-4 text-sm text-white rounded-lg <?= $messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?>" role="alert">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Configure Form -->
                <div class="bg-white shadow-md rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 text-purple-600">Modifier le Popup Actuel</h2>
                    <form action="exit_popups.php" method="POST">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="id" value="<?= $popup['id'] ?>">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Titre (Accroche)</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($popup['title']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Message Principal</label>
                            <textarea name="message" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline h-24"><?= htmlspecialchars($popup['message']) ?></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Code Promo (mode classique)</label>
                                <input type="text" name="discount_code" value="<?= htmlspecialchars($popup['discount_code']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline font-mono text-center bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">% Réduction (mode classique)</label>
                                <input type="number" name="discount_percent" value="<?= htmlspecialchars($popup['discount_percent']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-center">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Texte Bouton Action</label>
                            <input type="text" name="cta_text" value="<?= htmlspecialchars($popup['cta_text']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <!-- Extended Settings -->
                        <div class="border-t pt-4 mt-4">
                            <h3 class="font-bold text-gray-600 mb-3">Récompenses de la Roue</h3>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Type de Popup</label>
                                <select name="popup_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="spin_wheel" <?= ($popup['popup_type'] ?? '') == 'spin_wheel' ? 'selected' : '' ?>>Roue de la Fortune (Jeu)</option>
                                    <option value="classic" <?= ($popup['popup_type'] ?? '') == 'classic' ? 'selected' : '' ?>>Classique (Box Simple)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <p class="text-sm mb-3">Créez vos codes dans <a href="promos.php" class="text-purple-600 underline">Gestion des Codes Promo</a> : pourcentage, packs Étudiant, Pro et Senior uniquement. Les cinq résultats (0%, 10%, 20%, 30%, 40%) ont la même chance. Une seule participation par navigateur et adresse IP. Les dates et limites du code s'appliquent.</p>
                                <?php $savedSlices = json_decode($popup['slices'] ?? '[]', true) ?: []; ?>
                                <?php foreach ([10,20,30,40] as $percent):
                                    $selectedId = 0;
                                    foreach ($savedSlices as $slice) { if ((int)($slice['value'] ?? 0) === $percent) $selectedId = (int)($slice['promo_id'] ?? 0); }
                                ?>
                                <label class="block text-sm font-bold mt-3">Récompense -<?= $percent ?>%</label>
                                <select name="wheel_codes[<?= $percent ?>]" class="border rounded w-full p-2">
                                    <option value="">Choisir un code actif</option>
                                    <?php foreach ($wheelPromos as $promo): if ((int)$promo['discount_value'] !== $percent) continue; ?>
                                    <option value="<?= (int)$promo['id'] ?>" <?= $selectedId === (int)$promo['id'] ? 'selected' : '' ?>><?= htmlspecialchars($promo['code']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" <?= $popup['is_active'] ? 'checked' : '' ?>>
                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                <span class="ms-3 text-sm font-medium text-gray-900">Activer le Popup sur le site</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview Box -->
                <div class="bg-gray-200 p-8 rounded-lg flex items-center justify-center relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                    
                    <div class="bg-white w-full max-w-sm rounded-[2rem] p-6 shadow-2xl relative transform scale-90">
                         <!-- Decorative elements -->
                        <div class="absolute -top-5 -right-5 w-20 h-20 bg-purple-100 rounded-full blur-xl"></div>
                        
                        <div class="text-center relative">
                            <?php if (($popup['popup_type'] ?? '') === 'spin_wheel'): ?>
                            <p class="text-purple-600 font-bold mb-4">Roue : 0%, -10%, -20%, -30%, -40%</p>
                            <p class="text-sm mb-4">Le code associé au résultat sera affiché après le jeu.</p>
                            <?php else: ?>
                            <div class="bg-red-50 text-red-500 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">🎁</div>
                            <h2 class="text-xl font-black text-gray-800 mb-2 leading-tight uppercase"><?= htmlspecialchars($popup['title']) ?></h2>
                            <p class="text-gray-500 mb-6 text-sm leading-relaxed"><?= htmlspecialchars($popup['message']) ?></p>

                            <div class="bg-gray-50 p-4 rounded-2xl border-2 border-dashed border-red-200 mb-6">
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-1">CODE CADEAU UNIQUE</p>
                                <div class="text-3xl font-black text-red-500 font-mono tracking-tighter"><?= htmlspecialchars($popup['discount_code']) ?></div>
                            </div>

                            <?php endif; ?>
                            <button class="w-full bg-gray-900 text-white px-4 py-3 rounded-xl font-bold text-sm shadow-xl uppercase tracking-tighter hover:bg-black"><?= htmlspecialchars($popup['cta_text']) ?></button>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>

</html>
