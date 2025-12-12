<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (orders.name LIKE ? OR orders.email LIKE ? OR cv_details.skills LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch CVs with Order info
$sql = "SELECT cv_details.*, orders.name, orders.email, orders.country, orders.payment_status 
        FROM cv_details 
        JOIN orders ON cv_details.order_id = orders.id 
        $where 
        ORDER BY cv_details.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Clients Data - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">

        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Clients (Données CV)</h1>

            <!-- Search -->
            <form class="mb-6 flex gap-4 bg-white p-4 rounded shadow-sm">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Rechercher par nom, email, compétence..."
                    class="w-full md:w-1/3 p-2 border rounded focus:ring-blue-500 focus:border-blue-500">
                <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Filtrer</button>
            </form>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">Nom
                            </th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                                Contact</th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                                Pays</th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                                Statut Paiement</th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">CV
                                Upload</th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                                Date Soumission</th>
                            <th class="px-5 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="px-5 py-4 border-b bg-white text-sm font-bold">
                                    <?= htmlspecialchars($client['name']) ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm">
                                    <div><?= htmlspecialchars($client['email']) ?></div>
                                </td>
                                <td class="px-5 py-4 border-b bg-white text-sm"><?= htmlspecialchars($client['country']) ?>
                                </td>
                                <td class="px-5 py-4 border-b bg-white text-sm">
                                    <?php if ($client['payment_status'] == 'verified'): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Vérifié</span>
                                    <?php else: ?>
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 border-b bg-white text-sm">
                                    <?php if ($client['uploaded_cv_path']): ?>
                                        <a href="../<?= htmlspecialchars($client['uploaded_cv_path']) ?>" target="_blank"
                                            class="text-blue-500 underline flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                                                </path>
                                            </svg>
                                            Fichier
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">Rien</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 border-b bg-white text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($client['created_at'])) ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm">
                                    <a href="order-details.php?id=<?= $client['order_id'] ?>"
                                        class="bg-gray-100 border border-gray-300 px-3 py-1 rounded text-xs hover:bg-gray-200 transition">Voir
                                        Détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400">Aucun client trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </main>
    </div>
</body>

</html>