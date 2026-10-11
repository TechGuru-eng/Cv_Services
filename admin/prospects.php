<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once __DIR__ . '/includes/prospect_helper.php';
importAbandonedProspects($pdo);
require_once 'includes/activity_helper.php';
require_once 'includes/promo_helper.php';

$activeSeasonPromo = get_active_season_promo();

// --- Actions ---
if (isset($_GET['action']) && $_GET['action'] === 'abandon' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE prospects SET status = 'abandoned' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Fetch prospect name for better logging
    $p_stmt = $pdo->prepare("SELECT name FROM prospects WHERE id = ?");
    $p_stmt->execute([$_GET['id']]);
    $p_name = $p_stmt->fetchColumn();
    
    log_activity("Prospect Abandonné", "Le prospect $p_name a été marqué comme abandonné.");
    $msg = "Prospect marqué comme abandonné.";
}

// --- Data Fetching ---
// Stats for metrics
$totalProspects = $pdo->query("SELECT COUNT(*) FROM prospects")->fetchColumn();
// Sync with paid orders first
require_once 'includes/sync_helper.php';
$convertedCount = syncProspectsWithOrders($pdo);
if ($convertedCount > 0) {
    // Optional: Set a message if needed, or just let it happen silently
    // $msg = "$convertedCount prospects convertis et retirés de la liste.";
}

// Only show pending prospects (the "Abandoned Carts")
// Re-fetch totals after sync
$convertedProspects = $pdo->query("SELECT COUNT(*) FROM prospects WHERE status = 'converted'")->fetchColumn();
$totalProspects = $pdo->query("SELECT COUNT(*) FROM prospects")->fetchColumn(); // Re-fetch
$conversionRate = ($totalProspects > 0) ? round(($convertedProspects / $totalProspects) * 100, 1) : 0;

$statusFilter = $_GET['status'] ?? 'active';
$statusClauses = ['active' => "status IN ('pending', 'lead_audit')", 'converted' => "status = 'converted'", 'abandoned' => "status = 'abandoned'", 'all' => '1=1'];
if (!isset($statusClauses[$statusFilter])) $statusFilter = 'active';
$prospects = $pdo->query("SELECT * FROM prospects WHERE " . $statusClauses[$statusFilter] . " ORDER BY updated_at DESC")->fetchAll();

