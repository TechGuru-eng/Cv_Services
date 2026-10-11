<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/activity_helper.php';

// Stats Queries
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();

// Correct Revenue: Sum orders that are "verified" (PAID) or check revenue table if sync is perfect.
// User requested: "Revenue = sum of all orders marked as PAID".
// We will use the 'orders' table as the source of truth for simplicity and robustness.
$revenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0;
// Note: If 'payment_status' used different values (e.g. 'paid'), change accordingly. 
// I previously used 'verified' in order-details. I will stick to 'verified'.

$todayRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

// Use the same visitor records as the public tracker and live dashboard.
require_once __DIR__ . '/../includes/visitor-tracking.php';
$todayVisits = site_visits_today($pdo);

// Monthly Revenue for Sales Goal
$currentMonthStr = date('Y-m');
$monthlyRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND DATE_FORMAT(created_at, '%Y-%m') = '$currentMonthStr'")->fetchColumn() ?: 0;
$monthlyGoal = 500000; // Target: 500,000 XAF
$goalProgress = min(100, ($monthlyRevenue / $monthlyGoal) * 100);

// Prospects Stat
$pendingProspects = $pdo->query("SELECT COUNT(*) FROM prospects WHERE status = 'pending'")->fetchColumn();
$lostRevenue = $pdo->query("SELECT SUM(total_amount) FROM prospects WHERE status = 'pending'")->fetchColumn() ?: 0;

// Sponsorship Stats
$totalSponsored = $pdo->query("SELECT COUNT(*) FROM sponsorship_requests WHERE status = 'approved'")->fetchColumn();
$creditsAvailable = $pdo->query("SELECT SUM(credits_remaining) FROM sponsorship_pool")->fetchColumn() ?: 0;

