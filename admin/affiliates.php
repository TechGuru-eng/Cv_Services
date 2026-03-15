<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// --- Actions ---

// 0. Handle Redirect Messages
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'broadcast_success') $msg = "Message diffusé avec succès ! 🚀";
    if($_GET['msg'] == 'broadcast_partial') $msg = "Message diffusé (Emojis retirés pour compatibilité).";
}

// 1. Mark as Paid (Legacy Payout - kept for compatibility)
if (isset($_POST['payout_id'])) {
    $affId = $_POST['payout_id'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE affiliates SET balance = 0 WHERE id = ?")->execute([$affId]);
        $pdo->prepare("UPDATE affiliate_referrals SET status = 'paid' WHERE affiliate_id = ? AND status = 'verified'")->execute([$affId]);
        $pdo->commit();
        $msg = "Paiement enregistré avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

// 2. Handle Withdrawal Actions (Approve/Reject)
if (isset($_POST['withdrawal_id'])) {
    $wId = $_POST['withdrawal_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $pdo->beginTransaction();
    try {
        if ($status === 'rejected') {
            // Refund the balance if rejected
            $stmt = $pdo->prepare("SELECT affiliate_id, amount FROM withdrawals WHERE id = ?");
            $stmt->execute([$wId]);
            $w = $stmt->fetch();
            if ($w) {
                $pdo->prepare("UPDATE affiliates SET balance = balance + ? WHERE id = ?")->execute([$w['amount'], $w['affiliate_id']]);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE withdrawals SET status = ?, processed_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $wId]);
        
        $pdo->commit();
        $msg = "Demande de retrait mise à jour : " . ucfirst($status);
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

// 3. Handle Password Reset
if (isset($_POST['reset_password_id']) && !empty($_POST['new_password'])) {
    $affId = $_POST['reset_password_id'];
    $newPass = $_POST['new_password'];
    $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("UPDATE affiliates SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPass, $affId]);
        $msg = "Mot de passe mis à jour avec succès.";
    } catch (Exception $e) {
        $error = "Erreur modification mot de passe : " . $e->getMessage();
    }
}

    // 4. Handle Broadcast Message
    if(isset($_POST['action']) && $_POST['action'] === 'create_message') {
        $msgContent = trim($_POST['message']);
        if(!empty($msgContent)) {
            try {
                // Deactivate old messages
                $pdo->exec("UPDATE affiliate_messages SET is_active = 0");
                
                // Insert new message
                $stmt = $pdo->prepare("INSERT INTO affiliate_messages (message) VALUES (?)");
                $stmt->execute([$msgContent]);
                
                // Redirect to clear query params and prevent resubmission
                header("Location: affiliates.php?msg=broadcast_success");
                exit;
            } catch (PDOException $e) {
                // Fallback: If error relates to charset/emojis, try cleaning the string
                $cleanMsg = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $msgContent);
                try {
                    $stmt = $pdo->prepare("INSERT INTO affiliate_messages (message) VALUES (?)");
                    $stmt->execute([$cleanMsg]);
                    header("Location: affiliates.php?msg=broadcast_partial");
                    exit;
                } catch (PDOException $e2) {
                     $error = "Erreur lors de la diffusion : " . $e2->getMessage();
                }
            }
        }
    }

if (isset($_GET['delete_message'])) {
    try {
        $pdo->prepare("DELETE FROM affiliate_messages WHERE id = ?")->execute([$_GET['delete_message']]);
        $msg = "Message supprimé.";
    } catch (Exception $e) {
        $error = "Erreur suppression : " . $e->getMessage();
    }
}

if (isset($_GET['toggle_message'])) {
    try {
        $pdo->prepare("UPDATE affiliate_messages SET is_active = NOT is_active WHERE id = ?")->execute([$_GET['toggle_message']]);
        $msg = "Statut du message mis à jour.";
    } catch (Exception $e) {
        $error = "Erreur mise à jour : " . $e->getMessage();
    }
}


// --- Data Fetching ---

// 1. Total Overview
$totalAmbassadors = $pdo->query("SELECT COUNT(*) FROM affiliates")->fetchColumn();
$totalBalance = $pdo->query("SELECT SUM(balance) FROM affiliates")->fetchColumn() ?: 0;
$totalPaid = $pdo->query("SELECT SUM(total_earned) - SUM(balance) FROM affiliates")->fetchColumn() ?: 0;

// 2. Affiliate List
$affiliates = $pdo->query("SELECT * FROM affiliates ORDER BY total_earned DESC")->fetchAll();

// 3. Withdrawal Requests
$withdrawals = $pdo->query("SELECT w.*, a.name as affiliate_name, a.email as affiliate_email 
                            FROM withdrawals w 
                            JOIN affiliates a ON w.affiliate_id = a.id 
                            ORDER BY 
                                CASE WHEN w.status = 'pending' THEN 0 ELSE 1 END, 
                                w.created_at DESC")->fetchAll();

// 4. Messages
$messages = $pdo->query("SELECT * FROM affiliate_messages ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <header class="bg-white shadow p-4 md:hidden flex justify-between items-center">
                <h1 class="text-xl font-bold">Ambassadeurs</h1>
                <a href="logout.php" class="text-red-500 font-bold">Sortir</a>
            </header>

            <main class="p-6 md:p-10 flex-1 overflow-x-hidden overflow-y-auto">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-3xl font-bold">Programme Ambassadeur</h2>
                        <p class="text-gray-500">Gérez vos partenaires et leurs paiements (500 XAF / vente)</p>
                    </div>
                    <div class="bg-pink-100 text-pink-700 px-4 py-2 rounded-xl border border-pink-200 font-bold text-sm">
                        Équipage: <?= $totalAmbassadors ?> Ambassadeurs 🚀
                    </div>
                </div>

                <?php if(isset($msg)): ?>
                    <div class="bg-green-100 border border-green-200 text-green-700 p-4 rounded-xl mb-6 font-medium"><?= $msg ?></div>
                <?php endif; ?>
                <?php if(isset($error)): ?>
                    <div class="bg-red-100 border border-red-200 text-red-700 p-4 rounded-xl mb-6 font-medium"><?= $error ?></div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Dette Totale (À Payer)</p>
                        <p class="text-3xl font-black text-red-600"><?= number_format($totalBalance, 0, ',', ' ') ?> <span class="text-lg">FCFA</span></p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Déjà Payé</p>
                        <p class="text-3xl font-black text-green-600"><?= number_format($totalPaid, 0, ',', ' ') ?> <span class="text-lg">FCFA</span></p>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <p class="text-gray-400 text-xs font-black uppercase mb-1">Total Commissions</p>
                        <p class="text-3xl font-black text-gray-800"><?= number_format($totalPaid + $totalBalance, 0, ',', ' ') ?> <span class="text-lg text-gray-400">FCFA</span></p>
                    </div>
                </div>

                <!-- Message Broadcast Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-10">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-indigo-50">
                        <div class="font-bold text-indigo-900 flex items-center gap-2">
                            <span class="text-xl">📢</span> Diffusion Message Ambassadeurs
                        </div>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="flex gap-4 mb-8">
                            <input type="hidden" name="action" value="create_message">
                            <input type="text" name="message" required placeholder="Votre message ici (ex: Maintenance ce soir...)" class="flex-1 p-3 border rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
                            <button type="submit" class="bg-indigo-600 text-white font-bold px-6 py-3 rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                                Diffuser
                            </button>
                        </form>

                        <?php if(!empty($messages)): ?>
                        <div class="space-y-3">
                            <?php foreach($messages as $m): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100 group hover:border-indigo-100 transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="w-2 h-2 rounded-full <?= $m['is_active'] ? 'bg-green-500' : 'bg-gray-300' ?>"></div>
                                    <p class="font-medium text-gray-700 <?= !$m['is_active'] ? 'line-through opacity-50' : '' ?>"><?= htmlspecialchars($m['message']) ?></p>
                                    <span class="text-[10px] text-gray-400"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></span>
                                </div>
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="?toggle_message=<?= $m['id'] ?>" class="p-2 hover:bg-gray-200 rounded-lg text-gray-500" title="Activer/Désactiver">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </a>
                                    <a href="?delete_message=<?= $m['id'] ?>" class="p-2 hover:bg-red-100 rounded-lg text-red-500" onclick="return confirm('Supprimer ce message ?')" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Withdrawal Requests Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-10">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                        <div class="font-bold text-gray-700">Demandes de Retrait</div>
                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold"><?= count($withdrawals) ?> au total</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                                <tr>
                                    <th class="p-4">Ambassadeur</th>
                                    <th class="p-4">Montant</th>
                                    <th class="p-4">Méthode</th>
                                    <th class="p-4">Numéro</th>
                                    <th class="p-4">Date</th>
                                    <th class="p-4">Statut</th>
                                    <th class="p-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                <?php foreach($withdrawals as $w): ?>
                                <tr class="hover:bg-gray-50 transition-all <?= $w['status'] == 'pending' ? 'bg-blue-50/20' : '' ?>">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($w['affiliate_name']) ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($w['affiliate_email']) ?></div>
                                    </td>
                                    <td class="p-4 font-black"><?= number_format($w['amount'], 0, ',', ' ') ?> FCFA</td>
                                    <td class="p-4"><span class="bg-gray-100 px-2 py-0.5 rounded text-[10px] font-bold uppercase"><?= $w['method'] ?></span></td>
                                    <td class="p-4 font-mono font-bold"><?= htmlspecialchars($w['phone']) ?></td>
                                    <td class="p-4 text-gray-500 text-[10px]"><?= date('d/m/Y H:i', strtotime($w['created_at'])) ?></td>
                                    <td class="p-4">
                                        <?php if($w['status'] == 'pending'): ?>
                                            <span class="text-blue-600 font-bold bg-blue-50 px-2 py-1 rounded text-xs animate-pulse">En attente</span>
                                        <?php elseif($w['status'] == 'approved'): ?>
                                            <span class="text-green-600 font-bold bg-green-50 px-2 py-1 rounded text-xs">Approuvé ✅</span>
                                        <?php else: ?>
                                            <span class="text-red-600 font-bold bg-red-50 px-2 py-1 rounded text-xs">Rejeté ❌</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <?php if($w['status'] == 'pending'): ?>
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" onsubmit="return confirm('Approuver ce retrait ? Assurez-vous d\'avoir fait le transfert.')">
                                                    <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="bg-green-600 text-white text-[10px] font-bold px-3 py-1 rounded hover:bg-green-700 transition">Approuver</button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Rejeter ce retrait ? Le solde sera remboursé à l\'ambassadeur.')">
                                                    <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded hover:bg-red-600 transition">Rejeter</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-[10px] text-gray-300 italic">Traité le <?= date('d/m/Y', strtotime($w['processed_at'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($withdrawals)): ?>
                                    <tr><td colspan="7" class="p-10 text-center text-gray-400 italic">Aucune demande de retrait.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ambassador Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 font-bold text-gray-700">Liste des Ambassadeurs</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                                <tr>
                                    <th class="p-4">Ambassadeur</th>
                                    <th class="p-4">Code</th>
                                    <th class="p-4">Ventes</th>
                                    <th class="p-4">À Payer</th>
                                    <th class="p-4">Total Gagné</th>
                                    <th class="p-4">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach($affiliates as $a): ?>
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="p-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($a['name']) ?></div>
                                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($a['phone']) ?></div>
                                    </td>
                                    <td class="p-4">
                                        <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded font-mono text-xs font-bold"><?= $a['referral_code'] ?></span>
                                    </td>
                                    <td class="p-4 font-bold"><?= floor($a['total_earned'] / 500) ?></td>
                                    <td class="p-4">
                                        <?php if($a['balance'] > 0): ?>
                                            <span class="text-red-600 font-extrabold"><?= number_format($a['balance'], 0, ',', ' ') ?> FCFA</span>
                                        <?php else: ?>
                                            <span class="text-gray-300">0 FCFA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-gray-400 text-xs"><?= number_format($a['total_earned'], 0, ',', ' ') ?> FCFA</td>
                                    <td class="p-4">
                                        <?php if($a['balance'] > 0): ?>
                                            <form method="POST" onsubmit="return confirm('Confirmer le paiement de <?= $a['balance'] ?> FCFA à <?= addslashes($a['name']) ?> ?')">
                                                <input type="hidden" name="payout_id" value="<?= $a['id'] ?>">
                                                <button type="submit" class="bg-green-600 text-white text-[10px] font-bold px-3 py-1 rounded shadow hover:bg-green-700 transition">Marquer Payé</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-[10px] text-gray-300 italic font-bold">À jour</span>
                                        <?php endif; ?>
                                        
                                        <button onclick="resetPassword(<?= $a['id'] ?>, '<?= addslashes($a['name']) ?>')" class="mt-2 block w-full border border-gray-300 text-gray-600 text-[10px] font-bold px-3 py-1 rounded hover:bg-gray-100 transition">
                                            Reset Pass
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($affiliates)): ?>
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-400 italic">Aucun ambassadeur pour le moment.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Hidden Reset Form -->
    <div id="reset_form" method="POST" style="display:none;">
        <input type="hidden" name="reset_password_id" id="reset_pass_id">
        <input type="hidden" name="new_password" id="reset_pass_val">
    </div>

    <script>
        function resetPassword(id, name) {
            let newPass = prompt("Nouveau mot de passe pour " + name + " :");
            if (newPass) {
                // Determine if we need to use a form or ajax. using existing form structure.
                let form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                let idInput = document.createElement('input');
                idInput.name = 'reset_password_id';
                idInput.value = id;
                form.appendChild(idInput);
                
                let passInput = document.createElement('input');
                passInput.name = 'new_password';
                passInput.value = newPass;
                form.appendChild(passInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
