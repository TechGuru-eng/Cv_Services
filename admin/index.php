<?php
// admin/index.php
require_once 'auth.php';
require_once 'config/db.php';

// Stats Queries
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingCVs = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified'")->fetchColumn();
// Assuming payment verified means pending CV drafting logic unless we have another status column?
// Let's assume 'orders' table is simplified for now.

$revenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0;

$todayRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - HireMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans flex text-gray-800">

    <?php include 'includes/nav.php'; ?>

    <div class="flex-1 flex flex-col min-h-screen">
        <header class="bg-white shadow p-4 md:hidden">
            <h1 class="text-xl font-bold">HireMe Admin</h1>
        </header>

        <main class="p-6 md:p-10 flex-1">
            <h2 class="text-3xl font-bold mb-8">Vue d'ensemble</h2>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Total Orders -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-4 bg-blue-100 text-blue-600 rounded-full mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Commandes</p>
                        <p class="text-2xl font-bold"><?= $totalOrders ?></p>
                    </div>
                </div>

                <!-- Verified Revenue -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-4 bg-green-100 text-green-600 rounded-full mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Revenu Total</p>
                        <p class="text-2xl font-bold"><?= number_format($revenue, 0, ',', ' ') ?> FCFA</p>
                    </div>
                </div>

                <!-- Today Revenue -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-4 bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Revenu Aujourd'hui</p>
                        <p class="text-2xl font-bold"><?= number_format($todayRevenue, 0, ',', ' ') ?> FCFA</p>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
                    <div class="p-4 bg-purple-100 text-purple-600 rounded-full mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">CVs à Traiter</p>
                        <p class="text-2xl font-bold"><?= $pendingCVs ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders (Simplified) -->
            <h3 class="text-xl font-bold mb-4">Dernières Commandes</h3>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">Client</th>
                            <th class="p-4">Montant</th>
                            <th class="p-4">Statut</th>
                            <th class="p-4">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        $recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
                        foreach ($recentOrders as $order):
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-mono text-sm">#<?= $order['id'] ?></td>
                                <td class="p-4 font-bold"><?= htmlspecialchars($order['client_name']) ?></td>
                                <td class="p-4"><?= number_format($order['amount'], 0) ?> FCFA</td>
                                <td class="p-4">
                                    <?php if ($order['payment_status'] == 'verified'): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Payé</span>
                                    <?php else: ?>
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-sm text-gray-500"><?= $order['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-400">Aucune commande pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

</body>

</html>