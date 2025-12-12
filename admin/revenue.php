<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Total Revenue (From ORDERS table to ensure it matches 'Verified' status source of truth)
$stmtTotal = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified'");
$totalRevenue = $stmtTotal->fetchColumn() ?: 0;

// By Method (From ORDERS table)
$stmtMethod = $pdo->query("SELECT payment_method, SUM(amount) as total FROM orders WHERE payment_status = 'verified' GROUP BY payment_method");
$byMethod = $stmtMethod->fetchAll();

// By Package (From ORDERS table)
$stmtPackage = $pdo->query("SELECT package_name, SUM(amount) as total FROM orders WHERE payment_status = 'verified' GROUP BY package_name");
$byPackage = $stmtPackage->fetchAll();

// Recent Transactions (From ORDERS table)
$stmtRecent = $pdo->query("SELECT * FROM orders WHERE payment_status = 'verified' ORDER BY created_at DESC LIMIT 20");
$recent = $stmtRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Revenus - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">

        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Revenus</h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                    <h2 class="text-gray-500 text-sm font-bold uppercase">Revenu Total Confirmé</h2>
                    <p class="text-4xl font-bold text-green-600 mt-2"><?= number_format($totalRevenue, 0, ',', ' ') ?>
                        FCFA</p>
                    <p class="text-xs text-gray-400 mt-1">Somme des commandes statut "Vérifié"</p>
                </div>

                <!-- By Method -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-gray-500 text-sm font-bold uppercase border-b pb-2 mb-2">Par Méthode</h2>
                    <ul class="space-y-3">
                        <?php foreach ($byMethod as $row): ?>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-700"><?= htmlspecialchars($row['payment_method']) ?></span>
                                <span
                                    class="font-bold bg-gray-100 px-2 py-1 rounded"><?= number_format($row['total'], 0, ',', ' ') ?>
                                    FCFA</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($byMethod)): ?>
                            <li class="text-gray-400 text-sm italic">Aucune donnée.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- By Package -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-gray-500 text-sm font-bold uppercase border-b pb-2 mb-2">Par Pack</h2>
                    <ul class="space-y-3">
                        <?php foreach ($byPackage as $row): ?>
                            <li class="flex justify-between items-center">
                                <span
                                    class="text-gray-700"><?= htmlspecialchars($row['package_name'] ?? 'Standard') ?></span>
                                <span
                                    class="font-bold bg-gray-100 px-2 py-1 rounded"><?= number_format($row['total'], 0, ',', ' ') ?>
                                    FCFA</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($byPackage)): ?>
                            <li class="text-gray-400 text-sm italic">Aucune donnée.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <h3 class="px-6 py-4 border-b font-bold bg-gray-50">Historique des Transactions (Validées)</h3>
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">Ref
                            </th>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">
                                Client</th>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">Date
                            </th>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">Pack
                            </th>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">
                                Montant</th>
                            <th class="px-5 py-3 bg-white text-left text-xs font-semibold text-gray-600 uppercase">
                                Méthode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $row): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 border-b bg-white text-sm font-mono text-gray-500">
                                    <?= htmlspecialchars($row['order_ref'] ?? $row['id']) ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm font-medium">
                                    <?= htmlspecialchars($row['name']) ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm text-blue-600">
                                    <?= htmlspecialchars($row['package_name'] ?? 'Standard') ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm font-bold text-green-600">
                                    <?= number_format($row['amount'], 0, ',', ' ') ?></td>
                                <td class="px-5 py-4 border-b bg-white text-sm">
                                    <?= htmlspecialchars($row['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>

</html>