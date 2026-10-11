<?php
// admin/remboursements.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once 'includes/security_helper.php';

// --- Actions ---

// 1. Accept Refund & Ban
if (isset($_GET['accept'])) {
    $id = intval($_GET['accept']);
    
    // Get refund details
    $stmt = $pdo->prepare("SELECT * FROM refund_requests WHERE id = ?");
    $stmt->execute([$id]);
    $refund = $stmt->fetch();
    
    if ($refund) {
        // Update status
        $pdo->prepare("UPDATE refund_requests SET status = 'accepted' WHERE id = ?")->execute([$id]);
        
        // Ban the user
        $banReason = "Remboursement traité (Montant: " . $refund['amount'] . " FCFA)";
        $pdo->prepare("INSERT IGNORE INTO blacklisted_clients (identifier, type, reason) VALUES (?, 'email', ?)")->execute([$refund['email'], $banReason]);
        
        header('Location: remboursements.php?msg=accepted');
        exit;
    }
}

// 2. Reject Refund
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $pdo->prepare("UPDATE refund_requests SET status = 'rejected' WHERE id = ?")->execute([$id]);
    header('Location: remboursements.php?msg=rejected');
    exit;
}

// 3. Hide Refund (from public feed)
if (isset($_GET['hide'])) {
    $id = intval($_GET['hide']);
    $pdo->prepare("UPDATE refund_requests SET is_hidden = 1 WHERE id = ?")->execute([$id]);
    header('Location: remboursements.php?msg=hidden');
    exit;
}

// 4. Show Refund (on public feed)
if (isset($_GET['show'])) {
    $id = intval($_GET['show']);
    $pdo->prepare("UPDATE refund_requests SET is_hidden = 0 WHERE id = ?")->execute([$id]);
    header('Location: remboursements.php?msg=shown');
    exit;
}


// --- Data Fetching ---
$pending = $pdo->query("SELECT * FROM refund_requests WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$history = $pdo->query("SELECT * FROM refund_requests WHERE status != 'pending' ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Fetch Abandonments
$abandoned = $pdo->query("SELECT * FROM refund_abandoned WHERE status = 'abandoned' ORDER BY created_at DESC LIMIT 20")->fetchAll();

// Fetch Visits (Today)
$todayStarts = date('Y-m-d 00:00:00');
$visitsStmt = $pdo->prepare("SELECT COUNT(*) FROM stats_visits WHERE page_url LIKE '%remboursement.php%' AND created_at >= ?");
$visitsStmt->execute([$todayStarts]);
$todayVisits = $visitsStmt->fetchColumn();

// Fetch unique visitors (Today)
$uniqueVisitsStmt = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM stats_visits WHERE page_url LIKE '%remboursement.php%' AND created_at >= ?");
$uniqueVisitsStmt->execute([$todayStarts]);
$todayUniqueVisits = $uniqueVisitsStmt->fetchColumn();


$abandonMessage = urlencode("Salut, c'est l'équipe HireMe. J'ai constaté que vous avez entamé une demande de remboursement sans la terminer. Y a-t-il un souci avec lequel je peux vous aider ?");
?>

<?php include 'includes/header.php'; ?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap');
    body { font-family: 'Outfit', sans-serif; background-color: #0f172a; }
    .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .glass-card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); border-radius: 10px; }
    .custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.4); }
</style>

