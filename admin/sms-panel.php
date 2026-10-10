<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once 'config/sms_helper.php';
require_once 'includes/activity_helper.php';

$message = '';

// Handle Relance SMS Trigger from Retargeting Tab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_relance'])) {
    $orderId = intval($_POST['order_id']);
    
    $stmtOrd = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmtOrd->execute([$orderId]);
    $order = $stmtOrd->fetch();
    
    if ($order) {
        $clientFirstName = explode(' ', trim($order['name']))[0];
        $orderRef = htmlspecialchars($order['order_ref'] ?? $order['id']);
        $amount = number_format($order['amount'], 0, ',', ' ');
        
        $smsText = "Bonjour $clientFirstName! Votre commande de CV de $amount FCFA est en attente. Suivez et validez votre paiement ici: https://moncvpro.hiremeguide.com/track-cv.php?ref=" . urlencode($orderRef) . "&src=sms";
        
        $res = SMSHelper::sendSMS($order['whatsapp'], $smsText);
        if ($res['success']) {
            log_activity("SMS Relance", "SMS de relance envoyé à " . $order['name']);
            $message = '<div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-2"><span>✓</span> Relance SMS envoyée avec succès à ' . htmlspecialchars($order['name']) . ' !</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-2"><span>✗</span> Échec de la relance : ' . htmlspecialchars($res['error']) . '</div>';
        }
    }
}

// Handle Relance SMS Trigger for Non-Purchasing Prospects / Abandoned Leads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_relance_prospect'])) {
    $prospectId = intval($_POST['prospect_id']);
    
    $stmtP = $pdo->prepare("SELECT * FROM prospects WHERE id = ? LIMIT 1");
    $stmtP->execute([$prospectId]);
    $prospect = $stmtP->fetch();
    
    if ($prospect && !empty($prospect['whatsapp'])) {
        $clientFirstName = explode(' ', trim($prospect['name']))[0];
        $packageName = $prospect['package_name'] ?? 'CV Professionnel';
        $amount = (float)($prospect['total_amount'] ?? 0);
        
        $paymentLink = "https://moncvpro.hiremeguide.com/paiement.html?package=" . urlencode($packageName) . "&amount=" . $amount . "&name=" . urlencode($prospect['name']) . "&whatsapp=" . urlencode($prospect['whatsapp']) . "&city=" . urlencode($prospect['city'] ?? '') . "&src=sms_relance";
        
        $smsText = "Bonjour $clientFirstName! Votre commande de $packageName chez HireMe est toujours en attente. Finalisez-la en 1 clic ici: $paymentLink";
        
        $res = SMSHelper::sendSMS($prospect['whatsapp'], $smsText);
        if ($res['success']) {
            $pdo->prepare("UPDATE prospects SET updated_at = NOW() WHERE id = ?")->execute([$prospectId]);
            log_activity("SMS Relance Prospect", "SMS de relance envoyé au prospect " . $prospect['name']);
            $message = '<div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-2"><span>✓</span> Relance SMS envoyée avec succès au prospect ' . htmlspecialchars($prospect['name']) . ' !</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-2xl mb-6 text-sm font-bold flex items-center gap-2"><span>✗</span> Échec de la relance : ' . htmlspecialchars($res['error']) . '</div>';
        }
    }
}

// 1. Fetch SMS Metrics
$totalSent = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE status IN ('success', 'delivered')")->fetchColumn();
$totalFailed = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'error'")->fetchColumn();
$totalClicked = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE clicked = 1")->fetchColumn();
$ctr = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 1) : 0;

// 2. Fetch Unpaid Orders for Retargeting Tab
$stmtUnpaid = $pdo->query("SELECT * FROM orders WHERE payment_status IN ('pending', 'pending_draft') ORDER BY id DESC");
$unpaidOrders = $stmtUnpaid->fetchAll(PDO::FETCH_ASSOC);

