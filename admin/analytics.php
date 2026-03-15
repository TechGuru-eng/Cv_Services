<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// --- Analytics Queries ---

// 1. Total Visits & Unique Visitors
$totalVisits = $pdo->query("SELECT COUNT(*) FROM stats_visits")->fetchColumn();
$uniqueVisitors = $pdo->query("SELECT COUNT(*) FROM stats_visits WHERE is_unique = 1")->fetchColumn();
$todayVisitors = $pdo->query("SELECT COUNT(*) FROM stats_visits WHERE is_unique = 1 AND DATE(created_at) = CURDATE()")->fetchColumn();

// 2. UTM Sources (Ads Performance)
$utmSources = $pdo->query("SELECT utm_source, COUNT(*) as count FROM stats_visits WHERE utm_source IS NOT NULL GROUP BY utm_source ORDER BY count DESC")->fetchAll();

// 3. Funnel Events
$funnelSteps = [
    'visits' => $uniqueVisitors,
    'pack_selected' => $pdo->query("SELECT COUNT(DISTINCT visit_id) FROM stats_events WHERE event_name = 'pack_selected'")->fetchColumn(),
    'step_2' => $pdo->query("SELECT COUNT(DISTINCT visit_id) FROM stats_events WHERE event_name = 'funnel_step_2_details'")->fetchColumn(),
    'step_3' => $pdo->query("SELECT COUNT(DISTINCT visit_id) FROM stats_events WHERE event_name = 'funnel_step_3_payment_summary'")->fetchColumn(),
    'payment_initiate' => $pdo->query("SELECT COUNT(DISTINCT visit_id) FROM stats_events WHERE event_name = 'payment_initiate'")->fetchColumn(),
    'converted' => $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified'")->fetchColumn()
];

// 4. Top Countries
$topCountries = $pdo->query("SELECT country, COUNT(*) as count FROM stats_visits GROUP BY country ORDER BY count DESC LIMIT 5")->fetchAll();

