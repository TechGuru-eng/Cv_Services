<?php
// admin/linkedin/index.php
// Admin Dashboard for HireMe LinkedIn Optimization Service

require_once __DIR__ . '/../auth.php';

// Calculate KPIs
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders")->fetchColumn();
$ordersToday = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pendingPayments = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE payment_status = 'PAYMENT_SUBMITTED'")->fetchColumn();
$confirmedOrders = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE payment_status = 'CONFIRMED'")->fetchColumn();
$inProgressOrders = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE order_status = 'EN_COURS_D_OPTIMISATION'")->fetchColumn();
$completedOrders = (int)$pdo->query("SELECT COUNT(*) FROM linkedin_orders WHERE order_status = 'TERMINE'")->fetchColumn();

$totalRevenue = (float)$pdo->query("SELECT SUM(total_amount) FROM linkedin_orders WHERE payment_status = 'CONFIRMED'")->fetchColumn();

// Fetch Recent 10 Orders
$stmtRecent = $pdo->query("SELECT o.*, p.name as package_name 
                           FROM linkedin_orders o 
                           LEFT JOIN linkedin_packages p ON o.package_id = p.id 
                           ORDER BY o.id DESC LIMIT 10");
$recentOrders = $stmtRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<base href="<?= htmlspecialchars(rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin LinkedIn — Dashboard | HireMe</title>
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
        <!-- Sidebar Navigation -->
        <?php require __DIR__ . '/navigation.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6 md:p-10 space-y-8 overflow-x-hidden">
            
            <!-- Top Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Tableau de Bord — LinkedIn Optimization</h1>
                    <p class="text-xs text-slate-500">Vue d'ensemble des ventes et du suivi des prestations LinkedIn.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="commandes.php" class="bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-black text-xs px-4 py-2.5 rounded-xl shadow">
                        <i class="fa-solid fa-list mr-1"></i> Gérer toutes les commandes
                    </a>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <div class="flex justify-between items-center text-slate-500 text-xs">
                        <span>Commandes Totales</span>
                        <i class="fa-solid fa-box text-amber-700 text-lg"></i>
                    </div>
                    <strong class="text-3xl font-black text-slate-900 block"><?= $totalOrders ?></strong>
                    <span class="text-[11px] text-gray-500 block"><?= $ordersToday ?> aujourd'hui</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <div class="flex justify-between items-center text-slate-500 text-xs">
                        <span>Paiements à Vérifier</span>
                        <i class="fa-solid fa-clock text-amber-700 text-lg"></i>
                    </div>
                    <strong class="text-3xl font-black text-amber-700 block"><?= $pendingPayments ?></strong>
                    <span class="text-[11px] text-gray-500 block">En attente de validation</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <div class="flex justify-between items-center text-slate-500 text-xs">
                        <span>En cours d'Optimisation</span>
                        <i class="fa-solid fa-spinner text-blue-700 text-lg"></i>
                    </div>
                    <strong class="text-3xl font-black text-blue-700 block"><?= $inProgressOrders ?></strong>
                    <span class="text-[11px] text-gray-500 block"><?= $completedOrders ?> déjà terminées</span>
                </div>

                <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-2">
                    <div class="flex justify-between items-center text-slate-500 text-xs">
                        <span>Revenu Confirmé</span>
                        <i class="fa-solid fa-sack-dollar text-emerald-700 text-lg"></i>
                    </div>
                    <strong class="text-2xl sm:text-3xl font-black text-emerald-700 block"><?= number_format($totalRevenue, 0, ',', ' ') ?> FCFA</strong>
                    <span class="text-[11px] text-gray-500 block">Basé sur paiements confirmés</span>
                </div>

            </div>

            <!-- Recent Orders Table -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xl">
                <div class="flex justify-between items-center border-b border-slate-200 pb-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-700"></i> Dernières Commandes Reçues
                    </h2>
                    <a href="commandes.php" class="text-xs text-amber-700 hover:underline font-semibold">Voir tout</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="p-3">Réf</th>
                                <th class="p-3">Client</th>
                                <th class="p-3">Téléphone</th>
                                <th class="p-3">Forfait</th>
                                <th class="p-3">Montant</th>
                                <th class="p-3">Paiement</th>
                                <th class="p-3">Statut Commande</th>
                                <th class="p-3">Date</th>
                                <th class="p-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/80">
                            <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="9" class="p-6 text-center text-gray-500 italic">Aucune commande enregistrée pour le moment.</td></tr>
                            <?php else: ?>
                            <?php foreach ($recentOrders as $ord): ?>
                            <tr class="hover:bg-gray-800/50">
                                <td class="p-3 font-mono font-bold text-amber-700"><?= htmlspecialchars($ord['reference']) ?></td>
                                <td class="p-3 font-semibold text-slate-900"><?= htmlspecialchars($ord['first_name'] . ' ' . $ord['name']) ?></td>
                                <td class="p-3"><?= htmlspecialchars($ord['phone']) ?></td>
                                <td class="p-3"><?= htmlspecialchars($ord['package_name']) ?></td>
                                <td class="p-3 font-bold text-slate-900"><?= number_format($ord['total_amount'], 0, ',', ' ') ?> FCFA</td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold <?= $ord['payment_status'] === 'CONFIRMED' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($ord['payment_status'] === 'PAYMENT_SUBMITTED' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-gray-800 text-gray-400') ?>">
                                        <?= htmlspecialchars($ord['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-gray-200">
                                        <?= htmlspecialchars($ord['order_status']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-gray-500"><?= date('d/m/H:i', strtotime($ord['created_at'])) ?></td>
                                <td class="p-3 text-right">
                                    <a href="order_details.php?id=<?= $ord['id'] ?>" class="bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-bold px-3 py-1.5 rounded text-[11px]">
                                        Gérer
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

</body>
</html>
