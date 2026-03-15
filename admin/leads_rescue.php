<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
include 'includes/header.php';

// Fetch Hot Leads (Pending prospects)
$stmt = $pdo->query("SELECT * FROM prospects WHERE status = 'pending' ORDER BY created_at DESC LIMIT 100");
$leads = $stmt->fetchAll();
?>
<body class="bg-[#0f172a] font-sans text-slate-200 overflow-hidden">
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <!-- Header -->
            <header class="bg-slate-900/50 backdrop-blur-md border-b border-white/5 p-6 flex justify-between items-center z-20">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-emerald-500/20 text-emerald-400 rounded-2xl">
                        <i data-lucide="life-buoy" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-white flex items-center gap-2">Leads Rescue <span class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full font-bold uppercase tracking-widest">Hot Leads</span></h2>
                        <p class="text-xs font-medium text-slate-400">Récupération des paniers abandonnés</p>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-8 space-y-8 bg-slate-950">
                
                <!-- Lead Grid -->
                <div class="bg-slate-900 rounded-[2.5rem] border border-white/5 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-800/50 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                            <tr>
                                <th class="p-6">Prospect</th>
                                <th class="p-6">Détails Pack</th>
                                <th class="p-6">Source / Pays</th>
                                <th class="p-6">Date</th>
                                <th class="p-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($leads)): ?>
                            <tr>
                                <td colspan="5" class="p-12 text-center text-slate-600 italic">Aucun panier abandonné pour le moment. 🎉</td>
                            </tr>
                            <?php endif; ?>

                            <?php foreach ($leads as $l): ?>
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-slate-800 border border-white/10 flex items-center justify-center font-black text-slate-400 group-hover:text-emerald-400 transition-colors uppercase">
                                            <?= substr($l['name'], 0, 2) ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-black text-white"><?= htmlspecialchars($l['name']) ?></p>
                                            <p class="text-[9px] text-slate-500 font-bold tracking-tighter"><?= htmlspecialchars($l['whatsapp']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <span class="text-[10px] font-black text-blue-400 bg-blue-500/10 px-3 py-1 rounded-full uppercase">
                                        <?= htmlspecialchars($l['package_name']) ?>
                                    </span>
                                    <p class="text-[10px] text-slate-500 mt-1 font-bold"><?= number_format($l['total_amount'], 0, ',', ' ') ?> XAF</p>
                                </td>
                                <td class="p-6">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-[10px] font-black text-slate-400"><?= htmlspecialchars($l['country'] ?? 'Inconnu') ?></span>
                                        <span class="text-[9px] font-bold text-slate-600 uppercase tracking-widest"><?= htmlspecialchars($l['source'] ?? 'Direct') ?></span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <span class="text-[10px] font-black text-slate-500"><?= date('d/m H:i', strtotime($l['created_at'])) ?></span>
                                </td>
                                <td class="p-6 text-right">
                                    <?php
                                        $firstName = explode(' ', $l['name'])[0];
                                        $message = "Bonjour $firstName ! C'est Sophia de HireMe. J'ai vu que vous étiez intéressé par notre {$l['package_name']} pour votre CV. Souhaitez-vous un coup de pouce pour finaliser ?";
                                        $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $l['whatsapp']) . "?text=" . urlencode($message);
                                    ?>
                                    <a href="<?= $waLink ?>" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black px-4 py-2 rounded-xl transition-all transform hover:scale-105">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                        RELANCER
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