// 5. Recent High-Intent Events
$recentEvents = $pdo->query("SELECT e.*, v.ip_address, v.country 
                             FROM stats_events e 
                             JOIN stats_visits v ON e.visit_id = v.id 
                             ORDER BY e.created_at DESC LIMIT 15")->fetchAll();

// 6. Intent Heatmap Mapping
$intentData = [];
$packs = ['etudiant' => '🎓 Étudiant', 'pro' => '🚀 Pack Pro', 'senior' => '💼 Pack Senior', 'expert' => '👑 Pack Expert', 'templates40' => '🗂️ Templates'];

foreach ($packs as $id => $label) {
    // Counts hovers (high intent)
    $hovers = $pdo->query("SELECT COUNT(*) FROM stats_events WHERE event_name = 'intent_pack_hover' AND JSON_EXTRACT(event_data, '$.pack_id') = '$id'")->fetchColumn();
    
    // Counts direct clicks (ready to buy)
    $clicks = $pdo->query("SELECT COUNT(*) FROM stats_events WHERE event_name = 'pack_selected' AND JSON_EXTRACT(event_data, '$.pack') = '$id'")->fetchColumn();
    
    // Counts actual sales (confirmed)
    // Note: This matches the pack name as stored in the 'package' column of orders
    // We need to mapping the ID to the name stored in DB.
    $dbName = str_replace(' Pack', '', $label); // Rough mapping or use case-by-case
    if($id == 'etudiant') $dbName = 'Étudiant';
    if($id == 'pro') $dbName = 'Pro';
    if($id == 'senior') $dbName = 'Senior';
    if($id == 'expert') $dbName = 'Expert';
    if($id == 'templates40') $dbName = '40 Modèles';
    
    $sales = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified' AND package_name LIKE '%$dbName%'")->fetchColumn();

    $intentData[$id] = [
        'label' => $label,
        'hovers' => $hovers,
        'clicks' => $clicks,
        'sales' => $sales,
        'heat' => $hovers + ($clicks * 3) + ($sales * 10) // Weighted heatmap score
    ];
}

// Global max heat for scaling
$maxHeat = 0;
foreach($intentData as $d) if($d['heat'] > $maxHeat) $maxHeat = $d['heat'];

// --- Advanced Charts Data (NEW) ---

// A. Revenue Growth (Last 30 Days)
$revenueStats = $pdo->query("SELECT DATE(created_at) as date, SUM(amount) as total 
                             FROM orders 
                             WHERE payment_status = 'verified' 
                             AND created_at >= CURDATE() - INTERVAL 30 DAY 
                             GROUP BY DATE(created_at) 
                             ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

// B. Pack Distribution
$packStats = $pdo->query("SELECT package_name, COUNT(*) as count 
                          FROM orders 
                          WHERE payment_status = 'verified' 
                          GROUP BY package_name")->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include 'includes/header.php'; ?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style type="text/css">
    @media print {
        aside, header, .no-print {
            display: none !important;
        }
        .flex {
            display: block !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
        .bg-gray-100 {
            background: white !important;
        }
        .rounded-2xl {
            border-radius: 0 !important;
            border: 1px solid #eee !important;
            box-shadow: none !important;
        }
    }
</style>
</head>
<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white shadow p-4 md:hidden flex justify-between items-center">
                <h1 class="text-xl font-bold">Analytiques</h1>
                <a href="logout.php" class="text-red-500 font-bold">Sortir</a>
            </header>

            <main class="p-6 md:p-10 flex-1 overflow-x-hidden overflow-y-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-3xl font-bold">Tableau de Bord Marketing</h2>
                        <p class="text-gray-500">Trackage des audiences et du tunnel de conversion</p>
                    </div>
                    <div class="flex gap-3 no-print">
                        <a href="api/export_analytics.php?type=visits" class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                            <span>📊</span> Log Visites CSV
                        </a>
                        <a href="api/export_analytics.php?type=events" class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                            <span>🎯</span> Intentions CSV
                        </a>
                        <button onclick="window.print()" class="flex items-center gap-2 bg-indigo-600 px-4 py-2 rounded-xl text-sm font-bold text-white hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">
                            <span>📄</span> Rapport PDF
                        </button>
                    </div>
                </div>

                <!-- Ad Campaigns & UTMs -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                    <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                            <span class="mr-2">📣</span> Sources de Trafic (UTM)
                        </h3>
                        <div class="space-y-3">
                            <?php if(empty($utmSources)): ?>
                                <p class="text-gray-400 text-sm italic">Aucune campagne détectée. Utilisez des liens UTM pour tracker vos pubs.</p>
                            <?php endif; ?>
                            <?php foreach($utmSources as $source): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                                <span class="text-sm font-bold text-gray-600"><?= htmlspecialchars($source['utm_source']) ?></span>
                                <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full"><?= $source['count'] ?> clics</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700">
                            <strong>Tip:</strong> Taggez vos liens FB avec <code>?utm_source=facebook</code>
                        </div>
                    </div>

                    <!-- Conversion Funnel -->
                    <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-700 mb-6 flex items-center">
                            <span class="mr-2">🎯</span> Tunnel de Conversion
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center">
                            <div class="funnel-step">
                                <p class="text-[10px] text-gray-400 uppercase">Visiteurs</p>
                                <p class="text-xl font-black text-gray-800"><?= $funnelSteps['visits'] ?></p>
                            </div>
                            <div class="funnel-step">
                                <p class="text-[10px] text-gray-400 uppercase">Pack Choisi</p>
                                <p class="text-xl font-black text-blue-600"><?= $funnelSteps['pack_selected'] ?></p>
                                <p class="text-[10px] text-blue-400"><?= $funnelSteps['visits'] > 0 ? round(($funnelSteps['pack_selected']/$funnelSteps['visits'])*100) : 0 ?>%</p>
                            </div>
                            <div class="funnel-step">
                                <p class="text-[10px] text-gray-400 uppercase">Infos Client</p>
                                <p class="text-xl font-black text-indigo-600"><?= $funnelSteps['step_2'] ?></p>
                                <p class="text-[10px] text-indigo-400"><?= $funnelSteps['pack_selected'] > 0 ? round(($funnelSteps['step_2']/$funnelSteps['pack_selected'])*100) : 0 ?>%</p>
                            </div>
                            <div class="funnel-step">
                                <p class="text-[10px] text-gray-400 uppercase">Récapitulatif</p>
                                <p class="text-xl font-black text-purple-600"><?= $funnelSteps['step_3'] ?></p>
                            </div>
                            <div class="funnel-step">
                                <p class="text-[10px] text-gray-400 uppercase">Paiement Lancé</p>
                                <p class="text-xl font-black text-pink-600"><?= $funnelSteps['payment_initiate'] ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase">Payé (Ventes)</p>
                                <p class="text-xl font-black text-green-600"><?= $funnelSteps['converted'] ?></p>
                                <p class="text-[10px] text-green-400"><?= $funnelSteps['visits'] > 0 ? round(($funnelSteps['converted']/$funnelSteps['visits'])*100, 1) : 0 ?>% total</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual Intelligence Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                    <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="text-xl font-black text-slate-800 flex items-center">
                                <span class="w-2 h-2 bg-indigo-500 rounded-full mr-3"></span> Croissance des Revenus
                            </h3>
                            <span class="text-[10px] font-black text-slate-400 bg-slate-50 px-3 py-1 rounded-full uppercase tracking-widest">Derniers 30 Jours</span>
                        </div>
                        <div class="relative h-[300px]">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center">
                            <span class="w-2 h-2 bg-orange-500 rounded-full mr-3"></span> Mix Produit (Ventes)
                        </h3>
                        <div class="relative h-[300px] flex items-center justify-center">
                            <canvas id="packChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Visual Intent Map (Heatmap) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-10 overflow-hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-700 flex items-center">
                            <span class="mr-2">🔥</span> Carte d'Intention Visuelle (Heatmap)
                        </h3>
                        <span class="text-[10px] bg-orange-100 text-orange-600 px-2 py-1 rounded-full font-bold uppercase tracking-widest">Temps Réel</span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                        <?php foreach($intentData as $id => $data): 
                            $heatPercent = $maxHeat > 0 ? round(($data['heat'] / $maxHeat) * 100) : 0;
                            $color = "gray";
                            if($heatPercent > 20) $color = "blue";
                            if($heatPercent > 50) $color = "indigo";
                            if($heatPercent > 80) $color = "orange";
                            if($heatPercent > 90) $color = "red";
                        ?>
                        <div class="relative p-4 rounded-xl border border-gray-50 bg-gray-50/30 group hover:bg-white hover:shadow-md transition-all">
                            <div class="text-xs font-black text-gray-400 uppercase mb-3"><?= $data['label'] ?></div>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-gray-400">👀 Intérêt (Hovers)</span>
                                    <span class="font-bold text-gray-700"><?= $data['hovers'] ?></span>
                                </div>
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-gray-400">🖱️ Clics</span>
                                    <span class="font-bold text-blue-600"><?= $data['clicks'] ?></span>
                                </div>
                                <div class="flex justify-between text-[10px]">
                                    <span class="text-gray-400">💰 Ventes</span>
                                    <span class="font-bold text-green-600"><?= $data['sales'] ?></span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex justify-between items-end mb-1">
                                    <span class="text-[9px] font-black text-<?= $color ?>-600 uppercase">Température</span>
                                    <span class="text-sm font-black text-<?= $color ?>-700"><?= $heatPercent ?>°</span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-<?= $color ?>-500 transition-all duration-1000" style="width: <?= $heatPercent ?>%"></div>
                                </div>
                            </div>
                            
                            <!-- Heat Overlay effect -->
                            <div class="absolute inset-0 bg-<?= $color ?>-500/5 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity rounded-xl"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 p-3 bg-indigo-50 rounded-xl text-[10px] text-indigo-700 leading-relaxed">
                        <strong>Comment lire :</strong> Plus la température est élevée, plus le pack attire l'attention. 
                        Si un pack a beaucoup de <span class="font-bold">Hovers</span> mais peu de <span class="font-bold">Ventes</span>, 
                        votre prix est peut-être trop haut ou la description n'est pas assez convaincante.
                    </div>
                </div>

                <!-- Traffic by Page -->
                <?php
                // Query Traffic by Page
                $trafficByPage = $pdo->query("SELECT page_url, COUNT(*) as visits, COUNT(DISTINCT ip_address) as unique_visitors 
                                              FROM stats_visits 
                                              GROUP BY page_url 
                                              ORDER BY visits DESC 
                                              LIMIT 10")->fetchAll();
                ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-10">
                    <h3 class="font-bold text-gray-700 mb-6 flex items-center">
                        <span class="mr-2">📄</span> Trafic par Page
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                                <tr>
                                    <th class="p-3 rounded-l-lg">Page</th>
                                    <th class="p-3">Vues Totales</th>
                                    <th class="p-3 rounded-r-lg">Visiteurs Uniques</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($trafficByPage as $page): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 font-medium text-gray-600">
                                        <?php if($page['page_url'] == '/') echo '/ (Accueil)'; else echo htmlspecialchars($page['page_url']); ?>
                                    </td>
                                    <td class="p-3 font-bold text-gray-800"><?= $page['visits'] ?></td>
                                    <td class="p-3 text-sm text-gray-500"><?= $page['unique_visitors'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Events (Audience Insights) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Actions à Haute Intention (Audience Retargeting)</h3>
                        <span class="text-xs text-gray-400">Dernières actions des utilisateurs</span>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                            <tr>
                                <th class="p-4">Action</th>
                                <th class="p-4">Détails</th>
                                <th class="p-4">Provenance</th>
                                <th class="p-4">Il y a</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach($recentEvents as $e): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="p-4">
                                    <?php 
                                    $badgeClass = "bg-gray-100 text-gray-600";
                                    if(strpos($e['event_name'], 'whatsapp') !== false) $badgeClass = "bg-green-100 text-green-700";
                                    if(strpos($e['event_name'], 'payment') !== false) $badgeClass = "bg-purple-100 text-purple-700";
                                    if(strpos($e['event_name'], 'audit') !== false) $badgeClass = "bg-blue-100 text-blue-700";
                                    ?>
                                    <span class="<?= $badgeClass ?> px-2 py-1 rounded text-[10px] font-bold uppercase"><?= str_replace('_', ' ', $e['event_name']) ?></span>
                                </td>
                                <td class="p-4 text-xs text-gray-500 italic max-w-xs truncate">
                                    <?= htmlspecialchars($e['event_data']) ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center">
                                        <span class="text-xs font-bold text-gray-700"><?= htmlspecialchars($e['country']) ?></span>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <span class="text-[10px] text-gray-400"><?= $e['ip_address'] ?></span>
                                    </div>
                                </td>
                                <td class="p-4 text-[10px] text-gray-400">
                                    <?= date('H:i', strtotime($e['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <script>
        // Data from PHP
        const revenueData = <?= json_encode($revenueStats) ?>;
        const packData = <?= json_encode($packStats) ?>;

        // 1. Revenue Growth Chart
        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: revenueData.map(d => new Date(d.date).toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})),
                datasets: [{
                    label: 'Revenu (FCFA)',
                    data: revenueData.map(d => d.total),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: function(context) {
                                return new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: true, color: 'rgba(0,0,0,0.05)', drawBorder: false },
                        ticks: { 
                            font: { size: 10, weight: 'bold' },
                            callback: function(value) { return value >= 1000 ? (value/1000) + 'k' : value; }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });

        // 2. Pack Distribution Chart
        const ctxPack = document.getElementById('packChart').getContext('2d');
        new Chart(ctxPack, {
            type: 'doughnut',
            data: {
                labels: packData.map(d => d.package_name),
                datasets: [{
                    data: packData.map(d => d.count),
                    backgroundColor: [
                        '#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