// Fetch Non-Purchasing Prospects (Visitors who entered details but didn't finish checkout)
$stmtProspects = $pdo->query("
    SELECT p.* 
    FROM prospects p
    LEFT JOIN orders o ON (p.whatsapp = o.whatsapp OR (p.email != '' AND p.email = o.email)) AND o.payment_status = 'verified'
    WHERE p.status = 'pending' AND o.id IS NULL AND p.whatsapp IS NOT NULL AND LENGTH(TRIM(p.whatsapp)) >= 8
    ORDER BY p.updated_at DESC 
    LIMIT 100
");
$pendingProspects = $stmtProspects->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Recent SMS Logs for Log Tab
$stmtLogs = $pdo->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 50");
$smsLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch Conversations List grouped by Recipient for Live Chat Tab
$stmtConvs = $pdo->query("
    SELECT s1.recipient, s1.message, s1.status, s1.created_at, s1.clicked
    FROM sms_logs s1
    INNER JOIN (
        SELECT recipient, MAX(id) as max_id
        FROM sms_logs
        GROUP BY recipient
    ) s2 ON s1.id = s2.max_id
    ORDER BY s1.id DESC
");
$conversations = $stmtConvs->fetchAll(PDO::FETCH_ASSOC);

// Map recipient phone numbers to candidate profiles
$cleanConvs = [];
foreach ($conversations as $conv) {
    $phone = $conv['recipient'];
    $cleanLocalPhone = preg_replace('/^\+237/', '', $phone);
    
    $stmtC = $pdo->prepare("SELECT name, order_ref FROM orders WHERE whatsapp = ? OR whatsapp = ? OR whatsapp LIKE ? LIMIT 1");
    $stmtC->execute([$phone, $cleanLocalPhone, "%" . $cleanLocalPhone]);
    $client = $stmtC->fetch(PDO::FETCH_ASSOC);
    
    $cleanConvs[] = [
        "phone" => $phone,
        "name" => $client ? $client['name'] : 'Client Anonyme',
        "order_ref" => $client ? $client['order_ref'] : '',
        "message" => $conv['message'],
        "status" => $conv['status'],
        "clicked" => $conv['clicked'],
        "time" => date('d/m H:i', strtotime($conv['created_at']))
    ];
}

include 'includes/header.php';
?>
<body class="bg-gray-50">
    <title>Super SMS Panel & Live Chat - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-8 flex flex-col h-screen">
            
            <!-- Panel Header -->
            <div class="flex justify-between items-center mb-6 shrink-0">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-3">
                        <span>📱</span> Super SMS Panel & Live Chat
                    </h1>
                    <p class="text-gray-500 mt-1">Gérez vos conversations SMS, suivez vos statistiques d'ouverture et relancez les prospects en direct.</p>
                </div>
            </div>

            <?= $message ?>

            <!-- Metrics grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 shrink-0">
                <!-- Sent Card -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-lg shrink-0">
                        ✓
                    </div>
                    <div>
                        <div class="text-xl font-black text-gray-800"><?= $totalSent ?></div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">SMS Envoyés</div>
                    </div>
                </div>

                <!-- Failed Card -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-lg shrink-0">
                        ✗
                    </div>
                    <div>
                        <div class="text-xl font-black text-gray-800"><?= $totalFailed ?></div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">SMS Échoués</div>
                    </div>
                </div>

                <!-- Clicked Card -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg shrink-0">
                        👁️
                    </div>
                    <div>
                        <div class="text-xl font-black text-gray-800"><?= $totalClicked ?></div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Liers Cliqués</div>
                    </div>
                </div>

                <!-- CTR Card -->
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                    <div class="w-10 h-10 bg-sky-50 text-sky-600 rounded-xl flex items-center justify-center text-lg shrink-0">
                        📈
                    </div>
                    <div>
                        <div class="text-xl font-black text-gray-800"><?= $ctr ?>%</div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Taux de Clic (CTR)</div>
                    </div>
                </div>
            </div>

            <!-- Custom Tabs Menu -->
            <div class="flex border-b border-gray-200 mb-6 shrink-0">
                <button onclick="switchTab('live-chat-tab')" id="btn-live-chat-tab" class="tab-btn py-3 px-6 font-bold text-sm border-b-2 border-sky-600 text-sky-600 focus:outline-none flex items-center gap-2 transition duration-200">
                    <span>💬</span> Discussions SMS Live
                </button>
                <button onclick="switchTab('retargeting-tab')" id="btn-retargeting-tab" class="tab-btn py-3 px-6 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none flex items-center gap-2 transition duration-200">
                    <span>🎯</span> Relances de Paiement
                </button>
                <button onclick="switchTab('logs-tab')" id="btn-logs-tab" class="tab-btn py-3 px-6 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 focus:outline-none flex items-center gap-2 transition duration-200">
                    <span>📋</span> Journal Historique
                </button>
            </div>

            <!-- Tab Contents Wrapper (grows to fill remaining screen space) -->
            <div class="flex-1 overflow-hidden relative min-h-[500px]">

                <!-- 1. LIVE CHAT TAB CONTENT -->
                <div id="live-chat-tab" class="tab-content absolute inset-0 flex flex-col lg:flex-row gap-6">
                    
                    <!-- Conversations Sidebar List -->
                    <div class="lg:w-1/3 bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
                        <div class="p-4 border-b border-gray-50">
                            <input type="text" id="contact-search" placeholder="Rechercher un contact..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2 text-xs focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        
                        <div class="flex-1 overflow-y-auto divide-y divide-gray-50" id="convs-list">
                            <?php if (empty($cleanConvs)): ?>
                                <div class="text-center py-12 text-gray-400 text-xs italic">
                                    Aucune discussion commencée.
                                </div>
                            <?php else: ?>
                                <?php foreach ($cleanConvs as $conv): ?>
                                    <div onclick="loadConversation('<?= htmlspecialchars($conv['phone']) ?>')" id="conv-item-<?= htmlspecialchars(preg_replace('/\+/','',$conv['phone'])) ?>" class="conv-item p-4 hover:bg-sky-50/50 cursor-pointer transition duration-200 flex justify-between gap-3 items-start border-l-4 border-transparent">
                                        <div class="shrink-0 w-8 h-8 rounded-full bg-sky-100 text-sky-700 font-black flex items-center justify-center text-xs uppercase">
                                            <?= substr(htmlspecialchars($conv['name']), 0, 1) ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-baseline mb-0.5">
                                                <h3 class="font-extrabold text-xs text-gray-800 truncate" title="<?= htmlspecialchars($conv['name']) ?>">
                                                    <?= htmlspecialchars($conv['name']) ?>
                                                </h3>
                                                <span class="text-[9px] text-gray-400 shrink-0 font-mono">
                                                    <?= $conv['time'] ?>
                                                </span>
                                            </div>
                                            <p class="text-[10px] text-gray-500 truncate" id="last-msg-<?= htmlspecialchars(preg_replace('/\+/','',$conv['phone'])) ?>">
                                                <?php if ($conv['status'] === 'received'): ?>
                                                    <span class="text-indigo-600 font-bold">📥 </span>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($conv['message']) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Window Panel -->
                    <div class="lg:w-2/3 bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden" id="chat-window-panel">
                        <!-- Empty State -->
                        <div id="chat-empty-state" class="flex-1 flex flex-col items-center justify-center p-8 text-center bg-gray-50">
                            <span class="text-5xl mb-4">💬</span>
                            <h3 class="font-black text-gray-700 text-sm">Discussions SMS en direct</h3>
                            <p class="text-xs text-gray-400 max-w-sm mt-1">Sélectionnez une discussion dans la liste de gauche pour lire l'historique et chatter en temps réel.</p>
                        </div>
                        
                        <!-- Real Chat Container (hidden on load) -->
                        <div id="chat-main-container" class="flex-1 flex flex-col h-full hidden overflow-hidden">
                            <!-- Chat Header -->
                            <div class="p-4 border-b border-gray-50 bg-white flex justify-between items-center shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-sky-100 text-sky-700 font-black rounded-full flex items-center justify-center text-sm uppercase" id="chat-header-avatar">
                                        C
                                    </div>
                                    <div>
                                        <h2 class="font-extrabold text-sm text-gray-800" id="chat-header-name">Nom du Client</h2>
                                        <p class="text-[10px] text-gray-400 font-mono" id="chat-header-phone">+237xxxxxxxxx</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Infinix Activé</span>
                                </div>
                            </div>
                            
                            <!-- Chat History Bubbles -->
                            <div class="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-4" id="chat-bubbles-container">
                                <!-- Dynamic Bubbles injected via JS -->
                            </div>
                            
                            <!-- Chat Input Footer -->
                            <div class="p-4 border-t border-gray-100 bg-white shrink-0">
                                <form id="chat-send-form" onsubmit="sendChatSMS(event)">
                                    <input type="hidden" id="chat-recipient-input" name="recipient" value="">
                                    <div class="flex gap-3">
                                        <div class="flex-1 relative">
                                            <textarea id="chat-message-input" name="message" placeholder="Écrire un message..." rows="2" maxlength="480" class="w-full border border-gray-200 rounded-2xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-sky-500 outline-none resize-none pr-16"></textarea>
                                            <span class="absolute bottom-2 right-4 text-[9px] font-bold text-gray-400" id="char-counter">0/160</span>
                                        </div>
                                        <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-bold px-5 rounded-2xl text-xs flex items-center justify-center shrink-0 shadow-sm transition">
                                            <span>📲 Envoyer</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 2. RETARGETING CAMPAIGN TAB CONTENT -->
                <div id="retargeting-tab" class="tab-content absolute inset-0 hidden overflow-y-auto">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <span>🎯</span> Campagnes de Retargeting & Relances SMS
                                </h2>
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">Relancez en 1 clic les clients ayant des commandes impayées ainsi que tous les visiteurs ayant rempli le formulaire sans finaliser l'achat.</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-bold">
                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1.5 rounded-full">Commandes : <?= count($unpaidOrders) ?></span>
                                <span class="bg-orange-100 text-orange-800 px-3 py-1.5 rounded-full">Prospects : <?= count($pendingProspects) ?></span>
                            </div>
                        </div>

                        <!-- 1. UNPAID ORDERS SECTION -->
                        <div class="mb-10">
                            <h3 class="text-sm font-extrabold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                                Commandes en attente de paiement (<?= count($unpaidOrders) ?>)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if (empty($unpaidOrders)): ?>
                                    <div class="col-span-full text-center py-6 bg-gray-50 rounded-2xl text-gray-400 text-xs italic">
                                        Aucune commande en attente de paiement. 🎉
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($unpaidOrders as $ord): ?>
                                        <div class="bg-gray-50 border border-gray-100 rounded-3xl p-5 flex flex-col justify-between gap-4 hover:border-sky-300 transition duration-300">
                                            <div class="space-y-2">
                                                <div class="font-extrabold text-gray-800 text-xs uppercase flex items-center justify-between">
                                                    <span><?= htmlspecialchars($ord['name']) ?></span>
                                                    <span class="text-[9px] bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-bold">
                                                        <?= htmlspecialchars($ord['payment_status']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    Package : <span class="font-bold text-gray-700"><?= htmlspecialchars($ord['package_name']) ?></span>
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    Montant dû : <span class="font-bold text-green-600"><?= number_format($ord['amount'], 0, ',', ' ') ?> FCFA</span>
                                                </div>
                                                <div class="text-[10px] text-gray-600 font-mono bg-white border border-gray-100 rounded-lg p-2">
                                                    📞 <?= htmlspecialchars($ord['whatsapp']) ?>
                                                </div>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                <button type="submit" name="trigger_relance" class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition shadow-sm">
                                                    📲 Relancer par SMS
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 2. NON-PURCHASING PROSPECTS / ABANDONED LEADS SECTION -->
                        <div>
                            <h3 class="text-sm font-extrabold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                                Prospects & Paniers Abandonnés (<?= count($pendingProspects) ?>)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if (empty($pendingProspects)): ?>
                                    <div class="col-span-full text-center py-6 bg-gray-50 rounded-2xl text-gray-400 text-xs italic">
                                        Aucun prospect en abandon de panier pour le moment.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($pendingProspects as $prospect): ?>
                                        <div class="bg-orange-50/60 border border-orange-100 rounded-3xl p-5 flex flex-col justify-between gap-4 hover:border-orange-300 transition duration-300">
                                            <div class="space-y-2">
                                                <div class="font-extrabold text-gray-800 text-xs uppercase flex items-center justify-between">
                                                    <span class="truncate"><?= htmlspecialchars($prospect['name']) ?></span>
                                                    <span class="text-[9px] bg-orange-200 text-orange-900 px-2 py-0.5 rounded-full font-black tracking-tight">
                                                        PROSPECT
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    Pack sélectionné : <span class="font-bold text-gray-700"><?= htmlspecialchars($prospect['package_name'] ?? 'CV Professionnel') ?></span>
                                                </div>
                                                <div class="text-[10px] text-gray-500">
                                                    Montant estimé : <span class="font-bold text-orange-700"><?= number_format((float)($prospect['total_amount'] ?? 0), 0, ',', ' ') ?> FCFA</span>
                                                </div>
                                                <div class="text-[10px] text-gray-600 font-mono bg-white border border-gray-100 rounded-lg p-2 flex justify-between items-center">
                                                    <span>📞 <?= htmlspecialchars($prospect['whatsapp']) ?></span>
                                                    <span class="text-[9px] text-gray-400 font-sans"><?= htmlspecialchars($prospect['city'] ?? '') ?></span>
                                                </div>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="prospect_id" value="<?= $prospect['id'] ?>">
                                                <button type="submit" name="trigger_relance_prospect" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition shadow-sm">
                                                    📲 Relancer ce Prospect
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. DETAILED LOGS HISTORY TAB CONTENT -->
                <div id="logs-tab" class="tab-content absolute inset-0 hidden overflow-y-auto">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <span>📋</span> Journal d'Envoi SMS complet
                        </h2>
                        <p class="text-xs text-gray-400 mb-6">Suivez l'historique complet de vos SMS avec TextBee en temps réel.</p>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 uppercase font-bold">
                                        <th class="pb-3 pl-2">Destinataire</th>
                                        <th class="pb-3">Message</th>
                                        <th class="pb-3 text-center">Statut</th>
                                        <th class="pb-3 text-center">Ouverture</th>
                                        <th class="pb-3 text-right pr-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($smsLogs)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-8 text-gray-400 italic">
                                                Aucun SMS envoyé pour le moment.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($smsLogs as $log): ?>
                                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition duration-150">
                                                <td class="py-3 pl-2 font-bold text-gray-700 whitespace-nowrap">
                                                    <?= htmlspecialchars($log['recipient']) ?>
                                                </td>
                                                <td class="py-3 max-w-xs truncate text-gray-500" title="<?= htmlspecialchars($log['message']) ?>">
                                                    <?= htmlspecialchars($log['message']) ?>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <?php if ($log['status'] === 'success'): ?>
                                                        <span class="bg-green-50 text-green-600 px-2 py-0.5 rounded-full text-[9px] font-bold">
                                                            Envoyé
                                                        </span>
                                                    <?php elseif ($log['status'] === 'delivered'): ?>
                                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[9px] font-black flex items-center gap-0.5 justify-center w-max mx-auto shadow-sm">
                                                            ✓ Délivré
                                                        </span>
                                                    <?php elseif ($log['status'] === 'received'): ?>
                                                        <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full text-[9px] font-black flex items-center gap-0.5 justify-center w-max mx-auto shadow-sm">
                                                            📥 Reçu
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[9px] font-bold" title="<?= htmlspecialchars($log['error_message'] ?? '') ?>">
                                                            Échec
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <?php if ($log['clicked'] == 1): ?>
                                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[9px] font-extrabold flex items-center gap-0.5 justify-center w-max mx-auto shadow-sm">
                                                            👁️ Cliqué
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full text-[9px] font-bold">
                                                            Non ouvert
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 text-right pr-2 text-gray-400 font-mono text-[10px] whitespace-nowrap">
                                                    <?= date('d/m H:i', strtotime($log['created_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Live Chat AJAX Handler JavaScript -->
    <script>
        let activeRecipient = '';
        let lastMessageCount = 0;
        let pollTimer = null;

        // Switch panel tabs
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tabId).classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-sky-600', 'text-sky-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('btn-' + tabId).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById('btn-' + tabId).classList.add('border-sky-600', 'text-sky-600');
        }

        // Search contact sidebar list
        document.getElementById('contact-search').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.conv-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(query)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });

        // SMS character counter logic
        document.getElementById('chat-message-input').addEventListener('input', function(e) {
            const length = e.target.value.length;
            const smsCount = Math.ceil(length / 160) || 1;
            document.getElementById('char-counter').textContent = `${length}/${smsCount * 160} (${smsCount} SMS)`;
        });

        // Load conversation detail dynamically
        function loadConversation(phone) {
            activeRecipient = phone;
            
            // Highlight list selection
            document.querySelectorAll('.conv-item').forEach(item => {
                item.classList.remove('bg-sky-50', 'border-sky-500');
            });
            const safePhoneId = phone.replace(/\+/g, '');
            const activeItem = document.getElementById('conv-item-' + safePhoneId);
            if (activeItem) {
                activeItem.classList.add('bg-sky-50', 'border-sky-500');
            }

            // Hide empty state and show tchat box
            document.getElementById('chat-empty-state').classList.add('hidden');
            document.getElementById('chat-main-container').classList.remove('hidden');
            
            // Set inputs values
            document.getElementById('chat-recipient-input').value = phone;

            // Fetch logs immediately
            fetchChatLogs(phone, true);

            // Re-initialize dynamic poll interval
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(() => {
                if (activeRecipient === phone) {
                    fetchChatLogs(phone, false);
                }
            }, 5000);
        }

        // Fetch logs from JSON API
        function fetchChatLogs(phone, shouldScroll = false) {
            fetch(`api/get-chat-messages.php?phone=${encodeURIComponent(phone)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update Header profile details
                        document.getElementById('chat-header-name').textContent = data.contact.name;
                        document.getElementById('chat-header-phone').textContent = data.contact.phone;
                        document.getElementById('chat-header-avatar').textContent = data.contact.name.substring(0, 1);
                        
                        // Check if new messages arrived
                        if (data.messages.length !== lastMessageCount || shouldScroll) {
                            renderChatBubbles(data.messages);
                            lastMessageCount = data.messages.length;
                            
                            // Scroll chat container to bottom
                            if (shouldScroll || data.messages.length > lastMessageCount) {
                                const container = document.getElementById('chat-bubbles-container');
                                container.scrollTop = container.scrollHeight;
                            }
                        }
                    }
                })
                .catch(err => console.error("Error loading chat logs:", err));
        }

        // Render chat bubbles dynamically in the window
        function renderChatBubbles(messages) {
            const container = document.getElementById('chat-bubbles-container');
            container.innerHTML = '';

            if (messages.length === 0) {
                container.innerHTML = `<div class="text-center text-gray-400 italic text-[10px] py-12">Aucun message échangé pour le moment.</div>`;
                return;
            }

            messages.forEach(msg => {
                const bubble = document.createElement('div');
                bubble.className = `flex flex-col ${msg.is_incoming ? 'items-start' : 'items-end'} w-full`;
                
                let statusBadge = '';
                if (!msg.is_incoming) {
                    if (msg.status === 'delivered') {
                        statusBadge = `<span class="text-green-600 font-black">✓✓</span>`;
                    } else if (msg.status === 'success') {
                        statusBadge = `<span class="text-green-500">✓</span>`;
                    } else if (msg.status === 'error') {
                        statusBadge = `<span class="text-red-500" title="${msg.error_message}">✗ Échec</span>`;
                    }
                    if (msg.clicked == 1) {
                        statusBadge = `<span class="text-blue-500 font-extrabold" title="Lien ouvert par le client">👁️ Lu</span>`;
                    }
                }

                bubble.innerHTML = `
                    <div class="max-w-[75%] rounded-2xl px-4 py-2.5 text-xs shadow-sm ${
                        msg.is_incoming 
                        ? 'bg-white text-gray-800 border border-gray-100 rounded-tl-none' 
                        : 'bg-sky-600 text-white rounded-tr-none'
                    }">
                        ${msg.message}
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 text-[9px] text-gray-400 font-mono ${msg.is_incoming ? 'ml-1' : 'mr-1'}">
                        <span>${msg.time}</span>
                        ${statusBadge}
                    </div>
                `;
                
                container.appendChild(bubble);
            });
        }

        // Send new SMS via AJAX
        function sendChatSMS(event) {
            event.preventDefault();
            const recipient = document.getElementById('chat-recipient-input').value;
            const message = document.getElementById('chat-message-input').value;

            if (!recipient || !message.trim()) return;

            // Clear input box immediately
            document.getElementById('chat-message-input').value = '';
            document.getElementById('char-counter').textContent = '0/160';

            const formData = new FormData();
            formData.append('recipient', recipient);
            formData.append('message', message);

            // Fetch to send API
            fetch('api/send-chat-sms.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update chat list immediately
                    fetchChatLogs(recipient, true);
                    
                    // Update latest message snippet in sidebar contact item
                    const safeId = recipient.replace(/\+/g, '');
                    const snippet = document.getElementById('last-msg-' + safeId);
                    if (snippet) {
                        snippet.textContent = message;
                    }
                } else {
                    alert("Erreur lors de l'envoi du message : " + data.error);
                }
            })
            .catch(err => {
                console.error("AJAX Error sending SMS:", err);
                alert("Une erreur de communication est survenue.");
            });
        }
    </script>
</body>
</html>