// Recent Hot Prospects (Last 3)
$hotProspects = $pdo->query("SELECT * FROM prospects WHERE status = 'pending' ORDER BY updated_at DESC LIMIT 3")->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<body class="bg-gray-100 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 border-b border-gray-100 p-4 flex justify-between items-center">
                <div class="md:hidden flex items-center gap-3">
                    <span class="text-2xl font-black text-blue-600">HireMe</span>
                </div>
                <div class="flex items-center gap-4 ml-auto">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-gray-500 uppercase">Aujourd'hui</p>
                        <p class="text-sm font-black text-gray-800"><?= date('d F Y') ?></p>
                    </div>
                    <a href="logout.php" class="bg-red-50 text-red-500 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-100 transition-all">Sortir</a>
                </div>
            </header>

            <main class="p-6 md:p-10 flex-1 overflow-x-hidden overflow-y-auto">
                <div class="mb-10">
                    <h2 class="text-3xl font-black text-gray-900 tracking-tight">Vue d'ensemble 👋</h2>
                    <p class="text-gray-500 mt-1">Bon retour dans votre centre de contrôle HireMe.</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                    <!-- Total Orders -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex items-center group">
                        <div class="p-4 bg-blue-50 text-blue-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Total Commandes</p>
                            <p class="text-2xl font-black text-gray-800"><?= $totalOrders ?></p>
                        </div>
                    </div>

                    <!-- Verified Revenue -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex items-center group">
                        <div class="p-4 bg-green-50 text-green-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Revenu Total</p>
                            <p class="text-2xl font-black text-gray-800"><?= number_format($revenue, 0, ',', ' ') ?> <span class="text-sm font-medium text-gray-400 ml-1">XAF</span></p>
                        </div>
                    </div>

                    <!-- Today Revenue -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex items-center group">
                        <div class="p-4 bg-orange-50 text-orange-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Aujourd'hui</p>
                            <p class="text-2xl font-black text-gray-800 text-orange-600"><?= number_format($todayRevenue, 0, ',', ' ') ?> <span class="text-sm font-medium text-gray-400 ml-1">XAF</span></p>
                        </div>
                    </div>

                    <!-- Today Visits -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex items-center group">
                        <div class="p-4 bg-teal-50 text-teal-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Visites Aujourd'hui</p>
                            <p class="text-2xl font-black text-gray-800 text-teal-600"><?= $todayVisits ?></p>
                        </div>
                    </div>

                    <!-- Pending -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow flex items-center group">
                        <div class="p-4 bg-purple-50 text-purple-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-gray-400 text-[10px] font-black uppercase tracking-wider">Commandes en attente</p>
                            <p class="text-2xl font-black text-gray-800"><?= $pendingOrders ?></p>
                        </div>
                    </div>

                    <!-- Abandoned Carts (Prospects) - NEW -->
                    <a href="prospects.php" class="bg-white p-6 rounded-2xl shadow-sm border border-orange-100 hover:border-orange-300 hover:shadow-md transition-all flex items-center group">
                        <div class="p-4 bg-orange-50 text-orange-500 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-orange-400 text-[10px] font-black uppercase tracking-wider">Paniers Abandonnés</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-gray-800"><?= $pendingProspects ?></p>
                                <span class="text-[10px] font-bold text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded">Relancer</span>
                            </div>
                        </div>
                    </a>

                    <!-- Social Impact (Sponsorship) - NEW -->
                    <a href="sponsorships.php" class="bg-white p-6 rounded-2xl shadow-sm border border-teal-100 hover:border-teal-300 hover:shadow-md transition-all flex items-center group">
                        <div class="p-4 bg-teal-50 text-teal-600 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-teal-500 text-[10px] font-black uppercase tracking-wider">Impact Social</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-gray-800"><?= $totalSponsored ?></p>
                                <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded"><?= $creditsAvailable ?> dispos</span>
                            </div>
                        </div>
                    </a>

                    <!-- School Leads (New CRM) -->
                    <a href="school_leads.php" class="bg-white p-6 rounded-2xl shadow-sm border border-indigo-100 hover:border-indigo-300 hover:shadow-md transition-all flex items-center group">
                        <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <i data-lucide="graduation-cap" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <p class="text-indigo-500 text-[10px] font-black uppercase tracking-wider">Leads Écoles</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-gray-800"><?= $pdo->query("SELECT COUNT(*) FROM school_leads")->fetchColumn() ?></p>
                                <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded uppercase tracking-tighter">Gérer CRM</span>
                            </div>
                        </div>
                    </a>

                    <!-- Club HireMe Subscriptions -->
                    <a href="club_platform.php" class="bg-white p-6 rounded-2xl shadow-sm border border-blue-100 hover:border-blue-300 hover:shadow-md transition-all flex items-center group">
                        <div class="p-4 bg-blue-50 text-blue-600 rounded-2xl mr-4 group-hover:scale-110 transition-transform">
                            <i data-lucide="zap" class="w-8 h-8"></i>
                        </div>
                        <div>
                            <p class="text-blue-500 text-[10px] font-black uppercase tracking-wider">Club HireMe</p>
                            <div class="flex items-baseline gap-2">
                                <p class="text-2xl font-black text-gray-800"><?= $pdo->query("SELECT COUNT(*) FROM club_members")->fetchColumn() ?></p>
                                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded uppercase tracking-tighter">Abonnés</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Sales Goal & Quick Actions (NEW BOOST SALES FEATURES) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10 items-start">
                    <!-- Sales Goal Widget -->
                    <div class="lg:col-span-2 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-[2rem] shadow-xl p-8 text-white relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform">
                            <i data-lucide="target" class="w-32 h-32"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-2xl font-black tracking-tight">Objectif Mensuel 🎯</h3>
                                    <p class="text-indigo-100 mt-1">Générer <?= number_format($monthlyGoal, 0, ',', ' ') ?> XAF ce mois-ci</p>
                                </div>
                                <div class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-sm font-bold">
                                    <?= date('F Y') ?>
                                </div>
                            </div>
                            
                            <div class="mt-8">
                                <div class="flex justify-between text-sm font-bold mb-2">
                                    <span><?= number_format($monthlyRevenue, 0, ',', ' ') ?> XAF Réalisés</span>
                                    <span><?= number_format(100 - $goalProgress, 1) ?>% Restant</span>
                                </div>
                                <div class="w-full bg-indigo-900/50 rounded-full h-4 overflow-hidden backdrop-blur-sm">
                                    <div class="bg-white h-4 rounded-full transition-all duration-1000 ease-out relative" style="width: <?= $goalProgress ?>%">
                                        <div class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-r from-transparent to-white/50 animate-pulse"></div>
                                    </div>
                                </div>
                                <p class="text-xs text-indigo-200 mt-3 font-medium">Il vous manque <?= number_format(max(0, $monthlyGoal - $monthlyRevenue), 0, ',', ' ') ?> XAF pour atteindre votre objectif !</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions to Boost Sales -->
                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8">
                        <h3 class="text-xl font-black text-gray-900 tracking-tight mb-1">Boost des Ventes 🚀</h3>
                        <p class="text-xs text-gray-500 mb-6 font-medium">Actions rapides pour générer du CA</p>
                        
                        <div class="space-y-3">
                            <a href="promos.php" class="w-full flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50 group transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="bg-purple-100 text-purple-600 p-2 rounded-lg group-hover:scale-110 transition-transform">
                                        <i data-lucide="tag" class="w-5 h-5"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800">Créer Code Promo Flash</span>
                                </div>
                                <i data-lucide="arrow-right" class="w-4 h-4 text-purple-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all"></i>
                            </a>
                            
                            <a href="sms-panel.php" class="w-full flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-green-200 hover:bg-green-50 group transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="bg-green-100 text-green-600 p-2 rounded-lg group-hover:scale-110 transition-transform">
                                        <i data-lucide="message-square" class="w-5 h-5"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800">Campagne SMS Relance</span>
                                </div>
                                <i data-lucide="arrow-right" class="w-4 h-4 text-green-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all"></i>
                            </a>

                            <a href="export-leads.php" class="w-full flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 group transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg group-hover:scale-110 transition-transform">
                                        <i data-lucide="download" class="w-5 h-5"></i>
                                    </div>
                                    <span class="text-sm font-bold text-gray-800">Exporter Leads (Facebook Ads)</span>
                                </div>
                                <i data-lucide="arrow-right" class="w-4 h-4 text-blue-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Live Activity Feed Component -->
                <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8 mb-10 overflow-hidden relative group">
                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-24 h-24 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="flex justify-between items-center mb-6 relative">
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 tracking-tight">Flux d'Activité Live ⚡</h3>
                            <p class="text-sm font-medium text-gray-500">Suivi en temps réel des actions sur la plateforme.</p>
                        </div>
                        <div class="flex items-center gap-2">
                             <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                             </span>
                             <span class="text-[10px] font-black text-green-600 uppercase tracking-widest">En Direct</span>
                        </div>
                    </div>
                    
                    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php
                        $activities = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT 10")->fetchAll();
                        foreach($activities as $act):
                            $timeDiff = time() - strtotime($act['created_at']);
                            $timeStr = ($timeDiff < 60) ? 'À l\'instant' : (($timeDiff < 3600) ? floor($timeDiff/60).' min' : floor($timeDiff/3600).'h');
                            
                            // Color coding based on action
                            $colorClass = "text-blue-600 bg-blue-50";
                            if(strpos($act['action'], 'Échec') !== false) $colorClass = "text-red-600 bg-red-50";
                            if(strpos($act['action'], 'Prospect') !== false) $colorClass = "text-orange-600 bg-orange-50";
                            if(strpos($act['action'], 'Connexion') !== false) $colorClass = "text-green-600 bg-green-50";
                        ?>
                            <div class="flex items-start gap-4 p-4 rounded-2xl hover:bg-gray-50 transition-all border border-transparent hover:border-gray-100 group/item">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs <?= $colorClass ?> transition-transform group-hover/item:scale-110">
                                    <?= substr($act['action'], 0, 1) ?>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-1">
                                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($act['action']) ?></p>
                                        <span class="text-[10px] font-black text-gray-400 uppercase"><?= $timeStr ?></span>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-relaxed font-medium"><?= htmlspecialchars($act['details']) ?></p>
                                    <div class="flex items-center gap-2 mt-2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                        <span class="text-[9px] font-bold text-gray-300 uppercase"><?= htmlspecialchars($act['user_email'] ?? 'Système') ?></span>
                                        <span class="w-1 h-1 bg-gray-200 rounded-full"></span>
                                        <span class="text-[9px] font-bold text-gray-300 uppercase"><?= htmlspecialchars($act['ip_address'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($activities)): ?>
                            <div class="text-center py-10">
                                <p class="text-gray-400 italic text-sm">Aucune activité enregistrée pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <!-- Recent Orders -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Dernières Commandes</h3>
                            <a href="orders.php" class="text-xs font-bold text-blue-600 hover:underline">Voir tout →</a>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-black">
                                    <tr>
                                        <th class="p-4">Client</th>
                                        <th class="p-4">Pack</th>
                                        <th class="p-4">Prix</th>
                                        <th class="p-4">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php
                                    $recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                    foreach ($recentOrders as $order):
                                        ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="p-4">
                                                <div class="font-bold text-sm"><?= htmlspecialchars($order['name']) ?></div>
                                                <div class="text-[10px] text-gray-400"><?= date('d/m H:i', strtotime($order['created_at'])) ?></div>
                                            </td>
                                            <td class="p-4 text-xs text-gray-500">
                                                <?= htmlspecialchars($order['package_name'] ?? 'Standard') ?></td>
                                            <td class="p-4 text-green-600 font-bold text-xs">
                                                <?= number_format($order['amount'], 0, ',', ' ') ?></td>
                                            <td class="p-4">
                                                <?php if ($order['payment_status'] == 'verified'): ?>
                                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-bold">PAYÉ</span>
                                                <?php else: ?>
                                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-[10px] font-bold">ATTENTE</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Hot Prospects (The "Sweet" Part) -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Paniers "CHAUDS" ⚡</h3>
                            <a href="prospects.php" class="text-xs font-bold text-orange-600 hover:underline">Relancer tout le monde →</a>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-orange-100 overflow-hidden overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-orange-50 text-orange-400 uppercase text-[10px] font-black">
                                    <tr>
                                        <th class="p-4">Prospect</th>
                                        <th class="p-4">Pack</th>
                                        <th class="p-4">Dernière activité</th>
                                        <th class="p-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($hotProspects as $prospect): 
                                        $diff = time() - strtotime($prospect['updated_at']);
                                        $mins = floor($diff / 60);
                                    ?>
                                        <tr class="hover:bg-orange-50/30 transition">
                                            <td class="p-4">
                                                <div class="font-bold text-sm"><?= htmlspecialchars($prospect['name']) ?></div>
                                                <div class="text-[10px] text-gray-400"><?= htmlspecialchars($prospect['whatsapp']) ?></div>
                                            </td>
                                            <td class="p-4">
                                                <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-bold"><?= htmlspecialchars($prospect['package_name']) ?></span>
                                            </td>
                                            <td class="p-4 text-xs">
                                                <span class="<?= ($mins < 15) ? 'text-green-600 font-black' : 'text-gray-400' ?>">
                                                    <?= ($mins < 1) ? 'À l\'instant' : 'Il y a ' . $mins . ' min' ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a href="prospects.php" class="inline-block bg-orange-500 text-white text-[10px] font-black px-3 py-1.5 rounded-lg shadow-sm hover:bg-orange-600 transition-all">
                                                    Relancer
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($hotProspects)): ?>
                                        <tr>
                                            <td colspan="4" class="p-8 text-center text-gray-400 italic">Aucun prospect à relancer. Félicitations !</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