<body class="bg-slate-900 text-slate-200 custom-scroll min-h-screen">
    <div class="flex flex-col md:flex-row h-screen overflow-hidden">
        
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-y-auto p-4 md:p-8 bg-slate-900 relative">
            
            <!-- Ambient Glow -->
            <div class="fixed top-0 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>
            <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-rose-500/10 rounded-full blur-[120px] pointer-events-none z-0"></div>

            <div class="relative z-10 max-w-7xl mx-auto space-y-8">
                
                <!-- Premium Hero Header -->
                <div class="relative rounded-3xl bg-gradient-to-br from-slate-800 to-slate-900 p-8 md:p-10 overflow-hidden shadow-2xl border border-slate-700/50">
                    <div class="absolute -top-24 -right-24 w-64 h-64 bg-rose-500/20 blur-[80px] rounded-full"></div>
                    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-indigo-500/20 blur-[80px] rounded-full"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-bold uppercase tracking-wider mb-4">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Mode Litige
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight leading-tight mb-2 flex items-center gap-3">
                                Remboursements 💸
                            </h1>
                            <p class="text-slate-400 font-medium text-sm md:text-base max-w-xl">Gérez les insatisfactions clients, les demandes de remboursement et récupérez les prospects perdus.</p>
                        </div>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if(isset($_GET['msg'])): ?>
                    <?php if($_GET['msg'] == 'accepted'): ?>
                        <div class="glass-card border-emerald-500/20 text-emerald-400 p-4 rounded-2xl font-bold flex items-center gap-3 shadow-lg"><span class="text-2xl">✅</span> Remboursement accepté et client banni.</div>
                    <?php elseif($_GET['msg'] == 'rejected'): ?>
                        <div class="glass-card border-rose-500/20 text-rose-400 p-4 rounded-2xl font-bold flex items-center gap-3 shadow-lg"><span class="text-2xl">❌</span> Remboursement refusé. Le public verra ce refus.</div>
                    <?php elseif($_GET['msg'] == 'hidden'): ?>
                        <div class="glass-card border-slate-500/20 text-slate-300 p-4 rounded-2xl font-bold flex items-center gap-3 shadow-lg"><span class="text-2xl">👻</span> Demande masquée du flux public.</div>
                    <?php elseif($_GET['msg'] == 'shown'): ?>
                        <div class="glass-card border-blue-500/20 text-blue-400 p-4 rounded-2xl font-bold flex items-center gap-3 shadow-lg"><span class="text-2xl">👁️</span> Demande réaffichée sur le flux public.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Top Stats & Analytics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass-panel rounded-3xl p-6 hover:-translate-y-1 transition-transform duration-300 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><span class="text-blue-400">👁️</span> Visites (Aujourd'hui)</p>
                        <div class="flex items-end gap-3">
                            <p class="text-4xl font-black text-white"><?= number_format($todayVisits) ?></p>
                            <p class="text-sm font-medium text-slate-500 mb-1"><?= number_format($todayUniqueVisits) ?> uniques</p>
                        </div>
                    </div>
                    
                    <div class="glass-panel rounded-3xl p-6 hover:-translate-y-1 transition-transform duration-300 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><span class="text-rose-400">⚠️</span> En Attente</p>
                        <p class="text-4xl font-black text-white"><?= count($pending) ?></p>
                    </div>

                    <div class="glass-panel rounded-3xl p-6 hover:-translate-y-1 transition-transform duration-300 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 flex items-center gap-2"><span class="text-amber-400">🏃</span> Abandons</p>
                        <p class="text-4xl font-black text-white"><?= count($abandoned) ?></p>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Pending Requests -->
                    <div class="xl:col-span-2 space-y-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="p-2 bg-slate-800 rounded-xl text-orange-400 shadow-inner">⏳</span> 
                            Demandes à traiter
                        </h2>
                        
                        <?php if (empty($pending)): ?>
                            <div class="glass-panel rounded-3xl p-12 text-center border border-dashed border-slate-700">
                                <div class="text-5xl mb-4 opacity-50 grayscale">🎉</div>
                                <p class="text-slate-400 font-medium">Aucune demande de remboursement en attente.</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php foreach($pending as $req): ?>
                                    <div class="glass-panel rounded-3xl p-6 flex flex-col justify-between hover:border-slate-600 transition-colors duration-300">
                                        <div>
                                            <div class="flex justify-between items-start mb-5">
                                                <div>
                                                    <h3 class="font-bold text-white text-lg"><?= htmlspecialchars($req['name']) ?></h3>
                                                    <p class="text-[11px] text-slate-400 font-mono mt-1 bg-slate-800/50 inline-block px-2 py-0.5 rounded"><?= htmlspecialchars($req['email']) ?></p>
                                                </div>
                                                <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-3 py-1.5 rounded-xl text-xs font-black tracking-wide whitespace-nowrap shadow-sm">
                                                    <?= number_format($req['amount'], 0, ',', ' ') ?> F
                                                </div>
                                            </div>
                                            
                                            <div class="bg-slate-800/50 rounded-2xl p-4 mb-4 text-sm text-slate-300 h-28 overflow-y-auto custom-scroll border border-slate-700/50">
                                                <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500 mb-1 block">Motif d'insatisfaction</span>
                                                <?= nl2br(htmlspecialchars($req['reason'])) ?>
                                            </div>

                                            <?php if($req['proof_path']): ?>
                                                <a href="../<?= htmlspecialchars($req['proof_path']) ?>" target="_blank" class="inline-flex items-center gap-2 text-indigo-400 text-xs font-bold hover:text-indigo-300 transition-colors mb-5 bg-indigo-500/10 px-3 py-1.5 rounded-lg border border-indigo-500/20">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                                    Voir la Preuve
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex gap-3 border-t border-slate-700/50 pt-5 mt-2">
                                            <a href="remboursements.php?accept=<?= $req['id'] ?>" onclick="return confirm('Accepter le remboursement va BANNIR ce client. Continuer ?')" class="flex-1 text-center bg-rose-600 text-white font-bold py-2.5 rounded-xl text-xs hover:bg-rose-500 transition-colors shadow-lg shadow-rose-500/20">
                                                Rembourser & Bannir
                                            </a>
                                            <a href="remboursements.php?reject=<?= $req['id'] ?>" onclick="return confirm('Refuser cette demande ? (Cela sera visible publiquement)')" class="flex-1 text-center bg-slate-800 text-slate-300 font-bold py-2.5 rounded-xl text-xs hover:bg-slate-700 border border-slate-700 transition-colors">
                                                Refuser
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- History Section -->
                        <div class="mt-12">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3 mb-6">
                                <span class="p-2 bg-slate-800 rounded-xl text-blue-400 shadow-inner">📜</span> 
                                Historique & Visibilité
                            </h2>
                            
                            <div class="glass-panel rounded-3xl p-1 shadow-lg overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="text-[11px] uppercase tracking-widest text-slate-400 border-b border-slate-700/50 bg-slate-800/30">
                                            <th class="p-4 font-bold rounded-tl-2xl">Date</th>
                                            <th class="p-4 font-bold">Client</th>
                                            <th class="p-4 font-bold">Montant</th>
                                            <th class="p-4 font-bold">Statut</th>
                                            <th class="p-4 font-bold rounded-tr-2xl text-right">Action Public</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        <?php foreach($history as $h): ?>
                                            <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-colors group">
                                                <td class="p-4 text-slate-500 text-xs font-medium"><?= date('d/m/y H:i', strtotime($h['created_at'])) ?></td>
                                                <td class="p-4">
                                                    <div class="font-bold text-slate-200"><?= htmlspecialchars($h['name']) ?></div>
                                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5"><?= htmlspecialchars($h['email']) ?></div>
                                                </td>
                                                <td class="p-4 font-black text-slate-300"><?= number_format($h['amount'], 0, ',', ' ') ?> F</td>
                                                <td class="p-4">
                                                    <?php if($h['status'] == 'accepted'): ?>
                                                        <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-lg text-xs font-bold inline-block">Accepté (Banni)</span>
                                                    <?php else: ?>
                                                        <span class="bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2.5 py-1 rounded-lg text-xs font-bold inline-block">Refusé</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="p-4 text-right">
                                                    <?php if($h['is_hidden']): ?>
                                                        <a href="remboursements.php?show=<?= $h['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 text-slate-400 hover:text-white rounded-lg text-xs font-bold transition-colors opacity-50 hover:opacity-100" title="Actuellement caché. Cliquer pour afficher.">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                            Caché
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="remboursements.php?hide=<?= $h['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-500/10 text-blue-400 hover:bg-rose-500/10 hover:text-rose-400 rounded-lg text-xs font-bold transition-colors" title="Visible publiquement. Cliquer pour cacher.">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                                                            Cacher
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Abandonments -->
                    <div class="xl:col-span-1 space-y-6">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <span class="p-2 bg-slate-800 rounded-xl text-amber-400 shadow-inner">🏃</span> 
                            Abandons & Relance
                        </h2>
                        
                        <div class="glass-panel rounded-3xl p-6">
                            <p class="text-xs text-slate-400 mb-6 leading-relaxed">
                                Ces personnes ont cliqué sur "Chercher" pour voir leurs commandes éligibles mais n'ont pas soumis la demande de remboursement. Relancez-les !
                            </p>
                            
                            <?php if (empty($abandoned)): ?>
                                <div class="text-center py-8">
                                    <span class="text-3xl opacity-30 grayscale block mb-2">👻</span>
                                    <span class="text-xs font-bold text-slate-500 uppercase">Aucun abandon</span>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach($abandoned as $a): 
                                        $isWhatsApp = preg_match('/^[0-9\+\s]+$/', $a['identifier']);
                                        $waLink = null;
                                        $foundWa = null;
                                        
                                        if ($isWhatsApp) {
                                            $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $a['identifier']) . "?text=" . $abandonMessage;
                                        } else {
                                            // Look up WhatsApp number in orders table based on email
                                            $stmtOrder = $pdo->prepare("SELECT whatsapp FROM orders WHERE email = ? AND whatsapp IS NOT NULL AND whatsapp != '' ORDER BY id DESC LIMIT 1");
                                            $stmtOrder->execute([$a['identifier']]);
                                            $foundWa = $stmtOrder->fetchColumn();
                                            if ($foundWa) {
                                                $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $foundWa) . "?text=" . $abandonMessage;
                                            }
                                        }
                                    ?>
                                        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-4 flex flex-col gap-3 group hover:border-slate-600 transition-colors">
                                            <div class="flex justify-between items-center">
                                                <div class="truncate pr-2">
                                                    <p class="text-sm font-bold text-slate-200 truncate"><?= htmlspecialchars($a['identifier']) ?></p>
                                                    <p class="text-[10px] text-slate-500 mt-0.5"><?= date('d/m à H:i', strtotime($a['created_at'])) ?></p>
                                                </div>
                                            </div>
                                            
                                            <?php if($waLink): ?>
                                                <?php if(!$isWhatsApp && $foundWa): ?>
                                                    <div class="text-[10px] text-emerald-400 font-bold bg-emerald-500/10 px-2 py-1 rounded inline-block w-fit mb-1">WhatsApp trouvé en BD : <?= htmlspecialchars($foundWa) ?></div>
                                                <?php endif; ?>
                                                <a href="<?= $waLink ?>" target="_blank" class="w-full bg-[#25D366]/10 text-[#25D366] hover:bg-[#25D366] hover:text-white border border-[#25D366]/20 py-2 rounded-xl text-xs font-bold text-center transition-all flex items-center justify-center gap-2">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                    Relancer via WhatsApp
                                                </a>
                                            <?php else: ?>
                                                <div class="w-full bg-slate-900/50 text-slate-500 py-2 rounded-xl text-xs font-bold text-center border border-slate-800">
                                                    Email uniquement (Aucun WhatsApp trouvé)
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
            <br><br>
        </main>
    </div>
</body>
</html>
