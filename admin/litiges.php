<?php
// admin/litiges.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/security_helper.php';

// --- Actions ---

// 1. Manual Ban
if (isset($_POST['action']) && $_POST['action'] === 'manual_ban') {
    $id = trim($_POST['identifier']);
    $type = (filter_var($id, FILTER_VALIDATE_EMAIL)) ? 'email' : 'phone';
    $reason = $_POST['reason'] ?? 'Ajout manuel via Dashboard';
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO blacklisted_clients (identifier, type, reason) VALUES (?, ?, ?)");
    $stmt->execute([$id, $type, $reason]);
    header('Location: litiges.php?msg=banned');
    exit;
}

// 2. Unban
if (isset($_GET['unban'])) {
    $id = intval($_GET['unban']);
    $pdo->prepare("DELETE FROM blacklisted_clients WHERE id = ?")->execute([$id]);
    header('Location: litiges.php?msg=unbanned');
    exit;
}

// 3. Mark as Paid (Resolve Litige)
if (isset($_GET['resolve'])) {
    $oid = intval($_GET['resolve']);
    $pdo->prepare("UPDATE orders SET is_non_payer = 0, payment_status = 'verified' WHERE id = ?")->execute([$oid]);
    header('Location: litiges.php?msg=resolved');
    exit;
}

// --- Data Fetching ---

// Stats
$totalBanned = $pdo->query("SELECT COUNT(*) FROM blacklisted_clients")->fetchColumn();
$activeLitigesCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE is_non_payer = 1")->fetchColumn();
$revenueLost = $pdo->query("SELECT SUM(amount) FROM orders WHERE is_non_payer = 1")->fetchColumn() ?: 0;

// Blacklist (Last 10)
$blacklist = $pdo->query("SELECT * FROM blacklisted_clients ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Active Litiges (Queue for Retargeting)
$litiges = $pdo->query("SELECT id, order_ref, name, whatsapp, package_name, amount, reminder_count, created_at, deletion_scheduled_at 
                         FROM orders 
                         WHERE is_non_payer = 1 
                         ORDER BY reminder_count ASC, created_at DESC")->fetchAll();

?>

<?php include 'includes/header.php'; ?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;900&display=swap');
    body { font-family: 'Outfit', sans-serif; }
    .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); }
    .dark-glass { background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .neon-border-red { box-shadow: 0 0 20px rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); }
    .neon-border-green { box-shadow: 0 0 20px rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); }
    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>

