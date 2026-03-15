<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/activity_helper.php';

// Log access
try {
    log_activity("Consultation Audiences Étrangères", "L'administrateur a consulté le tableau de bord des clients internationaux.");
} catch (Exception $e) {}

// --- Geography Filters ---
// We define anything not Cameroon as foreign
$exclusionList = array("'Cameroun'", "'Cameroon'", "'CM'", "'Non spécifié'", "''");
$exclusionStr = implode(",", $exclusionList);

// --- Metrics ---

// 1. Verified Foreign Orders
$verifiedForeignOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE country NOT IN ($exclusionStr) AND country IS NOT NULL AND payment_status = 'verified'")->fetchColumn();

// 2. Foreign Revenue
$foreignRevenueXAF = $pdo->query("SELECT SUM(amount) FROM orders WHERE country NOT IN ($exclusionStr) AND country IS NOT NULL AND payment_status = 'verified'")->fetchColumn() ?: 0;

// 3. Foreign Prospects (Pending Carts from foreign countries)
$foreignProspectsCount = $pdo->query("SELECT COUNT(*) FROM prospects p JOIN stats_visits v ON p.visit_id = v.id WHERE v.country NOT IN ('Cameroun', 'Cameroon', 'CM', 'Unknown') AND p.status = 'pending'")->fetchColumn();

// 4. Foreign Conversion Rate
$totalForeignIntent = $verifiedForeignOrders + $foreignProspectsCount;
$foreignConvRate = ($totalForeignIntent > 0) ? round(($verifiedForeignOrders / $totalForeignIntent) * 100, 1) : 0;

// --- Data Lists ---

// Top Foreign Orders
$foreignOrders = $pdo->query("SELECT * FROM orders WHERE country NOT IN ($exclusionStr) AND country IS NOT NULL ORDER BY created_at DESC LIMIT 15")->fetchAll();

// Top Foreign Prospects
$foreignProspects = $pdo->query("SELECT p.*, v.country as visitor_country FROM prospects p JOIN stats_visits v ON p.visit_id = v.id WHERE v.country NOT IN ('Cameroun', 'Cameroon', 'CM', 'Unknown') AND p.status = 'pending' ORDER BY p.updated_at DESC LIMIT 15")->fetchAll();

