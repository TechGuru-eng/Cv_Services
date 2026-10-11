<?php
// admin/promos.php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'includes/csrf.php';

// Handle Actions (Create, Toggle, Delete)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); // Check CSRF first
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $code = strtoupper(trim($_POST['code']));
            $type = $_POST['discount_type'];
            $value = $_POST['discount_value'];
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];
            $products = isset($_POST['products']) ? implode(',', $_POST['products']) : null;
            $limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            // If setting as default, unset other defaults
            if ($is_default) {
                $pdo->exec("UPDATE promo_codes SET is_default = 0");
            }

            $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_type, discount_value, start_date, end_date, products, usage_limit, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$code, $type, $value, $start, $end, $products, $limit, $is_default]);
                $msg = "Code promo créé !";
            } catch (Exception $e) {
                $msg = "Erreur: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'toggle') {
            $id = $_POST['id'];
            $status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE promo_codes SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Code promo supprimé.";
        }
    }
}

$promos = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800 flex h-screen overflow-hidden">

    <?php include 'includes/nav.php'; ?>

    <main class="p-6 md:p-10 flex-1 overflow-auto">
        <h2 class="text-3xl font-bold mb-8">Gestion des Codes Promo</h2>
        <p class="mb-6">Pour la roue de sortie, créez quatre codes de 10%, 20%, 30% et 40%, limités aux packs Étudiant, Pro et Senior, puis associez-les dans <a class="text-purple-600 underline" href="exit_popups.php">Configuration Popup de Sortie</a>. Les codes associés à la roue ne sont pas appliqués automatiquement.</p>

        <?php if ($msg): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                <h3 class="font-bold text-lg mb-4">Créer un nouveau code</h3>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Code (Ex: HIREME60)</label>
                        <input type="text" name="code" required
                            class="w-full bg-gray-50 border p-2 rounded uppercase font-bold">
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type de Réduction</label>
                            <select name="discount_type" class="w-full bg-gray-50 border p-2 rounded">
                                <option value="percent">Pourcentage (%)</option>
                                <option value="fixed">Montant Fixe (XAF)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Valeur</label>
                            <input type="number" name="discount_value" min="0" required
                                class="w-full bg-gray-50 border p-2 rounded">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produits Applicables (Aucun = Tous)</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="etudiant"> Pack Étudiant</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="pro"> Pack Pro</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="senior"> Pack Senior</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="expert"> Pack Expert</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="templates40"> 40 Modèles</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="1"> LinkedIn Essentiel</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="2"> LinkedIn Pro</label>
                            <label class="flex items-center gap-2"><input type="checkbox" name="products[]" value="3"> LinkedIn Executive</label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Limite d'utilisation (Optionnel)</label>
                        <input type="number" name="usage_limit" placeholder="Ex: 100"
                            class="w-full bg-gray-50 border p-2 rounded">
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début</label>
                            <input type="date" name="start_date" required class="w-full bg-gray-50 border p-2 rounded" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin</label>
                            <input type="date" name="end_date" required class="w-full bg-gray-50 border p-2 rounded" value="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                        </div>
                    </div>
                    <div class="mb-6 flex items-center gap-2">
                        <input type="checkbox" name="is_default" id="is_default" class="rounded">
                        <label for="is_default" class="text-sm font-bold text-blue-600">Appliquer automatiquement (Main Promo)</label>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 shadow-lg transition-all">Créer le code</button>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="p-4">Code</th>
                            <th class="p-4 text-center">Réduction</th>
                            <th class="p-4 text-center">Usage</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($promos as $promo):
                            $isActive = $promo['status'] === 'active';
                            $isExpired = strtotime($promo['end_date']) < time();
                            ?>
                            <tr class="hover:bg-gray-50 transition <?= $promo['is_default'] ? 'bg-blue-50/50' : '' ?>">
                                <td class="p-4">
                                    <div class="font-mono font-bold text-lg flex items-center gap-2">
                                        <?= htmlspecialchars($promo['code']) ?>
                                        <?php if ($promo['is_default']): ?>
                                            <span class="bg-blue-600 text-white text-[10px] px-1.5 py-0.5 rounded-full" title="Main Auto-applied Promo">DEFAULT</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        <?= $promo['products'] ? 'Packs: ' . str_replace(',', ', ', $promo['products']) : 'Tous les packs' ?>
                                    </div>
                                </td>
                                <td class="p-4 text-center font-bold text-green-600">
                                    <?= $promo['discount_type'] === 'percent' ? '-' . $promo['discount_value'] . '%' : '-' . number_format($promo['discount_value'], 0) . ' XAF' ?>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="text-sm font-bold"><?= $promo['usage_count'] ?><?= $promo['usage_limit'] ? ' / ' . $promo['usage_limit'] : '' ?></div>
                                    <div class="text-[10px] text-gray-400 italic">utilisations</div>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($isActive && !$isExpired): ?>
                                        <span
                                            class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs uppercase font-bold">Actif</span>
                                    <?php elseif (!$isActive): ?>
                                        <span
                                            class="bg-gray-100 text-gray-500 px-2 py-1 rounded text-xs uppercase">Inactif</span>
                                    <?php else: ?>
                                        <span
                                            class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs uppercase font-bold">Expiré</span>
                                    <?php endif; ?>
                                    <div class="text-[10px] text-gray-400 mt-1">Jusqu'au <?= date('d/m/Y', strtotime($promo['end_date'])) ?></div>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <form method="POST" class="inline">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $promo['status'] ?>">
                                        <?php if ($isActive): ?>
                                            <button
                                                class="text-[10px] border border-red-200 text-red-600 px-2 py-1 rounded hover:bg-red-50">Désactiver</button>
                                        <?php else: ?>
                                            <button
                                                class="text-[10px] border border-green-200 text-green-600 px-2 py-1 rounded hover:bg-green-50">Activer</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce code ?')">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <button class="text-[10px] text-gray-400 hover:text-red-500 underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>