$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM prospects WHERE status IN ('pending', 'lead_audit')")->fetchColumn();
$potentialRevenue = 0;
foreach($prospects as $p) if (in_array($p['status'], ['pending', 'lead_audit'])) $potentialRevenue += $p['total_amount'];

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <title>Paniers Abandonnés - HireMe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .animate-pulse-green { animation: pulse-green 2s infinite; }
        .stage-dot { width: 6px; height: 6px; border-radius: 50%; }
        .stage-active { background-color: #3b82f6; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
        .stage-passed { background-color: #10b981; }
        .stage-future { background-color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white border-b border-gray-100 p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
                <h1 class="text-xl font-black text-blue-600">HireMe <span class="text-orange-600">Admin</span></h1>
                <div class="bg-orange-50 text-orange-600 px-3 py-1 rounded-full text-[10px] font-black uppercase"><?= $totalPending ?> Prospects</div>
            </header>

            <main class="p-4 md:p-10 flex-1 overflow-x-hidden overflow-y-auto pb-32 md:pb-10">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-3xl font-bold">Relance de Panier 🛒</h2>
                        <p class="text-gray-500">Ils ont failli acheter. Donnez-leur le dernier coup de pouce !</p>
                    </div>
                </div>

                <?php if(isset($msg)): ?>
                    <div class="bg-blue-100 border border-blue-200 text-blue-700 p-4 rounded-xl mb-6 font-medium"><?= $msg ?></div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Prospects en attente</p>
                        <p class="text-3xl font-black text-orange-600"><?= $totalPending ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Taux de Conversion</p>
                        <p class="text-3xl font-black text-blue-600"><?= $conversionRate ?>%</p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Chiffre d'affaires à récupérer</p>
                        <p class="text-3xl font-black text-green-600"><?= number_format($potentialRevenue, 0, ',', ' ') ?> <span class="text-lg">XAF</span></p>
                    </div>
                </div>

                <form method="GET" class="mb-4 flex items-center gap-3">
                    <label for="prospect-status" class="text-sm font-bold">Afficher les prospects</label>
                    <select id="prospect-status" name="status" class="border rounded-lg p-2">
                        <?php foreach (['active' => 'À relancer (paniers + audits)', 'converted' => 'Convertis', 'abandoned' => 'Ignorés', 'all' => 'Tous les prospects'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="bg-blue-600 text-white rounded-lg px-3 py-2">Afficher</button>
                </form>
                <p class="text-xs text-gray-500 mb-4">Les niveaux suivent uniquement les relances WhatsApp ouvertes par un administrateur. Les emails automatiques sont suivis séparément.</p>
                <!-- Prospects Table (Desktop) / Cards (Mobile) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 font-bold text-gray-700 flex justify-between items-center">
                        <span>Flux des Prospects (Live)</span>
                        <span class="text-xs font-medium text-gray-400">Trier par : Décalage d'activité</span>
                    </div>

                    <!-- Table only visible on Medium+ screens -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                                <tr>
                                    <th class="p-4">Statut</th>
                                    <th class="p-4">Client</th>
                                    <th class="p-4">Pack Intéressé</th>
                                    <th class="p-4">Montant</th>
                                    <th class="p-4 text-center">Relances</th>
                                    <th class="p-4">Dernière activité</th>
                                    <th class="p-4 text-right">Relance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($prospects as $p): 
                                    $diff = time() - strtotime($p['updated_at']);
                                    $isHot = ($diff < 900); // 15 minutes
                                    $firstName = trim(explode(' ', trim($p['name'] ?: 'Client'))[0]);
                                    $packageName = $p['package_name'] ?? 'CV Professionnel';
                                    $baseUrl = "https://moncvpro.hiremeguide.com/";
                                    $paymentLink = $baseUrl . "paiement.html?package=" . urlencode($packageName) . "&amount=" . $p['total_amount'] . "&name=" . urlencode($p['name']) . "&whatsapp=" . urlencode($p['whatsapp']);
                                    $modelsLink = $baseUrl . "cv-models";
                                    $stage = (int)$p['manual_followup_stage'];
                                    $nextLevel = min(10, $stage + 1);
                                ?>
                                <tr class="hover:bg-gray-50 transition-all group">
                                    <td class="p-4 text-center">
                                        <?php if($isHot): ?>
                                            <div class="flex flex-col items-center">
                                                <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse-green mb-1"></span>
                                                <span class="text-[8px] font-black text-green-600 uppercase">HOT</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="w-2 h-2 bg-gray-200 rounded-full inline-block"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($p['whatsapp']) ?> • <?= htmlspecialchars($p['city'] ?? '') ?></div>
                                    </td>
                                    <td class="p-4">
                                        <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-[10px] font-bold"><?= htmlspecialchars($p['package_name']) ?></span>
                                    </td>
                                    <td class="p-4 font-black text-sm">
                                        <?= number_format($p['total_amount'], 0, ',', ' ') ?> FCFA
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-1.5 min-w-[100px]">
                                            <div class="flex justify-between items-center text-[8px] font-black uppercase text-gray-400">
                                                <span>Progression Relance</span>
                                                <span><?= $stage ?>/10</span>
                                            </div>
                                            <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden flex gap-0.5 p-0.5">
                                                <?php for($i=1; $i<=10; $i++): ?>
                                                    <div class="flex-1 h-full rounded-full <?= ($i <= $stage) ? 'bg-green-500' : (($i == $stage+1) ? 'bg-blue-500 animate-pulse' : 'bg-gray-200') ?>"></div>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php 
                                            $mins = floor($diff / 60);
                                            if ($mins < 1) echo '<div class="text-[10px] text-green-500 font-bold uppercase">À l\'instant</div>';
                                            elseif ($mins < 60) echo '<div class="text-[10px] text-gray-600 font-bold">Il y a ' . $mins . ' min</div>';
                                            else echo '<div class="text-[10px] text-gray-400">Il y a ' . floor($mins/60) . 'h ' . ($mins%60) . 'm</div>';
                                        ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-2 items-center">
                                            <a href="?action=abandon&id=<?= $p['id'] ?>" class="text-[10px] text-gray-300 hover:text-red-500 font-bold self-center mr-2">Ignorer</a>
                                            
                                            <div class="relative group/menu">
                                                <button class="bg-blue-600 text-white text-[10px] font-black px-4 py-2.5 rounded-full shadow-lg hover:bg-blue-700 transition-all flex items-center gap-2">
                                                    <span>Propulser L<?= $nextLevel ?></span> 🚀
                                                </button>
                                                <div class="absolute right-0 mt-2 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all z-20 overflow-hidden ring-4 ring-black/5 text-left">
                                                    <?php
                                                        $basePack = strtolower($p['package_name'] ?? '');
                                                        $mKey = null;
                                                        if (strpos($basePack, 'etudiant') !== false) $mKey = 'etudiant';
                                                        elseif (strpos($basePack, 'expert') !== false) $mKey = 'expert';
                                                        elseif (strpos($basePack, 'senior') !== false) $mKey = 'senior';
                                                        elseif (strpos($basePack, 'pro') !== false) $mKey = 'pro';

                                                        $l11Msg = "Bonjour $firstName ! Nous avons une nouvelle offre sensationnelle. Venez vite : $baseUrl";
                                                        if (!empty($activeSeasonPromo)) {
                                                            $pName = $activeSeasonPromo['name'];
                                                            $pLink = $activeSeasonPromo['link'];
                                                            $pPrice = ($mKey && isset($activeSeasonPromo['prices'][$mKey])) ? $activeSeasonPromo['prices'][$mKey] : null;

                                                            if ($pPrice && $pPrice < $p['total_amount']) {
                                                                $l11Msg = "Bonjour $firstName ! Bonne nouvelle : avec notre offre spéciale $pName, votre $packageName passe à " . number_format($pPrice, 0, ',', ' ') . " FCFA au lieu de " . number_format($p['total_amount'], 0, ',', ' ') . " FCFA ! Profitez-en aujourd'hui : $pLink";
                                                            } else {
                                                                $l11Msg = "Bonjour $firstName ! Célébrez $pName avec nos réductions exceptionnelles pour booster votre carrière et obtenir un CV qui attire l'attention ! C'est par ici : $pLink";
                                                            }
                                                        }

                                                        $all_templates = [
                                                            1 => ['label' => 'L1: Assistant (Besoins?)', 'icon' => '👋', 'msg' => "Bonjour $firstName, c'est l'équipe MonCv ! J'ai vu que vous hésitiez sur votre $packageName. Avez-vous besoin d'une aide technique pour finaliser ? Vite ici : $paymentLink"],
                                                            2 => ['label' => 'L2: Qualité (Modèles)', 'icon' => '🎨', 'msg' => "Re-bonjour $firstName ! Juste pour vous inspirer, voici nos dernières réalisations de CV cette semaine : $modelsLink. Lequel préférez-vous pour votre $packageName ?"],
                                                            3 => ['label' => 'L3: Autorité (RH)', 'icon' => '👨‍💼', 'msg' => "Bonjour $firstName, nos experts RH sont prêts à booster votre carrière. Saviez-vous qu'un CV optimisé augmente vos chances de 60% ? Reprenez ici : $paymentLink"],
                                                            4 => ['label' => 'L4: Promo (-73%)', 'icon' => '🎁', 'msg' => "Bonjour $firstName, cadeau ! Utilisez le code 'LASTCHANCE' pour obtenir -73% sur votre $packageName. Valable 24h seulement : $paymentLink"],
                                                            5 => ['label' => 'L5: Succès (Story)', 'icon' => '🌟', 'msg' => "$firstName, un client vient de décrocher un CDI avec son nouveau CV MonCv ! Voulez-vous être le suivant ? Votre panier vous attend : $paymentLink"],
                                                            6 => ['label' => 'L6: Urgency (Timer)', 'icon' => '⏰', 'msg' => "Attention $firstName, votre session promo va expirer. C'est le moment de valider votre $packageName avant la hausse des prix : $paymentLink"],
                                                            7 => ['label' => 'L7: Cadeau (Conseils)', 'icon' => '📚', 'msg' => "Bonjour $firstName, en plus de votre CV, nous avons préparé un guide de 5 conseils pour réussir vos entretiens. Débloquez-le avec votre commande : $paymentLink"],
                                                            8 => ['label' => 'L8: Voucher (-30%)', 'icon' => '🔥', 'msg' => "DERNIÈRE CHANCE $firstName ! Exceptionnellement, voici -30% (Code: SUPER30) pour vous aider à franchir le pas. C'est maintenant ou jamais : $paymentLink"],
                                                            9 => ['label' => 'L9: Connect (WhatsApp)', 'icon' => '📲', 'msg' => "$firstName, je suis NJAI, responsable client. Discutons en direct ici si vous avez la moindre question bloquante !"],
                                                            10 => ['label' => 'L10: Archive (Fermeture)', 'icon' => '📁', 'msg' => "Au revoir $firstName, nous archivons votre dossier $packageName. Si vous changez d'avis, ce lien restera actif 24h : $paymentLink. Bonne continuation !"],
                                                            11 => ['label' => 'L11: Promo Saisonnière', 'icon' => '🌙', 'msg' => $l11Msg],
                                                        ];

                                                        foreach($all_templates as $level => $tpl):
                                                            $waUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $p['whatsapp']) . "?text=" . urlencode($tpl['msg']);
                                                            $trackingUrl = "api/track_relance.php?id=" . $p['id'] . "&type=L$level&expected_stage=$stage&url=" . urlencode($waUrl);
                                                            $isNext = ($level == $nextLevel);
                                                    ?>
                                                        <a href="<?= $trackingUrl ?>" target="_blank" class="flex items-center gap-3 px-4 py-3 text-[10px] text-gray-700 hover:bg-blue-50 border-b border-gray-50 last:border-0 font-bold transition-colors <?= $isNext ? 'bg-blue-50 border-l-4 border-l-blue-600' : 'opacity-80' ?>">
                                                            <span class="text-xs"><?= $tpl['icon'] ?></span>
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
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($prospects)): ?>
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-gray-400 italic font-medium">Aucun panier abandonné pour le moment. Félicitations ! 🙌</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cards only visible on Mobile -->
                    <div class="md:hidden divide-y divide-gray-50">
                        <?php foreach($prospects as $p): 
                            $diff = time() - strtotime($p['updated_at']);
                            $firstName = trim(explode(' ', trim($p['name'] ?: 'Client'))[0]);
                            $packageName = $p['package_name'] ?? 'CV Professionnel';
                            $baseUrl = "https://moncvpro.hiremeguide.com/";
                            $paymentLink = $baseUrl . "paiement.html?package=" . urlencode($packageName) . "&amount=" . $p['total_amount'] . "&name=" . urlencode($p['name']) . "&whatsapp=" . urlencode($p['whatsapp']);
                            $modelsLink = $baseUrl . "cv-models";
                            $stage = (int)$p['manual_followup_stage'];
                            $nextLevel = min(10, $stage + 1);
                        ?>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($p['whatsapp']) ?> • <?= htmlspecialchars($p['city'] ?? '') ?></div>
                                </div>
                                <?php if($diff < 900): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-600 text-[10px] font-black rounded-lg animate-pulse">HOT 🔥</span>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg text-xs font-bold"><?= htmlspecialchars($p['package_name']) ?></span>
                                <span class="font-black text-gray-800"><?= number_format($p['total_amount'], 0, ',', ' ') ?> FCFA</span>
                            </div>

                            <!-- Progress Bar for Mobile -->
                            <div class="space-y-1.5 pt-2">
                                <div class="flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase">
                                    <span>Niveau de Relance</span>
                                    <span class="text-blue-600"><?= $stage ?>/10</span>
                                </div>
                                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden flex gap-1 p-1">
                                    <?php for($i=1; $i<=10; $i++): ?>
                                        <div class="flex-1 h-full rounded-full <?= ($i <= $stage) ? 'bg-green-500' : (($i == $stage+1) ? 'bg-blue-500 animate-pulse' : 'bg-gray-200') ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <a href="?action=abandon&id=<?= $p['id'] ?>" class="text-xs text-gray-400 font-bold underline">Ignorer</a>
                                
                                <!-- Mobile Trigger -->
                                <div class="relative group/mobile-menu" tabindex="0">
                                    <button class="bg-blue-600 text-white text-xs font-black px-6 py-3 rounded-xl shadow-lg shadow-blue-200 active:scale-95 transition-all flex items-center gap-2">
                                        <span>Propulser L<?= $nextLevel ?></span> 🚀
                                    </button>
                                    <!-- Mobile Dropdown (Slightly larger for touch) -->
                                    <div class="absolute right-0 bottom-full mb-2 w-72 bg-white border border-gray-100 rounded-2xl shadow-2xl opacity-0 invisible group-focus-within/mobile-menu:opacity-100 group-focus-within/mobile-menu:visible transition-all z-20 overflow-hidden ring-4 ring-black/5 text-left max-h-[350px] overflow-y-auto">
                                        <?php 
                                            // RE-INITIALIZE TEMPLATES FOR MOBILE TO REFLECT CORRECT NAME
                                            $basePack = strtolower($p['package_name'] ?? '');
                                            $mKey = null;
                                            if (strpos($basePack, 'etudiant') !== false) $mKey = 'etudiant';
                                            elseif (strpos($basePack, 'expert') !== false) $mKey = 'expert';
                                            elseif (strpos($basePack, 'senior') !== false) $mKey = 'senior';
                                            elseif (strpos($basePack, 'pro') !== false) $mKey = 'pro';

                                            $l11Msg = "Bonjour $firstName ! Nous avons une nouvelle offre sensationnelle. Venez vite : $baseUrl";
                                            if (!empty($activeSeasonPromo)) {
                                                $pName = $activeSeasonPromo['name'];
                                                $pLink = $activeSeasonPromo['link'];
                                                $pPrice = ($mKey && isset($activeSeasonPromo['prices'][$mKey])) ? $activeSeasonPromo['prices'][$mKey] : null;

                                                if ($pPrice && $pPrice < $p['total_amount']) {
                                                    $l11Msg = "Bonjour $firstName ! Bonne nouvelle : avec notre offre spéciale $pName, votre $packageName passe à " . number_format($pPrice, 0, ',', ' ') . " FCFA au lieu de " . number_format($p['total_amount'], 0, ',', ' ') . " FCFA ! Profitez-en aujourd'hui : $pLink";
                                                } else {
                                                    $l11Msg = "Bonjour $firstName ! Célébrez $pName avec nos réductions exceptionnelles pour booster votre carrière et obtenir un CV qui attire l'attention ! C'est par ici : $pLink";
                                                }
                                            }

                                            $all_templates = [
                                                1 => ['label' => 'L1: Assistant (Besoins?)', 'icon' => '👋', 'msg' => "Bonjour $firstName, c'est l'équipe MonCv ! J'ai vu que vous hésitiez sur votre $packageName. Avez-vous besoin d'une aide technique pour finaliser ? Vite ici : $paymentLink"],
                                                2 => ['label' => 'L2: Qualité (Modèles)', 'icon' => '🎨', 'msg' => "Re-bonjour $firstName ! Juste pour vous inspirer, voici nos dernières réalisations de CV cette semaine : $modelsLink. Lequel préférez-vous pour votre $packageName ?"],
                                                3 => ['label' => 'L3: Autorité (RH)', 'icon' => '👨‍💼', 'msg' => "Bonjour $firstName, nos experts RH sont prêts à booster votre carrière. Saviez-vous qu'un CV optimisé augmente vos chances de 60% ? Reprenez ici : $paymentLink"],
                                                4 => ['label' => 'L4: Promo (-73%)', 'icon' => '🎁', 'msg' => "Bonjour $firstName, cadeau ! Utilisez le code 'LASTCHANCE' pour obtenir -73% sur votre $packageName. Valable 24h seulement : $paymentLink"],
                                                5 => ['label' => 'L5: Succès (Story)', 'icon' => '🌟', 'msg' => "$firstName, un client vient de décrocher un CDI avec son nouveau CV MonCv ! Voulez-vous être le suivant ? Votre panier vous attend : $paymentLink"],
                                                6 => ['label' => 'L6: Urgency (Timer)', 'icon' => '⏰', 'msg' => "Attention $firstName, votre session promo va expirer. C'est le moment de valider votre $packageName avant la hausse des prix : $paymentLink"],
                                                7 => ['label' => 'L7: Cadeau (Conseils)', 'icon' => '📚', 'msg' => "Bonjour $firstName, en plus de votre CV, nous avons préparé un guide de 5 conseils pour réussir vos entretiens. Débloquez-le avec votre commande : $paymentLink"],
                                                8 => ['label' => 'L8: Voucher (-30%)', 'icon' => '🔥', 'msg' => "DERNIÈRE CHANCE $firstName ! Exceptionnellement, voici -30% (Code: SUPER30) pour vous aider à franchir le pas. C'est maintenant ou jamais : $paymentLink"],
                                                9 => ['label' => 'L9: Connect (WhatsApp)', 'icon' => '📲', 'msg' => "$firstName, je suis NJAI, responsable client. Discutons en direct ici si vous avez la moindre question bloquante !"],
                                                10 => ['label' => 'L10: Archive (Fermeture)', 'icon' => '📁', 'msg' => "Au revoir $firstName, nous archivons votre dossier $packageName. Si vous changez d'avis, ce lien restera actif 24h : $paymentLink. Bonne continuation !"],
                                                11 => ['label' => 'L11: Promo Saisonnière', 'icon' => '🌙', 'msg' => $l11Msg],
                                            ];

                                            foreach($all_templates as $level => $tpl): 
                                                $waUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $p['whatsapp']) . "?text=" . urlencode($tpl['msg']);
                                                $trackingUrl = "api/track_relance.php?id=" . $p['id'] . "&type=L$level&expected_stage=$stage&url=" . urlencode($waUrl);
                                                $isNext = ($level == $nextLevel);
                                        ?>
                                            <a href="<?= $trackingUrl ?>" target="_blank" class="flex items-center gap-4 px-5 py-4 text-xs text-gray-700 hover:bg-blue-50 border-b border-gray-50 last:border-0 font-bold transition-colors <?= $isNext ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' ?>">
                                                <span class="text-base"><?= $tpl['icon'] ?></span>
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
                        <?php if(empty($prospects)): ?>
                            <div class="p-10 text-center text-gray-400 italic font-medium">Aucun prospect pour le moment. Tout le monde paye ! 🙌</div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

</body>
</html>