<body class="bg-slate-50 custom-scroll">
    <div class="flex flex-col md:flex-row h-screen overflow-hidden">
        
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 bg-slate-50">
            
            <!-- Hero Header -->
            <div class="relative rounded-[2.5rem] bg-slate-900 p-8 md:p-12 overflow-hidden mb-8 shadow-2xl shadow-slate-200">
                <div class="absolute top-0 right-0 w-64 h-64 bg-red-500/10 blur-[100px] rounded-full"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter uppercase italic leading-none mb-2">
                            War <span class="text-red-500">Room</span> ⚔️
                        </h1>
                        <p class="text-slate-400 font-medium text-sm md:text-base">Protégez votre business. Récupérez vos revenus.</p>
                    </div>
                    <button onclick="document.getElementById('ban-modal').classList.remove('hidden')" 
                            class="w-full md:w-auto bg-white text-slate-900 px-8 py-4 rounded-2xl font-black text-xs uppercase hover:scale-105 transition shadow-xl active:scale-95">
                        🚫 Bloquer un utilisateur
                    </button>
                </div>

                <!-- Stats Floating Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-12">
                    <div class="dark-glass rounded-3xl p-6 border-l-4 border-red-500">
                        <span class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em]">Blacklist</span>
                        <div class="text-3xl font-black text-white mt-1"><?= $totalBanned ?> <span class="text-xs font-normal text-slate-500 uppercase">Bannis</span></div>
                    </div>
                    <div class="dark-glass rounded-3xl p-6 border-l-4 border-emerald-500">
                        <span class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em]">Money at Risk</span>
                        <div class="text-3xl font-black text-emerald-400 mt-1"><?= number_format($revenueLost, 0, ',', ' ') ?> <span class="text-xs font-normal text-slate-500 uppercase">FCFA</span></div>
                    </div>
                    <div class="dark-glass rounded-3xl p-6 border-l-4 border-blue-500">
                        <span class="text-[10px] font-black uppercase text-slate-500 tracking-[0.2em]">Active Queue</span>
                        <div class="text-3xl font-black text-blue-400 mt-1"><?= $activeLitigesCount ?> <span class="text-xs font-normal text-slate-500 uppercase">Cibles</span></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Targets List (Retargeting) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="flex justify-between items-center px-4">
                        <h2 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter">Liste des Cibles <span class="text-red-500">(Impayés)</span></h2>
                        <span class="text-[10px] font-black bg-red-100 text-red-600 px-3 py-1 rounded-full uppercase">Relance Prioritaire</span>
                    </div>

                    <div class="space-y-4">
                        <?php foreach($litiges as $l): 
                            $level = min(5, $l['reminder_count'] + 1);
                            $waPhone = preg_replace('/[^0-9]/', '', $l['whatsapp']);
                            if(strlen($waPhone) == 9 && substr($waPhone, 0, 1) == '6') $waPhone = '237' . $waPhone;
                            
                            $firstName = explode(' ', trim($l['name']))[0];
                            $fmtAmount = number_format($l['amount'], 0, ',', ' ');
                            
                            $templates = [
                                1 => "Bonjour $firstName ! Votre CV est prêt. 📄 Il ne manque plus que le règlement de $fmtAmount FCFA pour la livraison. À très vite !",
                                2 => "Re-bonjour $firstName. Votre dossier est prêt ! ✨ Ne laissez pas votre carrière attendre pour seulement $fmtAmount FCFA. Un CV optimisé est votre meilleur investissement.",
                                3 => "Bonjour $firstName, Alerte Impayé. Votre CV sera SUPPRIMÉ prochainement si le paiement de $fmtAmount FCFA n'est pas reçu.",
                                4 => "Attention $firstName. Sans règlement de $fmtAmount FCFA sous 24h, nous devrons restreindre votre accès futur à nos services.",
                                5 => "DERNIER RAPPEL $firstName : Nous archivons votre dossier aujourd'hui. Le montant de $fmtAmount FCFA reste dû. C'est votre dernière chance."
                            ];
                            $waUrl = "https://wa.me/$waPhone?text=" . urlencode($templates[$level]);
                        ?>
                        <!-- Target Card -->
                        <div class="group bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 hover:shadow-xl hover:border-blue-100 transition-all duration-300">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">👤</div>
                                    <div>
                                        <div class="font-black text-slate-900 text-lg leading-none mb-1"><?= htmlspecialchars($l['name']) ?></div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black uppercase tracking-widest text-blue-600 bg-blue-50 px-2 py-0.5 rounded"><?= htmlspecialchars($l['package_name']) ?></span>
                                            <span class="text-[9px] font-bold text-slate-400 italic">Ref #<?= $l['order_ref'] ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="w-full md:w-auto flex flex-col md:items-center gap-1">
                                    <div class="flex gap-1 justify-center">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <div class="w-6 h-1.5 rounded-full <?= ($i <= $l['reminder_count']) ? 'bg-red-500' : (($i == $level) ? 'bg-blue-400 animate-pulse shadow-[0_0_10px_rgba(96,165,250,0.5)]' : 'bg-slate-100') ?>"></div>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-[10px] font-black uppercase text-slate-400 text-center">Niveau de relance L<?= $level ?></span>
                                </div>

                                <div class="w-full md:w-auto flex justify-between md:justify-end items-center gap-6">
                                    <div class="text-right">
                                        <div class="text-[10px] font-black text-slate-400 uppercase leading-none">Dû</div>
                                        <div class="text-xl font-black text-slate-900"><?= $fmtAmount ?> <span class="text-[10px]">FCFA</span></div>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="<?= $waUrl ?>" target="_blank" onclick="incrementReminder(<?= $l['id'] ?>)" 
                                           class="bg-[#25D366] text-white px-6 py-3 rounded-2xl font-black text-xs uppercase flex items-center gap-2 shadow-lg shadow-green-100 hover:scale-105 active:scale-95 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-6.912-11.975 1.701-26.241 15.688-26.241 8.528 0 15.467 6.938 15.467 15.466 0 8.527-6.939 15.466-15.468 15.466-4.088 0-7.947-1.585-10.957-4.469l-6.417 1.687zm9.648-9.982c-2.617-1.125-2.731-1.378-2.67-1.637.07-.3.738-1.527 1.258-1.848.514-.32.61-.267 1.053-.08.437.186 2.76.997 2.898 1.155.138.16.29.69.043 1.082-.246.392-.511.666-.889.85-.377.183-1.691.564-4.821-2.029-3.13-2.592-3.832-3.805-4.04-4.22-.208-.415-.06-.599.123-.781.183-.183.398-.291.603-.43.204-.138.384-.33.585-.544.199-.214.288-.358.418-.616.128-.258.07-.638-.179-1.23-.243-.591-1.31-3.155-1.742-3.486-.432-.33-.614-.234-.848-.052-.234.181-.782.529-1.157.94-.374.412-1.428 1.638-1.428 4.226 0 2.589 1.93 5.378 2.163 5.7.234.321 3.535 5.869 8.785 7.973 5.251 2.106 5.251 1.404 6.182 1.346.931-.059 3.016-1.23 3.447-2.433.432-1.203.432-2.228.307-2.433-.124-.204-.455-.325-.939-.567z"/></svg>
                                            Relance L<?= $level ?>
                                        </a>
                                        <a href="?resolve=<?= $l['id'] ?>" onclick="return confirm('Marquer comme payé ?')"
                                           class="bg-blue-600 text-white p-3 rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-100 flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($litiges)): ?>
                            <div class="bg-white rounded-[2.5rem] p-20 text-center border border-dashed border-slate-200">
                                <div class="text-5xl mb-4">🥂</div>
                                <h3 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter">Tout est sous contrôle !</h3>
                                <p class="text-slate-400 text-sm">Aucun impayé actif en attente de relance.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Side Panels -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Blacklist Quick View -->
                    <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm overflow-hidden relative">
                        <div class="absolute top-0 right-0 p-4 opacity-5">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.41L12 11.17l-4.59-4.58L6 8l6 6 6-6-1.41-1.41z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter mb-6">Zone de Bannissement 🛡️</h3>
                        
                        <div class="space-y-4 max-h-[400px] overflow-y-auto no-scrollbar pr-2">
                            <?php foreach($blacklist as $b): ?>
                                <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl group transition-all hover:bg-red-50">
                                    <div class="overflow-hidden">
                                        <div class="text-xs font-black text-slate-900 truncate"><?= htmlspecialchars($b['identifier']) ?></div>
                                        <div class="text-[9px] text-slate-400 uppercase font-bold"><?= $b['type'] ?> • <?= date('d M', strtotime($b['created_at'])) ?></div>
                                    </div>
                                    <a href="?unban=<?= $b['id'] ?>" onclick="return confirm('Réautoriser ?')" 
                                       class="bg-white text-red-600 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase shadow-sm opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 hover:text-white">
                                        Libérer
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="blacklist.php" class="block w-full text-center mt-6 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-blue-600 transition">Voir la liste complète</a>
                    </div>

                    <!-- Gold Rule Card -->
                    <div class="bg-gradient-to-br from-red-600 to-red-800 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-red-200 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 text-white opacity-10 text-8xl">💡</div>
                        <h3 class="text-2xl font-black italic tracking-tighter uppercase mb-4 leading-none">Règle <br> d'Or ⚡</h3>
                        <p class="text-sm text-red-50 leading-relaxed font-medium">
                            Un client banni est instantanément éjecté du système. Il ne pourra plus jamais commander ni réserver sur votre plateforme. 
                            <br><br>
                            <span class="font-black underline uppercase">Conseil :</span> Bannissez seulement après la **Relance L5** si aucune réponse n'est reçue sous 48h.
                        </p>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Modal Manual Ban (Premium Blur) -->
    <div id="ban-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-[3rem] p-8 md:p-12 max-w-md w-full shadow-2xl transform animate-in slide-in-from-bottom-10 duration-300">
            <div class="flex justify-between items-start mb-8">
                <h2 class="text-3xl font-black text-slate-900 italic tracking-tighter uppercase leading-none">Bannissement <br> Manuel <span class="text-red-500">🛡️</span></h2>
                <button onclick="document.getElementById('ban-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-900 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="manual_ban">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Identifiant (WA ou Email)</label>
                    <input type="text" name="identifier" required placeholder="6xxxxxx ou email@..." 
                           class="w-full bg-slate-50 border-2 border-transparent p-5 rounded-3xl focus:ring-0 focus:border-red-500 font-bold transition-all placeholder:text-slate-300">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Motif du blocage</label>
                    <textarea name="reason" rows="3" placeholder="Pourquoi bannir cet utilisateur ?" 
                              class="w-full bg-slate-50 border-2 border-transparent p-5 rounded-3xl focus:ring-0 focus:border-red-500 font-medium transition-all placeholder:text-slate-300"></textarea>
                </div>
                <button type="submit" class="w-full bg-red-600 text-white font-black py-5 rounded-3xl uppercase text-sm shadow-xl shadow-red-200 hover:scale-[1.02] active:scale-95 transition-all">
                    Confirmer le Bannissement
                </button>
            </form>
        </div>
    </div>

    <script>
        async function incrementReminder(orderId) {
            try {
                const formData = new FormData();
                formData.append('action', 'increment_reminder');
                formData.append('order_id', orderId);

                await fetch('api/manage_litige.php', { method: 'POST', body: formData });
                // Let the user switch to WhatsApp, then refresh after a short delay
                setTimeout(() => window.location.reload(), 3000);
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
