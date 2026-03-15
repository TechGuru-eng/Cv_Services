<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Handle Add/Edit/Delete
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'];
            $ref_price = $_POST['ref_price'];
            $min_price = $_POST['min_price'];
            $max_stock = $_POST['max_stock'] ?? 10;
            $stmt = $pdo->prepare("INSERT INTO flash_products (name, reference_price, min_price, max_stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $ref_price, $min_price, $max_stock]);
            $msg = "Produit ajouté !";
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM flash_products WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Produit supprimé.";
        } elseif ($_POST['action'] === 'toggle') {
            $id = $_POST['id'];
            $state = $_POST['state'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE flash_products SET is_active = ? WHERE id = ?");
            $stmt->execute([$state, $id]);
            $msg = "Statut mis à jour.";
        } elseif ($_POST['action'] === 'update') {
             $id = $_POST['id'];
             $name = $_POST['name'];
             $ref_price = $_POST['ref_price'];
             $min_price = $_POST['min_price'];
             $stmt = $pdo->prepare("UPDATE flash_products SET name=?, reference_price=?, min_price=? WHERE id=?");
             $stmt->execute([$name, $ref_price, $min_price, $id]);
             $msg = "Produit mis à jour.";
        } elseif ($_POST['action'] === 'update_stock') {
            $id = $_POST['id'];
            $sold = $_POST['sold_count'];
            $max = $_POST['max_stock'];
            $stmt = $pdo->prepare("UPDATE flash_products SET sold_count = ?, max_stock = ? WHERE id = ?");
            $stmt->execute([$sold, $max, $id]);
            $msg = "Stock mis à jour !";
        }
    }
}

$products = $pdo->query("SELECT * FROM flash_products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                 <h1 class="text-3xl font-bold text-gray-800">⚡ Produits Flash</h1>
                 <a href="settings.php" class="text-blue-600 hover:underline">&larr; Retour Paramètres</a>
            </div>

            <?php if ($msg): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= $msg ?></div>
            <?php endif; ?>

            <!-- Add Form -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <h3 class="font-bold text-lg mb-4">Ajouter un nouveau produit</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="Nom du pack (ex: Pack Etudiant)" required class="border p-2 rounded">
                    <input type="number" name="ref_price" placeholder="Valeur Réelle (Prix Barré)" required class="border p-2 rounded">
                    <input type="number" name="min_price" placeholder="Minimum Accepté" required class="border p-2 rounded">
                    <input type="number" name="max_stock" placeholder="Places Dispo" value="10" required class="border p-2 rounded">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">Ajouter</button>
                </form>
            </div>

            <!-- List -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produit</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix Standard</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix Minimum</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock (Vendu / Total)</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap font-bold"><?= htmlspecialchars($p['name']) ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-500 whitespace-no-wrap line-through"><?= number_format($p['reference_price'], 0, ',', ' ') ?> FCFA</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-green-600 whitespace-no-wrap font-bold"><?= number_format($p['min_price'], 0, ',', ' ') ?> FCFA</p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <form method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <div class="flex items-center bg-gray-100 rounded p-1">
                                        <input type="number" name="sold_count" value="<?= $p['sold_count'] ?>" class="w-12 bg-transparent text-right font-bold focus:outline-none" title="Vendus">
                                        <span class="px-1 text-gray-400">/</span>
                                        <input type="number" name="max_stock" value="<?= $p['max_stock'] ?>" class="w-12 bg-transparent font-bold focus:outline-none" title="Total">
                                    </div>
                                    <button type="submit" class="text-blue-600 hover:text-blue-800" title="Enregistrer">
                                        💾
                                    </button>
                                </form>
                                <?php 
                                    $remaining = $p['max_stock'] - $p['sold_count'];
                                    if ($remaining <= 0) echo '<span class="text-xs text-red-500 font-bold block mt-1">ÉPUISÉ</span>';
                                    elseif ($remaining <= 5) echo '<span class="text-xs text-orange-500 font-bold block mt-1">URGENCE ('.$remaining.')</span>';
                                ?>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="state" value="<?= $p['is_active'] ?>">
                                    <button type="submit" class="text-xs font-bold px-2 py-1 rounded <?= $p['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $p['is_active'] ? 'ACTIF' : 'INACTIF' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce produit ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
