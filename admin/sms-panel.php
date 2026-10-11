<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once 'config/sms_helper.php';
require_once 'includes/activity_helper.php';

require_once __DIR__ . '/includes/prospect_helper.php';
importAbandonedProspects($pdo);
$audience = ($_GET['audience'] ?? 'orders') === 'prospects' ? 'prospects' : 'orders';
$noOrderProspects = prospectsWithoutOrders($pdo);
$_SESSION['sms_csrf'] ??= bin2hex(random_bytes(32));
$message = $_SESSION['sms_flash'] ?? '';
unset($_SESSION['sms_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sms_approval_action'])) {
    $result = ['success'=>false,'error'=>'Formulaire expiré.'];
    if (hash_equals($_SESSION['sms_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($_POST['sms_approval_action'] === 'approve') {
            $result = SMSHelper::approveSMS($requestId, (string)$_POST['csrf']);
        } elseif ($_POST['sms_approval_action'] === 'edit') {
            $result = SMSApproval::edit($pdo, $requestId, (string)$_POST['csrf'], (string)($_POST['message'] ?? ''));
        } elseif ($_POST['sms_approval_action'] === 'cancel') {
            $stmt=$pdo->prepare("UPDATE sms_approval_requests SET status='cancelled' WHERE id=? AND status='pending'");
            $stmt->execute([$requestId]);
            $result=['success'=>true,'message'=>'SMS annulé.'];
        }
        $_SESSION['sms_csrf'] = bin2hex(random_bytes(32));
    }
    $text = $result['success'] ? ($result['message'] ?? 'SMS accepté par TextBee. La livraison reste à confirmer.') : $result['error'];
    $_SESSION['sms_flash']='<div class="bg-white border rounded-xl p-4 mb-6">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</div>';
    header('Location: sms-panel'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_relance'])) {
    $recipient = null;
    $error = 'Destinataire non éligible à cette relance.';
    if (hash_equals($_SESSION['sms_csrf'], (string)($_POST['csrf'] ?? ''))) {
        if (($_POST['recipient_type'] ?? '') === 'prospect') {
            foreach ($noOrderProspects as $p) {
                if ((int)$p['id'] === (int)($_POST['prospect_id'] ?? 0)) { $recipient = $p; break; }
            }
            if ($recipient) {
                $firstName = explode(' ', trim($recipient['name']))[0];
                $smsText = "Bonjour $firstName! Vous n'avez pas finalisé votre commande de CV. Reprenez votre commande ici: https://moncvpro.hiremeguide.com/?src=sms";
            }
        } elseif (($_POST['recipient_type'] ?? '') === 'order') {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND payment_status IN ('pending', 'pending_draft') LIMIT 1");
            $stmt->execute([(int)($_POST['order_id'] ?? 0)]);
            $recipient = $stmt->fetch();
            if ($recipient) {
                $firstName = explode(' ', trim($recipient['name']))[0];
                $amount = number_format($recipient['amount'], 0, ',', ' ');
                $ref = $recipient['order_ref'] ?? $recipient['id'];
                $smsText = "Bonjour $firstName! Votre commande de CV de $amount FCFA est en attente. Suivez et validez votre paiement ici: https://moncvpro.hiremeguide.com/track-cv.php?ref=" . urlencode($ref) . "&src=sms";
            }
        }
        // Consume this form token before sending, so refreshing cannot send again.
        $_SESSION['sms_csrf'] = bin2hex(random_bytes(32));
        if ($recipient && strlen(prospectPhone($recipient['whatsapp'])) >= 8) {
            $res = SMSHelper::sendSMS($recipient['whatsapp'], $smsText);
            if ($res['success']) {
                log_activity('SMS Relance', 'SMS préparé pour ' . $recipient['name']);
                $_SESSION['sms_flash'] = '<div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6">Relance préparée pour ' . htmlspecialchars($recipient['name']) . '. Approuvez son envoi ci-dessous.</div>';
            } else { $error = $res['error'] ?? 'Échec de la passerelle SMS.'; }
        } elseif ($recipient) { $error = 'Numéro de téléphone manquant ou incomplet.'; }
    } else { $error = 'Formulaire expiré. Réessayez depuis cette page.'; }
    $_SESSION['sms_flash'] ??= '<div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6">' . htmlspecialchars($error) . '</div>';
    header('Location: sms-panel?audience=' . $audience);
    exit;
}

// 1. Fetch SMS Metrics
$totalSent = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE status IN ('success', 'delivered')")->fetchColumn();
$totalFailed = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'error'")->fetchColumn();
$totalClicked = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE clicked = 1")->fetchColumn();
$ctr = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 1) : 0;

// 2. Fetch Unpaid Orders for Retargeting
$stmtUnpaid = $pdo->query("SELECT * FROM orders WHERE payment_status IN ('pending', 'pending_draft') ORDER BY id DESC");
$unpaidOrders = $stmtUnpaid->fetchAll(PDO::FETCH_ASSOC);
$retargetingRecipients = $audience === 'prospects' ? $noOrderProspects : $unpaidOrders;


// 3. Fetch Recent SMS Logs
$stmtLogs = $pdo->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 50");
$smsLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
$smsUsed = SMSApproval::used($pdo);
$pendingSMS = $pdo->query("SELECT * FROM sms_approval_requests WHERE status='pending' ORDER BY id DESC")->fetchAll();
$approvalHistory = $pdo->query("SELECT * FROM sms_approval_requests WHERE status <> 'pending' ORDER BY id DESC LIMIT 20")->fetchAll();

include 'includes/header.php';
?>
<body class="bg-gray-50">
    <title>SMS Panel & Relances - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-10">

            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 flex items-center gap-3">
                        <span>📱</span> Super SMS Panel & Relances
                    </h1>
                    <p class="text-gray-500 mt-1">Gérez vos envois SMS TextBee, suivez vos performances d'ouverture et relancez les clients non payés.</p>
                </div>
            </div>

            <?= $message ?>

            <section class="bg-white rounded-3xl p-6 mb-8 border border-gray-200">
                <h2 class="text-xl font-bold">Relances SMS : modifier et approuver</h2>
                <p class="text-gray-600 mt-2">Les alertes de commande complète à l’administrateur et les confirmations au client après validation du paiement sont automatiques. Les relances et autres SMS exigent votre clic sur « Approuver et envoyer ». Les doublons et nouveaux essais automatiques après une tentative sont bloqués.</p>
                <p class="font-bold mt-3">Budget <?= htmlspecialchars(date('m/Y')) ?> : <?= $smsUsed ?> / 300 unités SMS réservées · <?= max(0,300-$smsUsed) ?> restantes</p>
                <p class="text-sm text-gray-500">Un message long peut compter plusieurs SMS. Chaque tentative approuvée est comptée, même si la passerelle échoue. Le compteur inclut les anciens envois du site ; vérifiez aussi votre consommation TextBee pour les envois externes.</p>
                <h3 class="font-bold mt-5 mb-3">À approuver (<?= count($pendingSMS) ?>)</h3>
                <?php if (!$pendingSMS): ?><p class="text-gray-500">Aucun SMS en attente.</p><?php endif; ?>
                <?php foreach ($pendingSMS as $sms): ?>
                    <div class="border rounded-xl p-4 mb-3">
                        <div class="font-bold"><?= htmlspecialchars($sms['recipient']) ?> · <?= (int)$sms['segments'] ?> unité(s) SMS</div>
                        <p class="whitespace-pre-wrap break-words my-3"><?= htmlspecialchars($sms['message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <form method="post" class="mb-4">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['sms_csrf']) ?>">
                            <input type="hidden" name="request_id" value="<?= (int)$sms['id'] ?>">
                            <label for="sms-edit-<?= (int)$sms['id'] ?>" class="block font-bold mb-2">Modifier le message</label>
                            <textarea id="sms-edit-<?= (int)$sms['id'] ?>" name="message" rows="5" maxlength="2000" required class="w-full border rounded-lg p-3"><?= htmlspecialchars($sms['message'], ENT_QUOTES, 'UTF-8') ?></textarea>
                            <p class="text-sm text-gray-500 my-2">Enregistrez vos modifications avant d’approuver. Le coût SMS sera recalculé. Conservez le lien de suivi si nécessaire.</p>
                            <button name="sms_approval_action" value="edit" class="bg-gray-100 rounded-lg px-4 py-2">Enregistrer sans envoyer</button>
                        </form>
                        <form method="post" class="flex gap-3">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['sms_csrf']) ?>">
                            <input type="hidden" name="request_id" value="<?= (int)$sms['id'] ?>">
                            <button name="sms_approval_action" value="approve" <?= $smsUsed + (int)$sms['segments'] > 300 ? 'disabled' : '' ?> class="bg-blue-600 text-white rounded-lg px-4 py-2 disabled:opacity-40">Approuver et envoyer</button>
                            <button name="sms_approval_action" value="cancel" class="bg-gray-100 rounded-lg px-4 py-2">Annuler</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if ($approvalHistory): ?>
                    <h3 class="font-bold mt-5">Dernières décisions</h3>
                    <?php foreach ($approvalHistory as $sms): ?>
                        <p class="text-sm border-b py-2"><?= htmlspecialchars($sms['recipient']) ?> — <?= htmlspecialchars(['accepted'=>'Accepté par TextBee (livraison à confirmer)','failed'=>'Échec — aucun nouvel essai automatique','sending'=>'Tentative enregistrée — résultat à vérifier','cancelled'=>'Annulé'][$sms['status']] ?? $sms['status']) ?> <?= htmlspecialchars($sms['error_message'] ?? '') ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Metrics grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Sent Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                        ✓
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-800"><?= $totalSent ?></div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">SMS Envoyés</div>
                    </div>
                </div>

                <!-- Failed Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                        ✗
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-800"><?= $totalFailed ?></div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">SMS Échoués</div>
                    </div>
                </div>

                <!-- Clicked Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                        👁️
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-800"><?= $totalClicked ?></div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Liers Cliqués</div>
                    </div>
                </div>

                <!-- CTR Card -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                        📈
                    </div>
                    <div>
                        <div class="text-2xl font-black text-gray-800"><?= $ctr ?>%</div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Taux de Clic (CTR)</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Relance Campaign Column -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <span>🎯</span> Retargeting & Relances
                        </h2>
                        <p class="text-xs text-gray-400 mb-6 leading-relaxed"><?= $audience === 'prospects' ? 'Visiteurs identifiés sans commande. Relancez-les pour terminer leur commande.' : 'Commandes enregistrées en attente de paiement. Envoyez leur lien de suivi.' ?></p>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <a href="?audience=orders" class="px-3 py-2 rounded-xl text-xs font-bold <?= $audience === 'orders' ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700' ?>">Déjà commandé (<?= count($unpaidOrders) ?>)</a>
                            <a href="?audience=prospects" class="px-3 py-2 rounded-xl text-xs font-bold <?= $audience === 'prospects' ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700' ?>">Sans commande (<?= count($noOrderProspects) ?>)</a>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Les visiteurs anonymes ne peuvent être relancés par SMS sans numéro de téléphone.</p>
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                            <?php if (empty($retargetingRecipients)): ?>
                                <div class="text-center py-8 text-gray-400 text-xs italic">
                                    Aucun destinataire pour cette audience.
                                </div>
                            <?php else: ?>
                                <?php foreach ($retargetingRecipients as $ord): ?>
                                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex flex-col justify-between gap-3 hover:border-sky-300 transition duration-300">
                                        <div class="space-y-1">
                                            <div class="font-extrabold text-gray-800 text-xs uppercase flex items-center justify-between">
                                                <span><?= htmlspecialchars($ord['name']) ?></span>
                                                <span class="text-[9px] bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full font-bold">
                                                    <?= htmlspecialchars($ord['payment_status'] ?? 'Sans commande') ?>
                                                </span>
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                <?= htmlspecialchars($ord['package_name']) ?> — <span class="font-bold text-green-600"><?= number_format($ord['amount'] ?? $ord['total_amount'] ?? 0, 0, ',', ' ') ?> FCFA</span>
                                            </div>
                                            <div class="text-[10px] text-gray-500 font-mono">
                                                📞 <?= htmlspecialchars($ord['whatsapp']) ?>
                                            </div>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['sms_csrf']) ?>">
                                            <input type="hidden" name="recipient_type" value="<?= $audience === 'prospects' ? 'prospect' : 'order' ?>">
                                            <input type="hidden" name="<?= $audience === 'prospects' ? 'prospect_id' : 'order_id' ?>" value="<?= (int)$ord['id'] ?>">
                                            <button type="submit" name="trigger_relance" <?= strlen(prospectPhone($ord['whatsapp'])) < 8 ? 'disabled title="Numéro manquant ou incomplet"' : '' ?> class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold py-2 rounded-xl text-[10px] uppercase tracking-wider transition shadow-sm">
                                                📲 Préparer une relance SMS
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Logs Column -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <span>📋</span> Journal d'Envoi SMS
                        </h2>
                        <p class="text-xs text-gray-400 mb-6">Suivez l'historique complet de vos SMS envoyés par votre passerelle Android Infinix en temps réel.</p>

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
</body>
</html>
