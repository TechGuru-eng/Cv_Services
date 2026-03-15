<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Date Filter
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 day'));
$endDate = $_GET['end_date'] ?? date('Y-m-d 23:59:59');

// 1. Visitors Count (Flash Page Only)
$stmtVisits = $pdo->prepare("SELECT COUNT(*) FROM stats_visits 
    WHERE (page_url LIKE '%flash%' OR page_url LIKE '%promo%') 
    AND created_at BETWEEN ? AND ?");
$stmtVisits->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$totalVisitors = $stmtVisits->fetchColumn();

// 2. Orders & Revenue (Flash Orders Only)
// identifying flash orders by package name containing 'FLASH' or source if available.
// Based on flash-promo.php, package_name ends with ' (FLASH)'
$stmtOrders = $pdo->prepare("SELECT 
        COUNT(*) as total_orders, 
        SUM(CASE WHEN payment_status IN ('approved', 'verified') THEN amount ELSE 0 END) as revenue_approved,
        SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as revenue_pending,
        SUM(amount) as revenue_total,
        payment_status,
        package_name
    FROM orders 
    WHERE package_name LIKE '%(FLASH)%' 
    AND payment_status NOT IN ('failed', 'rejected', 'cancelled')
    AND created_at BETWEEN ? AND ?
    GROUP BY payment_status, package_name");
$stmtOrders->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$ordersData = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

$totalOrders = 0;
$totalRevenue = 0;
$pendingRevenue = 0;
$approvedRevenue = 0;
$ordersByStatus = [];
$ordersByPack = [];

foreach ($ordersData as $row) {
    $totalOrders += $row['total_orders'];
    $totalRevenue += $row['revenue_total'];
    $pendingRevenue += $row['revenue_pending'];
    // Aggregate approved and verified revenue together
    $approvedRevenue += $row['revenue_approved'];
    
    // Normalize status for the UI counters
    $statusKey = ($row['payment_status'] == 'verified') ? 'approved' : $row['payment_status'];
    if (!isset($ordersByStatus[$statusKey])) $ordersByStatus[$statusKey] = 0;
    $ordersByStatus[$statusKey] += $row['total_orders'];

    // Clean pack name
    $pack = str_replace(' (FLASH)', '', $row['package_name']);
    if (!isset($ordersByPack[$pack])) $ordersByPack[$pack] = 0;
    $ordersByPack[$pack] += $row['total_orders'];
}

// 3. Conversion Rate
$conversionRate = $totalVisitors > 0 ? round(($totalOrders / $totalVisitors) * 100, 2) : 0;

// 4. Most Popular Pack
arsort($ordersByPack);
$topPack = key($ordersByPack) ?? 'N/A';
$topPackCount = current($ordersByPack) ?? 0;

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>
        
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <!-- Header & Filter -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 tracking-tight">⚡ Flash Analytics</h1>
                    <p class="text-gray-500">Analysez la performance de vos ventes flash.</p>
                </div>
                
                <form class="flex items-center gap-2 bg-white p-2 rounded-xl shadow-sm border border-gray-200" method="GET">
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="border-none rounded-lg bg-gray-50 text-sm font-bold p-2 focus:ring-2 focus:ring-blue-500">
                    <span class="text-gray-300">➜</span>
                    <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime($endDate)) ?>" class="border-none rounded-lg bg-gray-50 text-sm font-bold p-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition shadow-lg shadow-blue-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Visitors -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-blue-50 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest relative z-10">Visiteurs Uniques</p>
                    <h3 class="text-4xl font-black text-gray-800 mt-2 relative z-10"><?= number_format($totalVisitors) ?></h3>
                    <div class="mt-4 flex items-center text-xs font-bold text-blue-500 relative z-10">
                        <span class="bg-blue-100 px-2 py-1 rounded-full">Pages Flash</span>
                    </div>
                </div>

                <!-- Orders -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-purple-50 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest relative z-10">Commandes Totales</p>
                    <h3 class="text-4xl font-black text-gray-800 mt-2 relative z-10"><?= number_format($totalOrders) ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-xs font-bold relative z-10">
                        <span class="text-green-500 bg-green-50 px-2 py-1 rounded-full"><?= $ordersByStatus['approved'] ?? 0 ?> Validées</span>
                        <span class="text-orange-500 bg-orange-50 px-2 py-1 rounded-full"><?= $ordersByStatus['pending'] ?? 0 ?> En attente</span>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-green-50 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest relative z-10">Revenu Généré</p>
                    <h3 class="text-4xl font-black text-gray-800 mt-2 relative z-10"><?= number_format($totalRevenue, 0, ',', ' ') ?> <span class="text-sm text-gray-400">FCFA</span></h3>
                    <p class="text-xs text-gray-400 mt-2 relative z-10">dont <?= number_format($pendingRevenue, 0, ',', ' ') ?> FCFA en attente</p>
                </div>

                <!-- Conversion -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 bg-yellow-50 w-24 h-24 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest relative z-10">Taux de Conversion</p>
                    <h3 class="text-4xl font-black text-gray-800 mt-2 relative z-10"><?= $conversionRate ?>%</h3>
                    <div class="mt-4 relative z-10 w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-yellow-400 h-2 rounded-full" style="width: <?= min($conversionRate, 100) ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Packs Breakdown -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-6">Répartition par Pack</h3>
                    <div class="space-y-4">
                        <?php foreach ($ordersByPack as $packName => $count): 
                            $percent = $totalOrders > 0 ? round(($count / $totalOrders) * 100, 1) : 0;
                        ?>
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-bold text-gray-700"><?= htmlspecialchars($packName) ?></span>
                                <span class="text-xs font-bold text-gray-500"><?= $count ?> ventes (<?= $percent ?>%)</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div class="bg-blue-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: 0%; animation: grow<?= md5($packName) ?> 1s forwards;"></div>
                            </div>
                            <style>@keyframes grow<?= md5($packName) ?> { from { width: 0%; } to { width: <?= $percent ?>%; } }</style>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($ordersByPack)): ?>
                            <p class="text-gray-400 italic text-sm text-center py-8">Aucune donnée pour cette période.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Stats -->
                <div class="bg-gray-800 text-white rounded-2xl shadow-lg p-6 flex flex-col justify-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-32 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <p class="text-blue-400 font-bold uppercase tracking-widest text-xs mb-2">Best Seller</p>
                        <h3 class="text-3xl font-black mb-1"><?= htmlspecialchars($topPack) ?></h3>
                        <p class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400"><?= $topPackCount ?></p>
                        <p class="text-sm text-gray-400 mt-1">ventes totales</p>
                    </div>

                    <div class="mt-8 pt-8 border-t border-gray-700 relative z-10">
                        <p class="text-blue-400 font-bold uppercase tracking-widest text-xs mb-2">Panier Moyen</p>
                        <?php $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0; ?>
                        <h3 class="text-3xl font-black text-white"><?= number_format($avgOrder, 0, ',', ' ') ?> FCFA</h3>
                    </div>
                </div>

            </div>
            
            <!-- Latest Orders Table -->
            <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                 <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">Dernières Commandes Flash</h3>
                    <a href="orders.php" class="text-sm text-blue-600 font-bold hover:underline">Voir tout →</a>
                </div>
                <!-- Fetch detailed list -->
                <?php 
                $stmtList = $pdo->prepare("SELECT order_ref, name, package_name, amount, payment_status, created_at 
                    FROM orders 
                    WHERE package_name LIKE '%(FLASH)%' 
                    AND created_at BETWEEN ? AND ?
                    ORDER BY created_at DESC LIMIT 5");
                $stmtList->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                $recentOrders = $stmtList->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-bold text-xs">
                            <tr>
                                <th class="p-4">Réf</th>
                                <th class="p-4">Client</th>
                                <th class="p-4">Pack</th>
                                <th class="p-4">Montant</th>
                                <th class="p-4">Statut</th>
                                <th class="p-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($recentOrders as $ro): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-4 font-mono font-bold text-gray-600">#<?= substr($ro['order_ref'], -6) ?></td>
                                <td class="p-4 font-bold"><?= htmlspecialchars($ro['name']) ?></td>
                                <td class="p-4 text-blue-600"><?= str_replace(' (FLASH)', '', htmlspecialchars($ro['package_name'])) ?></td>
                                <td class="p-4 font-bold"><?= number_format($ro['amount'], 0, ',', ' ') ?> FCFA</td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold 
                                        <?= $ro['payment_status'] == 'approved' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                                        <?= ucfirst($ro['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-400"><?= date('d/m H:i', strtotime($ro['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
