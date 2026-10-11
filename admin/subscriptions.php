<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Fetch Subscriptions
$query = "SELECT * FROM subscription_leads ORDER BY created_at DESC";
$leads = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$statusColors = [
    'new' => 'bg-blue-500/10 text-blue-600 border-blue-500/20',
    'contacted' => 'bg-amber-500/10 text-amber-600 border-amber-500/20',
    'active' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
    'cancelled' => 'bg-rose-500/10 text-rose-600 border-rose-500/20',
    'expired' => 'bg-slate-500/10 text-slate-500 border-slate-500/20'
];

$statusLabels = [
    'new' => 'Nouveau',
    'contacted' => 'Contacté',
    'active' => 'Actif',
    'cancelled' => 'Annulé',
    'expired' => 'Expiré'
];
?>
<?php include 'includes/header.php'; ?>
<body class="bg-[#fcfdfe] font-sans antialiased text-slate-900">
    <script src="https://unpkg.com/lucide@latest"></script>

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-10 custom-scrollbar">
            
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 pb-10 border-b border-slate-100">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                            <i data-lucide="zap" class="w-6 h-6"></i>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">HireMe Club Subscriptions</h1>
                    </div>
                    <p class="text-slate-400 font-medium text-xs ml-13">Gestion des abonnements mensuels et coaching CV.</p>
                </div>
            </div>

            <div class="mt-12 space-y-6">
                <?php if(empty($leads)): ?>
                    <div class="py-32 bg-white rounded-[3rem] text-center border-2 border-dashed border-slate-100">
                        <i data-lucide="users" class="w-16 h-16 text-slate-100 mx-auto mb-4"></i>
                        <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">Zéro abonnement pour le moment</p>
                    </div>
                <?php endif; ?>

                <?php foreach($leads as $l): 
                    $waLink = "https://wa.me/" . preg_replace('/[^0-9]/', '', $l['whatsapp']) . "?text=" . urlencode("Bonjour {$l['full_name']}, nous avons bien reçu votre demande d'adhésion au Club HireMe pour le {$l['plan']}...");
                ?>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm hover:shadow-xl transition-all border border-slate-100 flex flex-col lg:flex-row gap-6 items-center group">
                    
                    <!-- Basic Info -->
                    <div class="flex-1 min-w-[250px] space-y-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-black text-slate-900"><?= htmlspecialchars($l['full_name']) ?></h3>
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black rounded-full uppercase tracking-widest"><?= htmlspecialchars($l['plan']) ?></span>
                        </div>
                        <div class="flex items-center gap-4 text-[10px] text-slate-400 font-bold uppercase tracking-tighter">
                            <span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3 h-3"></i> <?= htmlspecialchars($l['whatsapp']) ?></span>
                            <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-3 h-3"></i> <?= date('d.m.Y', strtotime($l['created_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Career Context -->
                    <div class="flex-1 bg-slate-50 p-4 rounded-2xl border border-slate-100 flex gap-4">
                        <div class="flex-1">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Actuel</p>
                            <p class="text-[11px] font-bold text-slate-700"><?= htmlspecialchars($l['current_job'] ?: 'Non spécifié') ?></p>
                        </div>
                        <div class="flex-1 border-l border-slate-200 pl-4">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Cible</p>
                            <p class="text-[11px] font-bold text-slate-700"><?= htmlspecialchars($l['target_sector'] ?: 'Non spécifié') ?></p>
                        </div>
                    </div>

                    <!-- Status Selector -->
                    <div class="w-full lg:w-40">
                        <select onchange="updateSubStatus(<?= $l['id'] ?>, this.value)" 
                                class="w-full appearance-none px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all cursor-pointer <?= $statusColors[$l['status']] ?>">
                            <?php foreach($statusLabels as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $l['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Action -->
                    <a href="<?= $waLink ?>" target="_blank" class="h-12 w-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-200 hover:scale-110 active:scale-95 transition-all">
                        <i data-lucide="message-circle" class="w-6 h-6"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        async function updateSubStatus(id, newStatus) {
            try {
                const response = await fetch('api/update_subscription_lead.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status: newStatus })
                });
                const result = await response.json();
                if (result.success) location.reload();
                else alert(result.message);
            } catch (e) { alert('Erreur réseau'); }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #f1f5f9; border-radius: 10px; }
    </style>
</body>
</html>
