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
            $code = strtoupper($_POST['code']);
            $percent = $_POST['discount_percent'];
            $start = $_POST['start_date'];
            $end = $_POST['end_date'];

            $stmt = $pdo->prepare("INSERT INTO promo_codes (code, discount_percent, start_date, end_date) VALUES (?, ?, ?, ?)");
            try {
                $stmt->execute([$code, $percent, $start, $end]);
                $msg = "Code promo créé !";
            } catch (Exception $e) {
                $msg = "Erreur: Code existe peut-être déjà.";
            }
        } elseif ($_POST['action'] === 'toggle') {
            $id = $_POST['id'];
            $status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
            $stmt = $pdo->prepare("UPDATE promo_codes SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        }
    }
}

$promos = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codes Promo - HireMe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans flex text-gray-800">

    <?php include 'includes/nav.php'; ?>

    <main class="p-6 md:p-10 flex-1 overflow-auto">
        <h2 class="text-3xl font-bold mb-8">Gestion des Codes Promo</h2>

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
                        <label class="block text-sm font-medium text-gray-700">Code (Ex: HIREME50)</label>
                        <input type="text" name="code" required
                            class="w-full bg-gray-50 border p-2 rounded uppercase font-bold">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Pourcentage (%)</label>
                        <input type="number" name="discount_percent" min="1" max="100" required
                            class="w-full bg-gray-50 border p-2 rounded">
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Début</label>
                            <input type="date" name="start_date" required class="w-full bg-gray-50 border p-2 rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fin</label>
                            <input type="date" name="end_date" required class="w-full bg-gray-50 border p-2 rounded">
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">Créer</button>
                </form>
            </div>

            <!-- List -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="p-4">Code</th>
                            <th class="p-4 text-center">Réduction</th>
                            <th class="p-4 text-center">Validité</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($promos as $promo):
                            $isActive = $promo['status'] === 'active';
                            $isExpired = strtotime($promo['end_date']) < time();
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-mono font-bold text-lg"><?= htmlspecialchars($promo['code']) ?></td>
                                <td class="p-4 text-center font-bold text-green-600">-<?= $promo['discount_percent'] ?>%
                                </td>
                                <td class="p-4 text-center text-sm">
                                    <span class="block text-gray-400 text-xs">Du <?= $promo['start_date'] ?></span>
                                    <span class="block <?= $isExpired ? 'text-red-500 font-bold' : 'text-gray-600' ?>">Au
                                        <?= $promo['end_date'] ?></span>
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
                                </td>
                                <td class="p-4 text-right">
                                    <form method="POST" class="inline">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $promo['status'] ?>">
                                        <?php if ($isActive): ?>
                                            <button
                                                class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200">Désactiver</button>
                                        <?php else: ?>
                                            <button
                                                class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">Activer</button>
                                        <?php endif; ?>
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