// Geographic Interest (Traffic)
$topCountries = $pdo->query("SELECT country, COUNT(*) as visits, COUNT(DISTINCT ip_address) as uniques FROM stats_visits WHERE country NOT IN ('Cameroun', 'Cameroon', 'CM', 'Unknown') AND country IS NOT NULL GROUP BY country ORDER BY uniques DESC LIMIT 10")->fetchAll();
$totalForeignTraffic = 0;
foreach($topCountries as $gi) $totalForeignTraffic += $gi['uniques'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients Étrangers - HireMe Admin</title>
    
    <!-- Favicons -->
    <link rel="icon" href="../assets/favicons/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png">
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .gradient-text { background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .animate-pulse-green { animation: pulse-green 2s infinite; }
    </style>
</head>
<body class="bg-[#F8FAFC]">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white border-b border-slate-100 p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
                <h1 class="text-xl font-black text-blue-600">HireMe <span class="text-orange-600">Global</span></h1>
                <div class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">Intl Tracker</div>
            </header>
            <main class="flex-1 overflow-y-auto p-4 md:p-10 pb-32 md:pb-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight mb-2">Expansion Mondiale 🌍</h1>
                    <p class="text-slate-500 font-medium italic">Suivi des clients et opportunités hors Cameroun</p>
                </div>
                <div>
                    <div class="px-4 py-2 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Global Watcher Active</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="glass-card p-6 rounded-[2rem] shadow-xl shadow-slate-200/40">
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Ventes Export</p>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-black text-slate-900"><?= $verifiedForeignOrders ?></span>
                        <span class="text-xs font-bold text-indigo-600 mb-1">Payées</span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-[2rem] shadow-xl shadow-slate-200/40">
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">CA International</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-slate-900"><?= number_format($foreignRevenueXAF, 0, ',', ' ') ?></span>
                        <span class="text-xs font-bold text-slate-500 mb-1">XAF</span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-[2rem] shadow-xl shadow-slate-200/40">
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Paniers Étrangers</p>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-black text-orange-600"><?= $foreignProspectsCount ?></span>
                        <span class="text-xs font-bold text-orange-400 mb-1">Opportunités</span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-[2rem] shadow-xl shadow-slate-200/40">
                    <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-2">Taux d'Efficacité</p>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-black text-indigo-600"><?= $foreignConvRate ?>%</span>
                        <span class="text-xs font-bold text-indigo-400 mb-1">Marketing</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Orders & Prospects -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Verified Foreign Customers -->
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/20">
                            <h3 class="text-lg font-black text-slate-800">Derniers Clients Internationaux</h3>
                            <span class="text-[9px] bg-slate-900 px-3 py-1 rounded-full font-bold text-white uppercase tracking-widest">Verified</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 text-[10px] text-slate-400 uppercase font-black">
                                    <tr>
                                        <th class="p-6">Client</th>
                                        <th class="p-6">Provenance</th>
                                        <th class="p-6">Montant</th>
                                        <th class="p-6 text-right">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php foreach($foreignOrders as $o): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="p-6">
                                            <div class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors"><?= htmlspecialchars($o['name']) ?></div>
                                            <div class="text-[10px] text-slate-400"><?= htmlspecialchars($o['whatsapp']) ?></div>
                                        </td>
                                        <td class="p-6">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg">📍</span>
                                                <span class="text-xs font-bold text-slate-600"><?= htmlspecialchars($o['country']) ?></span>
                                            </div>
                                        </td>
                                        <td class="p-6">
                                            <span class="text-sm font-black text-slate-900"><?= number_format($o['amount'], 0, ',', ' ') ?> XAF</span>
                                        </td>
                                        <td class="p-6 text-right text-[10px] text-slate-400 font-bold">
                                            <?= date('d M, Y', strtotime($o['created_at'])) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($foreignOrders)): ?>
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-300 italic">Aucun client étranger trouvé pour le moment.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Foreign Prospects (Relance) -->
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                        <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-orange-50/20">
                            <h3 class="text-lg font-black text-slate-800">Paniers Abandonnés (Étranger)</h3>
                            <span class="text-[9px] bg-orange-500 px-3 py-1 rounded-full font-bold text-white uppercase tracking-widest">Hot Leads</span>
                        </div>
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 text-[10px] text-slate-400 uppercase font-black">
                                     <tr>
                                         <th class="p-6">Prospect</th>
                                         <th class="p-6 text-center">Relance Stage</th>
                                         <th class="p-6">Pack Potentiel</th>
                                         <th class="p-6 text-right">Relance</th>
                                     </tr>
                                 </thead>
                                 <tbody class="divide-y divide-slate-50">
                                     <?php foreach($foreignProspects as $p): 
                                         $firstName = trim(explode(' ', trim($p['name'] ?: 'Client'))[0]);
                                         $packageName = $p['package_name'] ?? 'CV Professionnel';
                                         $baseUrl = "https://moncvpro.hiremeguide.com/";
                                         $paymentLink = $baseUrl . "paiement.html?package=" . urlencode($packageName) . "&amount=" . $p['total_amount'] . "&name=" . urlencode($p['name']) . "&phone=" . urlencode($p['whatsapp']);
                                         $modelsLink = $baseUrl . "cv-models";
                                         $stage = (int)$p['followup_stage'];
                                         $nextLevel = min(10, $stage + 1);
                                     ?>
                                     <tr class="hover:bg-slate-50/50 transition-colors group">
                                         <td class="p-6">
                                             <div class="font-bold text-slate-800"><?= htmlspecialchars($p['name']) ?></div>
                                             <div class="text-[10px] text-slate-400"><?= htmlspecialchars($p['whatsapp']) ?></div>
                                         </td>
                                         <td class="p-6 text-center">
                                             <div class="flex flex-col items-center gap-1.5 min-w-[120px]">
                                                 <div class="flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 border border-slate-100 rounded-full">
                                                     <span class="text-lg">🌍</span>
                                                     <span class="text-[10px] font-bold text-slate-600"><?= htmlspecialchars($p['visitor_country']) ?></span>
                                                 </div>
                                                 <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden flex gap-0.5 p-0.5">
                                                     <?php for($i=1; $i<=10; $i++): ?>
                                                         <div class="flex-1 h-full rounded-full <?= ($i <= $stage) ? 'bg-green-500' : (($i == $stage+1) ? 'bg-blue-500 animate-pulse' : 'bg-slate-200') ?>"></div>
                                                     <?php endfor; ?>
                                                 </div>
                                                 <div class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">
                                                     Stage <?= $stage ?>/10
                                                 </div>
                                             </div>
                                         </td>
                                         <td class="p-6">
                                             <div class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-tighter"><?= htmlspecialchars($p['package_name'] ?? 'Non spécifié') ?></div>
                                             <span class="text-sm font-black text-orange-600"><?= number_format($p['total_amount'] ?? 0, 0, ',', ' ') ?> XAF</span>
                                         </td>
                                         <td class="p-6 text-right">
                                             <div class="relative inline-block group/menu text-left">
                                                 <button class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-[10px] font-black rounded-full hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 uppercase tracking-widest">
                                                     <span>Propulser L<?= $nextLevel ?></span> 🚀
                                                 </button>
                                                 
                                                 <div class="absolute right-0 mt-2 w-72 bg-white border border-slate-100 rounded-2xl shadow-2xl opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all z-20 overflow-hidden ring-4 ring-black/5">
                                                     <?php
                                                         $intl_templates = [
                                                             1 => ['label' => 'L1: Assistant (Crypto/USDT?)', 'icon' => '🌍', 'msg' => "Bonjour $firstName, c'est l'équipe MonCv ! Besoin d'aide technique pour finaliser votre $packageName ou pour le paiement international (USDT/Crypto) ? Reprenez ici : $paymentLink"],
                                                             2 => ['label' => 'L2: Qualité (Modèles)', 'icon' => '🎨', 'msg' => "Re-bonjour $firstName ! Voici nos derniers CV réalisés pour nos clients internationaux cette semaine : $modelsLink. Lequel vous inspire pour votre $packageName ?"],
                                                             3 => ['label' => 'L3: Autorité (RH)', 'icon' => '👨‍💼', 'msg' => "Bonjour $firstName, nos experts RH internationaux sont prêts. Un CV aux normes mondiales augmente vos chances de 60%. Reprenez votre commande : $paymentLink"],
                                                             4 => ['label' => 'L4: Promo Global (-73%)', 'icon' => '🎁', 'msg' => "Bonjour $firstName ! Utilisez le code 'LASTCHANCE' pour obtenir -73% sur votre $packageName. Offre spéciale pour les clients hors Cameroun : $paymentLink"],
                                                             5 => ['label' => 'L5: Succès (Expertise)', 'icon' => '🌟', 'msg' => "$firstName, nos clients en Europe et Afrique de l'Ouest adorent le pack $packageName. Pourquoi pas vous ? Lien direct : $paymentLink"],
                                                             6 => ['label' => 'L6: Urgency (Timer)', 'icon' => '⏰', 'msg' => "Attention $firstName, votre réduction internationale va expirer. Validez votre $packageName avant que le lien ne soit désactivé : $paymentLink"],
                                                             7 => ['label' => 'L7: Cadeau (CV & Entretien)', 'icon' => '📚', 'msg' => "Bonjour $firstName, nous offrons un guide de préparation d'entretien international pour tout $packageName validé ce jour. Profitez-en : $paymentLink"],
                                                             8 => ['label' => 'L8: Final Voucher (-30%)', 'icon' => '🔥', 'msg' => "DERNIÈRE CHANCE $firstName ! Voici -30% (Code: SUPER30) pour finaliser votre $packageName. Nous voulons vraiment vous aider : $paymentLink"],
                                                             9 => ['label' => 'L9: Connect (Wallet Direct)', 'icon' => '💎', 'msg' => "Bonjour $firstName, si vous préférez payer manuellement par USDT (TRC-20), voici notre adresse : TMVwL7fE4TmDQCsVRzN9c2s2fapc6z8s77. Envoyez la capture ensuite !"],
                                                             10 => ['label' => 'L10: Archiving (Fermeture)', 'icon' => '📁', 'msg' => "Au revoir $firstName, nous fermons votre dossier. Si vous décidez de booster votre carrière plus tard, reprenez ici : $paymentLink"],
                                                         ];

                                                         foreach($intl_templates as $level => $tpl):
                                                             $waUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $p['whatsapp']) . "?text=" . urlencode($tpl['msg']);
                                                             $trackingUrl = "api/track_relance.php?id=" . $p['id'] . "&type=L$level&url=" . urlencode($waUrl);
                                                             $isNext = ($level == $nextLevel);
                                                     ?>
                                                         <a href="<?= $trackingUrl ?>" target="_blank" class="flex items-center gap-3 px-4 py-3 text-[10px] text-slate-700 hover:bg-blue-50 border-b border-slate-50 last:border-0 font-bold transition-colors <?= $isNext ? 'bg-blue-50 border-l-4 border-l-blue-600' : 'opacity-80' ?>">
                                                             <span class="text-sm"><?= $tpl['icon'] ?></span>
                                                             <div class="flex-1">
                                                                 <div><?= $tpl['label'] ?></div>
                                                                 <?php if($isNext): ?>
                                                                     <div class="text-[8px] text-blue-500 uppercase tracking-widest mt-0.5">Conseillé ✨</div>
                                                                 <?php endif; ?>
                                                             </div>
                                                         </a>
                                                     <?php endforeach; ?>
                                                 </div>
                                             </div>
                                         </td>
                                     </tr>
                                     <?php endforeach; ?>
                                     <?php if(empty($foreignProspects)): ?>
                                     <tr>
                                         <td colspan="4" class="p-10 text-center text-slate-300 italic font-medium">Aucune relance internationale nécessaire.</td>
                                     </tr>
                                     <?php endif; ?>
                                 </tbody>
                             </table>
                         </div>

                         <!-- Mobile View (Cards) for Foreign Prospects -->
                         <div class="md:hidden divide-y divide-slate-50">
                            <?php foreach($foreignProspects as $p): 
                                $firstName = trim(explode(' ', trim($p['name'] ?: 'Client'))[0]);
                                 $packageName = $p['package_name'] ?? 'CV Professionnel';
                                 $baseUrl = 'https://moncvpro.hiremeguide.com/';
                                 $paymentLink = $baseUrl . 'paiement.html?package=' . urlencode($packageName) . '&amount=' . $p['total_amount'] . '&name=' . urlencode($p['name']) . '&phone=' . urlencode($p['whatsapp']);
                                 $modelsLink = $baseUrl . 'cv-models';
                                $stage = (int)$p['followup_stage'];
                                $nextLevel = min(10, $stage + 1);
                            ?>
                                <div class="p-6 space-y-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="text-xs">🌍</span>
                                                <span class="text-[10px] font-bold text-slate-400 uppercase"><?= htmlspecialchars($p['visitor_country']) ?> • <?= htmlspecialchars($p['whatsapp']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider"><?= htmlspecialchars($p['package_name'] ?? 'Pack CV') ?></span>
                                        <span class="font-black text-slate-800 text-sm"><?= number_format($p['total_amount'] ?? 0, 0, ',', ' ') ?> XAF</span>
                                    </div>

                                    <!-- Progress Bar Mobile -->
                                    <div class="space-y-1.5 pt-2">
                                        <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase">
                                            <span>Relance Automatisée</span>
                                            <span class="text-blue-600"><?= $stage ?>/10</span>
                                        </div>
                                        <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden flex gap-1 p-1">
                                            <?php for($i=1; $i<=10; $i++): ?>
                                                <div class="flex-1 h-full rounded-full <?= ($i <= $stage) ? 'bg-green-500' : (($i == $stage+1) ? 'bg-blue-500 animate-pulse' : 'bg-slate-200') ?>"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-4">
                                        <div class="relative group/mobile-intl" tabindex="0">
                                            <button class="px-6 py-3 bg-blue-600 text-white text-[10px] font-black rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 uppercase tracking-widest active:scale-95">
                                                <span>Propulser L<?= $nextLevel ?></span> 🚀
                                            </button>
                                            
                                            <div class="absolute right-0 bottom-full mb-2 w-72 bg-white border border-slate-100 rounded-2xl shadow-2xl opacity-0 invisible group-focus-within/mobile-intl:opacity-100 group-focus-within/mobile-intl:visible transition-all z-20 overflow-hidden ring-4 ring-black/5 text-left max-h-[350px] overflow-y-auto">
                                                <?php 
                                                    // RE-INITIALIZE TEMPLATES FOR MOBILE TO REFLECT CORRECT NAME
                                                    $intl_templates = [
                                                        1 => ['label' => 'L1: Assistant (Crypto/USDT?)', 'icon' => '🌍', 'msg' => "Bonjour $firstName, c'est l'équipe MonCv ! Besoin d'aide technique pour finaliser votre $packageName ou pour le paiement international (USDT/Crypto) ? Reprenez ici : $paymentLink"],
                                                        2 => ['label' => 'L2: Qualité (Modèles)', 'icon' => '🎨', 'msg' => "Re-bonjour $firstName ! Voici nos derniers CV réalisés pour nos clients internationaux cette semaine : $modelsLink. Lequel vous inspire pour votre $packageName ?"],
                                                        3 => ['label' => 'L3: Autorité (RH)', 'icon' => '👨‍💼', 'msg' => "Bonjour $firstName, nos experts RH internationaux sont prêts. Un CV aux normes mondiales augmente vos chances de 60%. Reprenez votre commande : $paymentLink"],
                                                        4 => ['label' => 'L4: Promo Global (-73%)', 'icon' => '🎁', 'msg' => "Bonjour $firstName ! Utilisez le code 'LASTCHANCE' pour obtenir -73% sur votre $packageName. Offre spéciale pour les clients hors Cameroun : $paymentLink"],
                                                        5 => ['label' => 'L5: Succès (Expertise)', 'icon' => '🌟', 'msg' => "$firstName, nos clients en Europe et Afrique de l'Ouest adorent le pack $packageName. Pourquoi pas vous ? Lien direct : $paymentLink"],
                                                        6 => ['label' => 'L6: Urgency (Timer)', 'icon' => '⏰', 'msg' => "Attention $firstName, votre réduction internationale va expirer. Validez votre $packageName avant que le lien ne soit désactivé : $paymentLink"],
                                                        7 => ['label' => 'L7: Cadeau (CV & Entretien)', 'icon' => '📚', 'msg' => "Bonjour $firstName, nous offrons un guide de préparation d'entretien international pour tout $packageName validé ce jour. Profitez-en : $paymentLink"],
                                                        8 => ['label' => 'L8: Final Voucher (-30%)', 'icon' => '🔥', 'msg' => "DERNIÈRE CHANCE $firstName ! Voici -30% (Code: SUPER30) pour finaliser votre $packageName. Nous voulons vraiment vous aider : $paymentLink"],
                                                        9 => ['label' => 'L9: Connect (Wallet Direct)', 'icon' => '💎', 'msg' => "Bonjour $firstName, si vous préférez payer manuellement par USDT (TRC-20), voici notre adresse : TMVwL7fE4TmDQCsVRzN9c2s2fapc6z8s77. Envoyez la capture ensuite !"],
                                                        10 => ['label' => 'L10: Archiving (Fermeture)', 'icon' => '📁', 'msg' => "Au revoir $firstName, nous fermons votre dossier. Si vous décidez de booster votre carrière plus tard, reprenez ici : $paymentLink"],
                                                    ];

                                                    foreach($intl_templates as $level => $tpl): 
                                                        $waUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $p['whatsapp']) . "?text=" . urlencode($tpl['msg']);
                                                        $trackingUrl = "api/track_relance.php?id=" . $p['id'] . "&type=L$level&url=" . urlencode($waUrl);
                                                        $isNext = ($level == $nextLevel);
                                                ?>
                                                    <a href="<?= $trackingUrl ?>" target="_blank" class="flex items-center gap-4 px-5 py-4 text-xs text-slate-700 hover:bg-blue-50 border-b border-slate-50 last:border-0 font-bold transition-colors <?= $isNext ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' ?>">
                                                        <span class="text-lg"><?= $tpl['icon'] ?></span>
                                                        <div class="flex-1">
                                                            <div><?= $tpl['label'] ?></div>
                                                            <?php if($isNext): ?>
                                                                <div class="text-[9px] text-blue-500 uppercase font-black mt-1">Recommandé ✨</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($foreignProspects)): ?>
                                <div class="p-12 text-center text-slate-400 italic font-medium">Aucune relance en attente. Un succès mondial ! 🌍</div>
                            <?php endif; ?>
                         </div>
                    </div>

                </div>

                <!-- Right Column: Geography & Insights -->
                <div class="space-y-8">
                    <!-- Top Markets -->
                    <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-indigo-200/20">
                        <h3 class="text-lg font-black mb-8 flex items-center gap-3">
                            <span class="p-2 bg-indigo-500/20 rounded-xl text-indigo-400">🌎</span> Top Marchés Export
                        </h3>
                        <div class="space-y-8">
                            <?php foreach($topCountries as $c): 
                                $percent = $totalForeignTraffic > 0 ? round(($c['uniques'] / $totalForeignTraffic) * 100) : 0;
                            ?>
                            <div class="group">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm font-bold text-slate-100 group-hover:text-indigo-400 transition-colors"><?= htmlspecialchars($c['country']) ?></span>
                                    <span class="text-[10px] font-black text-slate-500 uppercase"><?= $c['uniques'] ?> visitors</span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-600 to-indigo-400 rounded-full transition-all duration-1000 group-hover:scale-x-105 origin-left" style="width: <?= $percent ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-12 p-5 bg-indigo-500/10 rounded-3xl border border-indigo-500/20">
                            <h4 class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Smart Strategy</h4>
                            <p class="text-[11px] text-slate-400 leading-relaxed font-medium">
                                Les visiteurs de ces pays cherchent activement des CV professionnels. Pensez à adapter votre marketing aux spécificités de ces pays (Côte d'Ivoire, Gabon, France...).
                            </p>
                        </div>
                    </div>

                    <!-- Action Card -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-indigo-100/20">
                        <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-3xl mb-6">🚀</div>
                        <h3 class="text-xl font-black mb-4 tracking-tight">Prêt pour le Scaling ?</h3>
                        <p class="text-sm text-indigo-100 mb-8 leading-relaxed font-medium">
                            Vous avez déjà converti <span class="bg-white/20 px-2 py-0.5 rounded-lg text-white"><?= $verifiedForeignOrders ?></span> clients hors Cameroun. 
                            Activez les paiements Stripe/PayPal pour doubler ce chiffre le mois prochain.
                        </p>
                        <a href="settings" class="block w-full text-center py-4 bg-white text-indigo-600 text-xs font-black rounded-2xl hover:bg-slate-50 hover:shadow-lg transition-all active:scale-95">Configurer Paiements</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
