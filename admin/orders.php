<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Commandes - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Commandes</h1>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Ref/ID</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Pack</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Montant</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Méthode</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-bold"><?= htmlspecialchars($order['order_ref'] ?? $order['id']) ?>
                                    </div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-bold"><?= htmlspecialchars($order['name']) ?></div>
                                    <div class="text-gray-500 text-xs"><?= htmlspecialchars($order['email']) ?></div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span
                                        class="bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs"><?= htmlspecialchars($order['package_name'] ?? 'Standard') ?></span>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-semibold">
                                    <?= number_format($order['amount'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="text-gray-900"><?= htmlspecialchars($order['payment_method']) ?></div>
                                    <div class="text-gray-400 text-xs font-mono">
                                        <?= htmlspecialchars($order['transaction_number'] ?? '') ?></div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <span class="relative inline-block px-3 py-1 font-semibold leading-tight 
                                    <?php
                                    if ($order['payment_status'] == 'verified')
                                        echo 'text-green-900';
                                    elseif ($order['payment_status'] == 'failed')
                                        echo 'text-red-900';
                                    else
                                        echo 'text-yellow-900';
                                    ?>">
                                        <span aria-hidden class="absolute inset-0 opacity-50 rounded-full 
                                        <?php
                                        if ($order['payment_status'] == 'verified')
                                            echo 'bg-green-200';
                                        elseif ($order['payment_status'] == 'failed')
                                            echo 'bg-red-200';
                                        else
                                            echo 'bg-yellow-200';
                                        ?>"></span>
                                        <span class="relative"><?= htmlspecialchars($order['payment_status']) ?></span>
                                    </span>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?= date('d/m H:i', strtotime($order['created_at'])) ?></td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <a href="order-details.php?id=<?= $order['id'] ?>"
                                        class="text-blue-600 hover:text-blue-900 font-medium">Gérer</a>
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