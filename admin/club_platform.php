<?php
require_once 'config/db.php';
session_start();
if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit; }

$activeTab = $_GET['tab'] ?? 'payments';

// Pending payments with member info
$payments = $pdo->query("SELECT p.*, m.full_name, m.whatsapp, m.plan_id
                         FROM club_payments p
                         JOIN club_members m ON p.member_id = m.id
                         WHERE p.status = 'pending'
                         ORDER BY p.created_at DESC")->fetchAll();

// All verified payments (history)
$paidHistory = $pdo->query("SELECT p.*, m.full_name, m.whatsapp, m.plan_id
                            FROM club_payments p
                            JOIN club_members m ON p.member_id = m.id
                            WHERE p.status = 'verified'
                            ORDER BY p.created_at DESC LIMIT 20")->fetchAll();

// Pending/in-progress CV requests
$requests = $pdo->query("SELECT r.*, m.full_name, m.whatsapp
                         FROM club_cv_requests r
                         JOIN club_members m ON r.member_id = m.id
                         WHERE r.status IN ('pending', 'in_progress')
                         ORDER BY r.created_at DESC")->fetchAll();

// All members with full info
$members = $pdo->query("SELECT * FROM club_members ORDER BY status ASC, created_at DESC")->fetchAll();

// Stats
$totalRevenue   = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM club_payments WHERE status='verified'")->fetchColumn();
$activeMembers  = (int)$pdo->query("SELECT COUNT(*) FROM club_members WHERE status='active'")->fetchColumn();
$pendingCount   = count($payments);
$openRequests   = count($requests);
$totalMembers   = count($members);

// Monthly revenue (current month)
$monthRevenue = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM club_payments WHERE status='verified' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// --- DATA FOR GRAPHS ---

// 1. Revenue trend (Last 7 days)
$revenueData = [];
$revenueLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $q = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM club_payments WHERE status='verified' AND DATE(created_at) = ?");
    $q->execute([$date]);
    $revenueData[] = (int)$q->fetchColumn();
    $revenueLabels[] = date('d M', strtotime($date));
}

// 2. Member status distribution
$statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM club_members GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$statusLabels = ['Active', 'En attente', 'Suspendu', 'Expiré'];
$statusValues = [
    (int)($statusCounts['active'] ?? 0),
    (int)($statusCounts['pending_payment'] ?? 0),
    (int)($statusCounts['suspended'] ?? 0),
    (int)($statusCounts['expired'] ?? 0)
];

// 3. Plan adoption
$planCounts = $pdo->query("SELECT plan_id, COUNT(*) as count FROM club_members GROUP BY plan_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$planLabels = ['Basic', 'Standard', 'Premium'];
$planValues = [
    (int)($planCounts[1] ?? 0),
    (int)($planCounts[2] ?? 0),
    (int)($planCounts[3] ?? 0)
];

$planNames  = [1 => 'Basic', 2 => 'Standard', 3 => 'Premium'];
$planColors = [1 => 'bg-slate-100 text-slate-600', 2 => 'bg-sky-100 text-sky-700', 3 => 'bg-indigo-100 text-indigo-700'];
?>
<?php include 'includes/header.php'; ?>
<body class="bg-slate-50 antialiased selection:bg-indigo-100 selection:text-indigo-900" style="font-family:'Inter',sans-serif;">
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
    
    :root {
        --primary: #6366f1;
        --primary-soft: rgba(99, 102, 241, 0.1);
        --secondary: #0f172a;
        --accent: #0ea5e9;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --glass: rgba(255, 255, 255, 0.75);
    }

    /* Premium Layout */
    .dashboard-container {
        max-width: 1600px;
        margin: 0 auto;
        min-width: 0;
    }

    @media (max-width: 768px) {
        .chart-box { height: 280px !important; }
    }

    /* Glassmorphism & Cards */
    .glass-card {
        background: var(--glass);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        box-shadow: 0 8px 32px -4px rgba(0, 0, 0, 0.04);
    }

    .premium-card {
        background: white;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .premium-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08);
    }

    /* Navigation Tabs V2 */
    .tab-btn {
        position: relative;
        transition: all 0.3s ease;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        font-size: 0.75rem;
    }

    .tab-btn.active {
        color: var(--primary);
    }

    @media (max-width: 768px) {
        .tab-btn { font-size: 10px; padding: 10px 12px; }
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--primary);
        border-radius: 99px;
    }

    /* Custom Scrollbar */
    .scroll-hide::-webkit-scrollbar { display: none; }
    .scroll-hide { -ms-overflow-style: none; scrollbar-width: none; }

    /* Performance Animations */
    .fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .slide-up { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* Chart Containers */
    .chart-box {
        min-height: 350px;
    }

    /* Mobile Optimizations */
    @media (max-width: 768px) {
        .glass-card { backdrop-filter: none; background: white; }
        .dashboard-container { padding: 0 1rem; }
    }
</style>

<!-- Password Modal -->
<div id="pwdModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 backdrop-blur-md p-4 transition-all duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm p-8 text-center border border-white/20 transform">
        <div class="w-20 h-20 bg-indigo-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
            <i data-lucide="key-round" class="w-10 h-10 text-indigo-600"></i>
        </div>
        <h3 class="text-2xl font-black text-slate-900 mb-2" id="pwdModalName">Réinitialisation</h3>
        <p class="text-slate-400 text-sm mb-8 font-medium">Nouveau mot de passe membre</p>
        
        <div class="bg-slate-950 rounded-2xl px-8 py-5 mb-6 flex items-center justify-between gap-4 group">
            <span class="text-3xl font-black text-white tracking-[0.2em] font-mono" id="pwdValue">—</span>
            <button onclick="copyPwd()" class="w-11 h-11 bg-white/10 hover:bg-indigo-500 text-white rounded-xl flex items-center justify-center transition-all flex-shrink-0 group-active:scale-95">
                <i data-lucide="copy" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="flex flex-col gap-3">
            <button onclick="sendPwdWhatsApp()" class="w-full py-4 bg-emerald-500 text-white font-black rounded-2xl text-sm hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-200 flex items-center justify-center gap-2">
                <i data-lucide="message-circle" class="w-5 h-5"></i> Envoyer via WhatsApp
            </button>
            <button onclick="closePwdModal()" class="w-full py-4 bg-slate-100 text-slate-500 font-bold rounded-2xl text-sm hover:bg-slate-200 transition-all">
                Fermer
            </button>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div id="statusModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/40 backdrop-blur-md p-4 transition-all duration-300">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-sm p-8 border border-white/20">
        <h3 class="text-xl font-black text-slate-900 mb-2">Changer le statut</h3>
        <p class="text-slate-400 text-sm mb-8 font-medium italic" id="statusModalName">—</p>
        <div class="space-y-3" id="statusOptions">
            <?php foreach([
                'active'          => ['Membre Actif',    'emerald', 'check-circle-2'],
                'pending_payment' => ['En Attente',      'amber',   'clock-4'],
                'suspended'       => ['Accès Suspendu',  'rose',    'shield-alert'],
                'expired'         => ['Abonnement Expiré','slate',   'calendar-x-2'],
            ] as $val => [$label, $color, $icon]): ?>
            <button onclick="applyStatus('<?= $val ?>')"
                    class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl border-2 border-slate-50 hover:border-<?= $color ?>-200 hover:bg-<?= $color ?>-50 transition-all text-left group">
                <div class="w-10 h-10 bg-<?= $color ?>-100 rounded-xl flex items-center justify-center group-hover:bg-<?= $color ?>-500 transition-colors">
                    <i data-lucide="<?= $icon ?>" class="w-5 h-5 text-<?= $color ?>-600 group-hover:text-white transition-colors"></i>
                </div>
                <span class="font-black text-slate-700 text-sm group-hover:text-<?= $color ?>-700 transition-colors"><?= $label ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <button onclick="closeStatusModal()" class="w-full mt-6 py-3 text-sm text-slate-400 font-bold hover:text-rose-500 transition-colors">Annuler</button>
    </div>
</div>

<div class="flex flex-col md:flex-row min-h-screen bg-slate-50">
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="w-full md:flex-1 dashboard-container px-4 md:px-8 py-6 md:py-12 min-h-screen">
        
        <!-- Premium Header -->
        <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-10">
            <div class="text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-3 mb-3">
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase tracking-widest rounded-full border border-indigo-100/50">Admin Platform</span>
                </div>
                <h1 class="text-4xl md:text-6xl font-black text-slate-900 tracking-tight leading-none">HireMe <span class="text-indigo-600">Club</span></h1>
                <p class="text-slate-400 font-medium max-w-sm mt-3 mx-auto md:mx-0 text-sm md:text-base">Gestion intelligente des membres et optimisation des livrables.</p>
            </div>
            
            <div class="flex items-center gap-2 overflow-x-auto scroll-hide pb-2 md:pb-0">
                <a href="?tab=dashboard" class="tab-btn px-6 py-3 whitespace-nowrap <?= $activeTab === 'dashboard' ? 'active' : 'text-slate-400' ?>">Tableau de bord</a>
                <a href="?tab=payments" class="tab-btn px-6 py-3 whitespace-nowrap <?= $activeTab === 'payments' ? 'active' : 'text-slate-400' ?>">
                    Paiements 
                    <?php if($pendingCount > 0): ?>
                        <span class="ml-1.5 px-2 py-0.5 bg-rose-500 text-white text-[9px] rounded-full"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=requests" class="tab-btn px-6 py-3 whitespace-nowrap <?= $activeTab === 'requests' ? 'active' : 'text-slate-400' ?>">
                    Commandes CV
                    <?php if($openRequests > 0): ?>
                        <span class="ml-1.5 px-2 py-0.5 bg-amber-500 text-white text-[9px] rounded-full"><?= $openRequests ?></span>
                    <?php endif; ?>
                </a>
                <a href="?tab=members" class="tab-btn px-6 py-3 whitespace-nowrap <?= $activeTab === 'members' ? 'active' : 'text-slate-400' ?>">Annuaire</a>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="fade-in">
            
            <!-- ============================
                 DASHBOARD TAB (OVERVIEW)
                 ============================ -->
            <?php if ($activeTab === 'dashboard' || $activeTab === ''): ?>
            <div class="space-y-8">
                
                <!-- KPI Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="premium-card rounded-3xl p-5 md:p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center">
                                <i data-lucide="banknote" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Revenu Total</p>
                        <h3 class="text-2xl font-black text-slate-900"><?= number_format($totalRevenue) ?> <span class="text-xs text-slate-300 ml-1">XAF</span></h3>
                    </div>
                    
                    <a href="?tab=members" class="premium-card rounded-3xl p-5 md:p-6 hover:scale-[1.02] transition-transform block">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">Voir tout</span>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Membres Actifs</p>
                        <h3 class="text-2xl font-black text-slate-900"><?= $activeMembers ?> <span class="text-xs text-slate-300 ml-1">/ <?= $totalMembers ?></span></h3>
                    </a>

                    <a href="?tab=payments" class="premium-card rounded-3xl p-5 md:p-6 hover:scale-[1.02] transition-transform block">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center">
                                <i data-lucide="clock" class="w-6 h-6"></i>
                            </div>
                            <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">Action</span>
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">En Attente</p>
                        <h3 class="text-2xl font-black text-slate-900"><?= $pendingCount + $openRequests ?> <span class="text-xs text-slate-300 ml-1">Actions</span></h3>
                    </a>

                    <div class="premium-card rounded-3xl p-5 md:p-6 bg-slate-900 border-none">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-white/10 text-white rounded-2xl flex items-center justify-center">
                                <i data-lucide="trending-up" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Performance (Mois)</p>
                        <h3 class="text-2xl font-black text-white"><?= number_format($monthRevenue) ?> <span class="text-xs text-white/30 ml-1">XAF</span></h3>
                    </div>
                </div>

                <!-- Graphs Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 premium-card rounded-[2.5rem] p-8">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h4 class="text-lg font-black text-slate-900">Courbe de Revenus</h4>
                                <p class="text-xs text-slate-400 font-medium">Performance des 7 derniers jours</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-indigo-500 rounded-full"></div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Paiements Validés</span>
                            </div>
                        </div>
                        <div id="revenueChart" class="chart-box"></div>
                    </div>

                    <div class="space-y-8">
                        <div class="premium-card rounded-[2.5rem] p-8">
                            <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-6">Répartition des Packs</h4>
                            <div id="planChart"></div>
                        </div>
                        <div class="premium-card rounded-[2.5rem] p-8">
                            <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-6">État des Comptes</h4>
                            <div id="statusChart"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ============================
                 PAYMENTS TAB
                 ============================ -->
            <?php if ($activeTab === 'payments'): ?>
            <div class="space-y-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        Paiements à valider
                        <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[10px] rounded-full"><?= count($payments) ?></span>
                    </h2>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="premium-card rounded-[2.5rem] p-20 text-center">
                        <div class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="check" class="w-10 h-10"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 mb-2">Tout est à jour !</h3>
                        <p class="text-slate-400 font-medium">Aucun nouveau paiement à valider pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        <?php foreach ($payments as $p): ?>
                        <div class="premium-card rounded-[2rem] p-5 md:p-6 flex flex-col">
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 text-lg font-black">
                                        <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="font-black text-slate-900 leading-none mb-1"><?= htmlspecialchars($p['full_name']) ?></h4>
                                        <p class="text-[10px] text-slate-400 font-bold tracking-widest uppercase"><?= $p['whatsapp'] ?></p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-600 text-[9px] font-black uppercase tracking-widest rounded-lg"><?= $p['method'] ?></span>
                            </div>

                            <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100/50">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Montant</span>
                                    <span class="text-xs font-black text-indigo-600 uppercase"><?= $planNames[$p['plan_id']] ?? 'Pack' ?></span>
                                </div>
                                <p class="text-2xl font-black text-slate-900"><?= number_format($p['amount']) ?> <span class="text-xs text-slate-300">XAF</span></p>
                                <?php if($p['transaction_id']): ?>
                                    <p class="text-[10px] text-slate-400 font-bold mt-2 truncate">ID: <?= htmlspecialchars($p['transaction_id']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto flex items-center gap-3">
                                <button onclick="approvePayment(<?= $p['id'] ?>, <?= $p['member_id'] ?>)" 
                                        class="flex-1 py-3.5 bg-slate-900 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                                    Valider
                                </button>
                                <?php if($p['proof_file']): ?>
                                    <a href="../<?= $p['proof_file'] ?>" target="_blank" 
                                       class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600 hover:bg-indigo-50 transition-all">
                                        <i data-lucide="image" class="w-5 h-5"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ============================
                 CV REQUESTS TAB
                 ============================ -->
            <?php if ($activeTab === 'requests'): ?>
            <div class="max-w-5xl mx-auto space-y-6 fade-in">

                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <i data-lucide="briefcase" class="w-4 h-4 text-indigo-500"></i>
                        File d'attente (<?= $openRequests ?> CV à optimiser)
                    </h2>
                </div>

                <?php if (empty($requests)): ?>
                <div class="bg-white rounded-[2.5rem] border border-slate-100 py-24 text-center shadow-sm">
                    <div class="w-20 h-20 bg-indigo-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-indigo-400">
                        <i data-lucide="inbox" class="w-10 h-10"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">Aucune commande</h3>
                    <p class="text-slate-400 text-sm">Les nouvelles demandes apparaîtront ici dès qu'un membre soumet une offre.</p>
                </div>
                <?php endif; ?>

                <div class="space-y-6">
                    <?php foreach ($requests as $r):
                        $isDescMode = ($r['job_url'] === '[description]');
                        $jobDescription = ''; $extraNotes = '';
                        if ($isDescMode && $r['notes']) {
                            $sep = "\n\n--- Notes supplémentaires ---\n";
                            $pos = strpos($r['notes'], $sep);
                            if ($pos !== false) {
                                $jobDescription = substr($r['notes'], 0, $pos);
                                $extraNotes = substr($r['notes'], $pos + strlen($sep));
                            } else { $jobDescription = $r['notes']; }
                        }
                    ?>
                    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden hover:shadow-xl hover:border-indigo-100 transition-all group">
                        <!-- Upper Section -->
                        <div class="p-6 md:p-8 pb-6 border-b border-slate-50">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-slate-900 rounded-2xl flex items-center justify-center text-white text-lg font-black shadow-lg">
                                        <?= strtoupper(substr($r['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-[900] text-slate-900 leading-tight"><?= htmlspecialchars($r['full_name']) ?></h4>
                                        <div class="flex items-center gap-3 mt-1">
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['whatsapp']) ?>" 
                                               target="_blank" class="text-xs text-indigo-600 font-bold hover:underline flex items-center gap-1">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                                <?= htmlspecialchars($r['whatsapp']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($isDescMode): ?>
                                    <span class="px-3 py-1.5 bg-purple-50 text-purple-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-purple-100 flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="w-3 h-3"></i> Texte Manuel
                                    </span>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 bg-sky-50 text-sky-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-sky-100 flex items-center gap-1.5">
                                        <i data-lucide="globe" class="w-3 h-3"></i> Lien Web
                                    </span>
                                    <?php endif; ?>
                                    
                                    <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest <?= $r['status'] === 'in_progress' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-amber-50 text-amber-600 border border-amber-100' ?>">
                                        <?= $r['status'] === 'in_progress' ? 'Traitement' : 'Attente' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="bg-slate-50 border border-slate-100 rounded-3xl p-6 mb-4">
                                <?php if ($isDescMode): ?>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Détails de l'Annonce</p>
                                <div class="text-sm text-slate-600 leading-relaxed max-h-48 overflow-y-auto scroll-hide whitespace-pre-wrap font-medium"><?= htmlspecialchars($jobDescription) ?></div>
                                <?php else: ?>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Lien de l'Annonce</p>
                                <a href="<?= htmlspecialchars($r['job_url']) ?>" target="_blank"
                                   class="inline-flex items-center gap-2 px-5 py-3 bg-white border border-slate-200 rounded-2xl text-sky-600 font-bold text-sm hover:border-sky-300 transition-all group/link shadow-sm">
                                    <i data-lucide="external-link" class="w-4 h-4 group-hover/link:translate-x-0.5 transition-transform"></i>
                                    Consulter l'offre cible
                                </a>
                                <?php endif; ?>
                            </div>

                            <?php if (!$isDescMode && $r['notes']): ?>
                            <div class="px-4 py-3 bg-amber-50/50 rounded-2xl border border-amber-100 flex items-start gap-3">
                                <i data-lucide="sticky-note" class="w-4 h-4 text-amber-500 mt-0.5"></i>
                                <p class="text-xs text-amber-700 font-medium italic"><?= nl2br(htmlspecialchars($r['notes'])) ?></p>
                            </div>
                            <?php elseif ($isDescMode && $extraNotes): ?>
                            <div class="px-4 py-3 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex items-start gap-3">
                                <i data-lucide="info" class="w-4 h-4 text-indigo-500 mt-0.5"></i>
                                <p class="text-xs text-indigo-700 font-medium italic"><?= nl2br(htmlspecialchars($extraNotes)) ?></p>
                            </div>
                            <?php endif; ?>
                            <!-- Lower Section (Delivery) -->
                            <div class="px-8 py-6 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-6 border-t border-slate-50">
                                <form action="api/club_deliver_cv.php" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row md:items-center gap-4 w-full">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <div class="flex-1">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Uploader le CV optimisé (PDF)</p>
                                        <label class="flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-2xl cursor-pointer hover:border-indigo-300 transition-all shadow-sm">
                                            <i data-lucide="upload-cloud" class="w-4 h-4 text-slate-400"></i>
                                            <span class="text-xs text-slate-500 font-medium">Choisir un fichier...</span>
                                            <input type="file" name="optimized_cv" class="hidden" accept=".pdf,.doc,.docx" required>
                                        </label>
                                    </div>
                                    <button type="submit" 
                                            class="px-8 py-4 bg-emerald-500 text-white font-black rounded-2xl text-xs hover:bg-emerald-600 shadow-xl shadow-emerald-100 active:scale-95 transition-all flex items-center justify-center gap-2 md:mt-5">
                                        <i data-lucide="send" class="w-4 h-4"></i> Livrer au membre
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>



             <!-- ============================
                 MEMBERS DIRECTORY TAB
                 ============================ -->
            <?php if ($activeTab === 'members'): ?>
            <div class="space-y-8">
                
                <!-- Filters & Stats Card -->
                <div class="premium-card rounded-[2.5rem] p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Membres</p>
                            <h4 class="text-2xl font-black text-slate-900"><?= $totalMembers ?></h4>
                        </div>
                        <div class="w-px h-10 bg-slate-100 hidden md:block"></div>
                        <div class="flex flex-wrap items-center gap-3">
                            <input type="text" id="memberSearch" placeholder="Rechercher (Nom, WhatsApp...)" onkeyup="filterMembers()"
                                   class="premium-input bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-medium w-full md:w-64 focus:outline-none">
                            <select id="planFilter" onchange="filterMembers()" class="bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-black text-slate-600 focus:outline-none">
                                <option value="">Tous les packs</option>
                                <option value="Basic">Basic</option>
                                <option value="Standard">Standard</option>
                                <option value="Premium">Premium</option>
                            </select>
                            <select id="statusFilter" onchange="filterMembers()" class="bg-slate-50 border-slate-100 rounded-2xl px-5 py-3 text-sm font-black text-slate-600 focus:outline-none">
                                <option value="">Tous les statuts</option>
                                <option value="active">Actif</option>
                                <option value="pending_payment">En attente</option>
                                <option value="suspended">Suspendu</option>
                                <option value="expired">Expiré</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block premium-card rounded-[2.5rem] overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Membre</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut & Pack</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Usage CV</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="memberTableBody">
                            <?php foreach ($members as $m): 
                                $planLabel = $planNames[$m['plan_id']] ?? '—';
                                $statusConfig = [
                                    'active'          => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'Actif'],
                                    'pending_payment' => ['bg-amber-50 text-amber-700 border-amber-100',  'En attente'],
                                    'suspended'       => ['bg-rose-50 text-rose-700 border-rose-100',     'Suspendu'],
                                    'expired'         => ['bg-slate-100 text-slate-500 border-slate-200', 'Expiré'],
                                ];
                                [$statusClass, $statusLabel] = $statusConfig[$m['status']] ?? ['bg-slate-100 text-slate-500 border-slate-200', $m['status']];
                                $usedPct = $m['cv_allowance'] > 0 ? round(($m['cv_used'] / $m['cv_allowance']) * 100) : 0;
                            ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors member-row" 
                                data-name="<?= strtolower(htmlspecialchars($m['full_name'])) ?>" 
                                data-whatsapp="<?= $m['whatsapp'] ?>" 
                                data-status="<?= $m['status'] ?>" 
                                data-plan="<?= $planLabel ?>">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-black text-sm">
                                            <?= strtoupper(substr($m['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900"><?= htmlspecialchars($m['full_name']) ?></p>
                                            <p class="text-xs text-slate-400 font-medium"><?= htmlspecialchars($m['whatsapp']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-xs">
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <span class="px-2.5 py-1 rounded-lg font-black uppercase tracking-widest border <?= $statusClass ?>"><?= $statusLabel ?></span>
                                        <span class="text-[10px] font-black text-slate-400 lowercase ml-1"><?= $planLabel ?> Pack</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="w-32">
                                        <div class="flex justify-between text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-widest">
                                            <span><?= $m['cv_used'] ?> / <?= $m['cv_allowance'] ?></span>
                                            <span><?= $usedPct ?>%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full transition-all bg-indigo-500 rounded-full" style="width: <?= min(100, $usedPct) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2">
                                        <button onclick="openStatusModal(<?= $m['id'] ?>,'<?= addslashes($m['full_name']) ?>','<?= $m['status'] ?>')" 
                                                class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                                            <i data-lucide="settings-2" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="openPwdModalAction(<?= $m['id'] ?>, '<?= addslashes($m['full_name']) ?>', '<?= $m['whatsapp'] ?>')" 
                                                class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:border-indigo-200 transition-all">
                                            <i data-lucide="key" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Grid -->
                <div class="md:hidden space-y-4" id="memberMobileContainer">
                    <?php foreach ($members as $m): 
                        // Reuse variables from above or recalculate safely
                         $planLabel = $planNames[$m['plan_id']] ?? '—';
                         $statusConfig = [
                             'active'          => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'Actif'],
                             'pending_payment' => ['bg-amber-50 text-amber-700 border-amber-100',  'En attente'],
                             'suspended'       => ['bg-rose-50 text-rose-700 border-rose-100',     'Suspendu'],
                             'expired'         => ['bg-slate-100 text-slate-500 border-slate-200', 'Expiré'],
                         ];
                         [$statusClass, $statusLabel] = $statusConfig[$m['status']] ?? ['bg-slate-100 text-slate-500 border-slate-200', $m['status']];
                         $usedPct = $m['cv_allowance'] > 0 ? round(($m['cv_used'] / $m['cv_allowance']) * 100) : 0;
                    ?>
                    <div class="premium-card rounded-[2rem] p-6 member-card" 
                         data-name="<?= strtolower(htmlspecialchars($m['full_name'])) ?>" 
                         data-whatsapp="<?= $m['whatsapp'] ?>" 
                         data-status="<?= $m['status'] ?>" 
                         data-plan="<?= $planLabel ?>">
                        <div class="flex items-start justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-500 text-lg font-black">
                                    <?= strtoupper(substr($m['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 class="font-black text-slate-900 leading-none mb-1"><?= htmlspecialchars($m['full_name']) ?></h4>
                                    <p class="text-xs text-slate-400 font-bold"><?= htmlspecialchars($m['whatsapp']) ?></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest <?= $statusClass ?>"><?= $statusLabel ?></span>
                        </div>
                        <div class="flex items-center justify-between gap-4 mb-6">
                            <div class="bg-slate-50 rounded-2xl p-4 flex-1">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Pack actuel</p>
                                <p class="text-xs font-black text-indigo-600 uppercase"><?= $planLabel ?></p>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-4 flex-1">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Usage CV</p>
                                <p class="text-xs font-black text-slate-900"><?= $m['cv_used'] ?> / <?= $m['cv_allowance'] ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="openStatusModal(<?= $m['id'] ?>,'<?= addslashes($m['full_name']) ?>','<?= $m['status'] ?>')" 
                                    class="flex-1 py-3.5 bg-slate-900 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest">
                                Gérer statuts
                            </button>
                            <button onclick="openPwdModalAction(<?= $m['id'] ?>, '<?= addslashes($m['full_name']) ?>', '<?= $m['whatsapp'] ?>')" 
                                    class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600">
                                <i data-lucide="key" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <div id="noResults" class="hidden premium-card rounded-[2.5rem] p-20 text-center">
                    <p class="text-slate-400 font-black uppercase tracking-widest text-[11px]">Aucun membre trouvé</p>
                </div>
            </div>
            <?php endif; ?>

        </div> <!-- /fade-in -->
    </main>
</div>

<!-- ============================
     MODALS & FLOATING UI
     ============================ -->

<!-- Password Display Modal -->
<div id="pwdModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl transform scale-95 transition-all duration-300 overflow-hidden relative">
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-indigo-50 rounded-full"></div>
        <div class="relative">
            <h3 class="text-xl font-black text-slate-900 mb-2">Accès Membre</h3>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6" id="pwdModalName">MEMBRE</p>
            
            <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100 mb-8 text-center relative group">
                <button onclick="copyPwd()" class="absolute top-3 right-3 text-slate-300 hover:text-indigo-500 transition-colors">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
                <p class="text-4xl font-black text-slate-900 tracking-wider" id="pwdValue">XXXXX</p>
                <p class="text-[10px] font-bold text-slate-400 mt-2">Mot de passe provisoire</p>
            </div>

            <div class="flex flex-col gap-3">
                <button onclick="sendPwdWhatsApp()" class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-200 flex items-center justify-center gap-2">
                    <i data-lucide="message-square" class="w-4 h-4"></i> Envoyer via WhatsApp
                </button>
                <button onclick="closePwdModal()" class="w-full py-4 text-slate-400 font-black text-xs uppercase tracking-widest">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Status Edit Modal -->
<div id="statusModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-[2.5rem] w-full max-w-sm p-8 shadow-2xl transform scale-95 transition-all duration-300">
        <h3 class="text-xl font-black text-slate-900 mb-1">Modifier Statut</h3>
        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-6" id="statusModalName">NOM MÉMBRE</p>
        
        <div class="grid grid-cols-1 gap-3">
            <button onclick="applyStatus('active')" class="w-full p-4 rounded-2xl border-2 border-slate-50 hover:border-emerald-500 hover:bg-emerald-50 flex items-center justify-between group transition-all">
                <span class="font-black text-sm text-slate-600 group-hover:text-emerald-700">Activer le compte</span>
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
            </button>
            <button onclick="applyStatus('suspended')" class="w-full p-4 rounded-2xl border-2 border-slate-50 hover:border-rose-500 hover:bg-rose-50 flex items-center justify-between group transition-all">
                <span class="font-black text-sm text-slate-600 group-hover:text-rose-700">Suspendre l'accès</span>
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500"></i>
            </button>
            <button onclick="applyStatus('expired')" class="w-full p-4 rounded-2xl border-2 border-slate-50 hover:border-slate-500 hover:bg-slate-50 flex items-center justify-between group transition-all">
                <span class="font-black text-sm text-slate-600 group-hover:text-slate-700">Marquer comme expiré</span>
                <i data-lucide="clock" class="w-5 h-5 text-slate-500"></i>
            </button>
        </div>
        <button onclick="closeStatusModal()" class="w-full py-4 mt-6 text-slate-400 font-black text-xs uppercase tracking-widest">Annuler</button>
    </div>
</div>

<!-- Global Toast -->
<div id="toast" class="fixed top-8 left-1/2 -translate-x-1/2 z-[100] hidden items-center gap-3 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl">
    <div class="w-6 h-6 bg-indigo-500 rounded-lg flex items-center justify-center">
        <i data-lucide="bell" class="w-3 h-3"></i>
    </div>
    <span id="toastMsg" class="text-sm font-black whitespace-nowrap">Message</span>
</div>

<script>
lucide.createIcons();

// --- DATA FROM PHP ---
const revenueData = <?php echo json_encode($revenueData); ?>;
const revenueLabels = <?php echo json_encode($revenueLabels); ?>;
const statusValues = <?php echo json_encode($statusValues); ?>;
const statusLabels = <?php echo json_encode($statusLabels); ?>;
const planValues = <?php echo json_encode($planValues); ?>;
const planLabels = <?php echo json_encode($planLabels); ?>;

// 1. Revenue Spline Chart
const revenueOptions = {
    series: [{ name: 'Revenu', data: revenueData }],
    chart: { height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'Inter' },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3, colors: ['#6366f1'] },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100] }
    },
    xaxis: { categories: revenueLabels, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: { labels: { show: false } },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
    colors: ['#6366f1']
};
new ApexCharts(document.querySelector("#revenueChart"), revenueOptions).render();

// 2. Plan Distribution (Donut)
const planOptions = {
    series: planValues,
    chart: { type: 'donut', height: 280, fontFamily: 'Inter' },
    labels: planLabels,
    colors: ['#94a3b8', '#0ea5e9', '#6366f1'],
    legend: { position: 'bottom', fontWeight: 700 },
    plotOptions: { pie: { donut: { size: '75%' } } },
    dataLabels: { enabled: false }
};
new ApexCharts(document.querySelector("#planChart"), planOptions).render();

// 3. Status Distributions
const statusOptions = {
    series: statusValues,
    chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter' },
    plotOptions: { bar: { borderRadius: 8, horizontal: true, distributed: true, barHeight: '60%' } },
    colors: ['#10b981', '#f59e0b', '#ef4444', '#94a3b8'],
    dataLabels: { enabled: false },
    xaxis: { categories: statusLabels },
    legend: { show: false }
};
new ApexCharts(document.querySelector("#statusChart"), statusOptions).render();

// --- SHARED ACTIONS ---
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    t.classList.remove('hidden');
    t.classList.add('flex', 'fade-in');
    setTimeout(() => { 
        t.classList.add('hidden'); 
        t.classList.remove('flex', 'fade-in'); 
    }, 3000);
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => showToast('Copié : ' + text));
}

// ── Payment Processing ───────────────────────────────────────
async function approvePayment(payId, memberId) {
    if (!confirm('Voulez-vous APPROUVER ce paiement ?\nLe membre sera activé immédiatement.')) return;
    try {
        const res = await fetch('api/club_approve_payment.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payId, memberId, action: 'approve' })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Paiement validé avec succès !');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(e) { alert('Erreur réseau'); }
}

async function rejectPayment(payId) {
    if (!confirm('Voulez-vous REJETER ce paiement ?')) return;
    try {
        const res = await fetch('api/club_approve_payment.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payId, action: 'reject' })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Paiement rejeté.');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(e) { alert('Erreur réseau'); }
}

// ── Password Logic ──────────────────────────────────
let _pwdWaNum = '';
let _pwdPlain = '';

async function openPwdModalAction(memberId, name, waNum) {
    if (!confirm(`Générer un nouveau mot de passe pour ${name} ?`)) return;
    _pwdWaNum = waNum;
    try {
        const res = await fetch('api/reset_member_password.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ memberId })
        });
        const data = await res.json();
        if (data.success) {
            _pwdPlain = data.password;
            document.getElementById('pwdModalName').textContent = name;
            document.getElementById('pwdValue').textContent = data.password;
            openModal('pwdModal');
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(e) { alert('Erreur réseau'); }
}

function copyPwd() {
    navigator.clipboard.writeText(_pwdPlain).then(() => showToast('Code copié !'));
}

function sendPwdWhatsApp() {
    const msg = encodeURIComponent(`👋 Bonjour ! Voici vos accès Club HireMe :\n\n🔐 Mot de passe : *${_pwdPlain}*\n🔗 Accès : https://moncvpro.hiremeguide.com/club/login.php\n\nNous restons à votre écoute.`);
    window.open(`https://wa.me/${_pwdWaNum}?text=${msg}`, '_blank');
    closePwdModal();
}

function closePwdModal() { closeModal('pwdModal'); }

// ── Status Management ──────────────────────────────────────────
let _statusMemberId = null;

function openStatusModal(id, name, currentStatus) {
    _statusMemberId = id;
    document.getElementById('statusModalName').textContent = name;
    openModal('statusModal');
}

async function applyStatus(newStatus) {
    try {
        const res = await fetch('api/update_member_status.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ memberId: _statusMemberId, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            closeModal('statusModal');
            showToast('Statut mis à jour !');
            setTimeout(() => location.reload(), 800);
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch(e) { alert('Erreur réseau'); }
}

function closeStatusModal() { closeModal('statusModal'); }

// ── Modal Generic ──
function openModal(id) {
    const m = document.getElementById(id);
    m.classList.remove('hidden');
    m.classList.add('flex');
    setTimeout(() => m.children[0].classList.add('scale-100'), 10);
    lucide.createIcons();
}
function closeModal(id) {
    const m = document.getElementById(id);
    m.children[0].classList.remove('scale-100');
    setTimeout(() => {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }, 200);
}
// Backdrop close
['pwdModal','statusModal'].forEach(id => {
    const m = document.getElementById(id);
    if(m) m.addEventListener('click', (e) => { if(e.target === m) closeModal(id); });
});

// ── Member Search / Filter Engine ────────────────────────────────
function filterMembers() {
    const q      = document.getElementById('memberSearch')?.value.toLowerCase() || '';
    const status = document.getElementById('statusFilter')?.value || '';
    const plan   = document.getElementById('planFilter')?.value || '';

    const rows   = document.querySelectorAll('.member-row');
    const cards  = document.querySelectorAll('.member-card');
    
    let totalVisible = 0;

    function checkMatches(el) {
        const name     = el.dataset.name || '';
        const whatsapp = el.dataset.whatsapp || '';
        const st       = el.dataset.status || '';
        const pl       = el.dataset.plan || '';

        const matchesText   = !q || name.includes(q) || whatsapp.includes(q);
        const matchesStatus = !status || st === status;
        const matchesPlan   = !plan || pl.includes(plan);

        return matchesText && matchesStatus && matchesPlan;
    }

    rows.forEach(r => {
        const ok = checkMatches(r);
        r.classList.toggle('hidden', !ok);
        if(ok) totalVisible++;
    });

    cards.forEach(c => {
        const ok = checkMatches(c);
        c.classList.toggle('hidden', !ok);
        if(window.innerWidth < 768 && ok) totalVisible++; 
    });

    const noRes = document.getElementById('noResults');
    if (noRes) noRes.classList.toggle('hidden', totalVisible > 0);
}
</script>

<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</body>
</html>
