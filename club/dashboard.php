<?php
require_once '../admin/config/db.php';
session_start();

if (!isset($_SESSION['club_member_id'])) { header('Location: login.php'); exit; }

$memberId = $_SESSION['club_member_id'];

$stmt = $pdo->prepare("SELECT * FROM club_members WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();
if (!$member) { unset($_SESSION['club_member_id']); header('Location: login.php'); exit; }

if ($member['status'] === 'pending_payment') { header('Location: payment.php'); exit; }
if ($member['status'] !== 'active') {
    session_destroy();
    header('Location: login.php?reason=suspended');
    exit;
}

$stmtReq = $pdo->prepare("SELECT * FROM club_cv_requests WHERE member_id = ? ORDER BY created_at DESC");
$stmtReq->execute([$memberId]);
$requests = $stmtReq->fetchAll();

$remaining  = max(0, $member['cv_allowance'] - $member['cv_used']);
$usedPct    = $member['cv_allowance'] > 0 ? round(($member['cv_used'] / $member['cv_allowance']) * 100) : 0;
$planNames  = [1 => 'Basic', 2 => 'Standard', 3 => 'Premium'];
$planName   = $planNames[$member['plan_id']] ?? 'Basic';
$firstName  = explode(' ', $member['full_name'])[0];

$expiryDate = $member['expiry_date'] ?? null;
$expiryStr  = $expiryDate ? date('d/m/Y', strtotime($expiryDate)) : null;
$daysLeft   = $expiryDate ? (int)ceil((strtotime($expiryDate) - time()) / 86400) : null;
$expirySoon = $daysLeft !== null && $daysLeft <= 5 && $daysLeft > 0;
$isExpired  = $daysLeft !== null && $daysLeft <= 0;

$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);

$statusCfg = [
    'pending'     => ['bg-amber-50 text-amber-700 border border-amber-200',  'En attente'],
    'in_progress' => ['bg-sky-50 text-sky-700 border border-sky-200',         'En cours'],
    'delivered'   => ['bg-emerald-50 text-emerald-700 border border-emerald-200', 'Livré'],
    'rejected'    => ['bg-rose-50 text-rose-700 border border-rose-200',       'Refusé'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — HireMe Club</title>
    <!-- Social Preview -->
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://moncvpro.hiremeguide.com/assets/club-preview-premium.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://moncvpro.hiremeguide.com/assets/club-preview-premium.png">

    <!-- Favicons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family:'Inter',sans-serif; }
        .progress-bar { height:6px; border-radius:99px; background:#e2e8f0; overflow:hidden; }
        .progress-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#0ea5e9,#6366f1); transition:width .6s ease; }
        .card { background:#fff; border:1px solid #f1f5f9; border-radius:20px; }
        .toast { animation: slideUp .35s ease forwards; }
        @keyframes slideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
    </style>
    <script src="../assets/js/site-language.js?v=4" defer></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
</head>
<body class="antialiased bg-slate-50 min-h-screen pb-24 md:pb-0">

<!-- ===== TOP NAV ===== -->
<nav class="bg-white border-b border-slate-100 sticky top-0 z-30">
    <div class="max-w-5xl mx-auto px-5 h-16 flex items-center justify-between">
        <a href="../abonnements.html" class="font-black text-slate-900 tracking-tight">
            HireMe<span class="text-sky-500">Club</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="hidden sm:block text-xs font-semibold text-slate-400">
                <?= htmlspecialchars($member['full_name']) ?>
            </span>
            <a href="logout.php"
               class="h-9 w-9 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-xl flex items-center justify-center transition-all"
               title="Déconnexion">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</nav>

<main class="max-w-5xl mx-auto px-4 py-8 md:py-12">

    <!-- Success toast -->
    <?php if ($successMsg): ?>
    <div class="toast mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 flex-shrink-0"></i>
        <p class="text-emerald-700 text-sm font-semibold"><?= htmlspecialchars($successMsg) ?></p>
    </div>
    <?php endif; ?>

    <!-- Expiry warning -->
    <?php if ($expirySoon): ?>
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0"></i>
        <p class="text-amber-700 text-sm font-semibold">
            Votre abonnement expire dans <strong><?= $daysLeft ?> jour<?= $daysLeft>1?'s':'' ?></strong>. Renouvelez pour garder l'accès.
        </p>
    </div>
    <?php endif; ?>

    <!-- Greeting -->
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Bonjour, <?= htmlspecialchars($firstName) ?> 👋</h1>
        <p class="text-slate-500 text-sm mt-1">Voici l'état de votre abonnement.</p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

        <!-- CVs restants -->
        <div class="card col-span-2 p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">CVs restants</p>
                <div class="w-8 h-8 bg-sky-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="file-text" class="w-4 h-4 text-sky-500"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-4xl font-black text-slate-900"><?= $remaining ?></span>
                <span class="text-slate-400 text-sm font-semibold">/ <?= $member['cv_allowance'] ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $usedPct ?>%"></div>
            </div>
            <p class="text-[11px] text-slate-400 font-medium mt-2"><?= $member['cv_used'] ?> utilisé<?= $member['cv_used']>1?'s':'' ?> ce mois</p>
        </div>

        <!-- Plan -->
        <div class="card p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Plan</p>
                <div class="w-7 h-7 bg-indigo-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-indigo-500"></i>
                </div>
            </div>
            <p class="text-xl font-black text-slate-900 uppercase tracking-tight"><?= $planName ?></p>
            <span class="inline-block mt-2 px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full uppercase tracking-wider w-fit">Actif</span>
        </div>

        <!-- Expiry -->
        <div class="card p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Expiration</p>
                <div class="w-7 h-7 bg-slate-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-500"></i>
                </div>
            </div>
            <?php if ($expiryStr): ?>
                <p class="text-lg font-black text-slate-900"><?= $expiryStr ?></p>
                <p class="text-[11px] text-slate-400 font-medium mt-1">
                    <?= $daysLeft > 0 ? $daysLeft . ' jour' . ($daysLeft>1?'s':'') . ' restant' . ($daysLeft>1?'s':'') : '<span class="text-red-500">Expiré</span>' ?>
                </p>
            <?php else: ?>
                <p class="text-lg font-black text-slate-400">—</p>
                <p class="text-[11px] text-slate-400 font-medium mt-1">Non défini</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- CTA: New request -->
    <?php if ($remaining > 0): ?>
    <a href="request.php"
       class="block w-full bg-sky-500 hover:bg-sky-600 text-white rounded-2xl p-5 md:p-6 mb-8 transition-all active:scale-[.99] shadow-lg shadow-sky-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest opacity-80 mb-1">Action rapide</p>
                <p class="text-xl font-black">Optimiser un nouveau CV →</p>
                <p class="text-sky-200 text-sm mt-1 font-medium">Avec un lien ou une description de poste</p>
            </div>
            <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                <i data-lucide="plus" class="w-6 h-6"></i>
            </div>
        </div>
    </a>
    <?php else: ?>
    <div class="w-full bg-slate-100 border-2 border-dashed border-slate-200 rounded-2xl p-5 md:p-6 mb-8 text-center">
        <i data-lucide="alert-circle" class="w-6 h-6 text-slate-400 mx-auto mb-2"></i>
        <p class="font-black text-slate-500 text-sm">Quota mensuel atteint</p>
        <p class="text-slate-400 text-xs mt-1">Vos crédits se renouvellent au prochain cycle.</p>
        <a href="https://wa.me/237654250688?text=Bonjour+je+souhaite+upgrader+mon+pack+HireMe+Club"
           target="_blank"
           class="inline-block mt-4 px-5 py-2.5 bg-sky-500 text-white text-xs font-black rounded-xl hover:bg-sky-600 transition-all uppercase tracking-wide">
            Passer à un pack supérieur
        </a>
    </div>
    <?php endif; ?>

    <!-- Request history -->
    <div>
        <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">Historique des demandes</h2>

        <?php if (empty($requests)): ?>
        <div class="card py-16 text-center">
            <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="inbox" class="w-7 h-7 text-slate-300"></i>
            </div>
            <p class="font-black text-slate-400 text-sm uppercase tracking-widest">Aucune demande pour le moment</p>
            <p class="text-slate-300 text-xs mt-1">Cliquez sur "Optimiser un nouveau CV" pour commencer.</p>
        </div>
        <?php endif; ?>

        <div class="space-y-3">
        <?php foreach ($requests as $req):
            [$badgeClass, $badgeLabel] = $statusCfg[$req['status']] ?? ['bg-slate-100 text-slate-500 border border-slate-200', $req['status']];
            $isTextMode = ($req['job_url'] === '[description]');
        ?>
        <div class="card p-5 hover:shadow-md transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                <!-- Left: icon + info -->
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i data-lucide="<?= $isTextMode ? 'align-left' : 'link' ?>" class="w-4 h-4 text-slate-500"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="px-2.5 py-0.5 text-[10px] font-black rounded-full uppercase tracking-wider <?= $badgeClass ?>">
                                <?= $badgeLabel ?>
                            </span>
                            <?php if ($isTextMode): ?>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full">Description collée</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isTextMode): ?>
                        <a href="<?= htmlspecialchars($req['job_url']) ?>" target="_blank"
                           class="text-sm font-semibold text-slate-700 hover:text-sky-500 transition-colors truncate block">
                            <?= htmlspecialchars($req['job_url']) ?>
                        </a>
                        <?php else: ?>
                        <p class="text-sm font-semibold text-slate-500 truncate">
                            <?= htmlspecialchars(mb_substr($req['notes'], 0, 80)) ?>…
                        </p>
                        <?php endif; ?>
                        <p class="text-[11px] text-slate-400 font-medium mt-1">
                            <?= date('d/m/Y à H:i', strtotime($req['created_at'])) ?>
                        </p>
                    </div>
                </div>

                <!-- Right: download -->
                <div class="flex-shrink-0 pl-14 sm:pl-0">
                    <?php if ($req['status'] === 'delivered' && $req['delivered_cv_path']): ?>
                    <a href="../<?= htmlspecialchars($req['delivered_cv_path']) ?>" download
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-black text-xs hover:bg-emerald-600 transition-all active:scale-95 shadow-sm shadow-emerald-200">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i> Télécharger
                    </a>
                    <?php else: ?>
                    <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> En traitement
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

</main>

<!-- Mobile bottom nav shortcut -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-5 py-3 flex items-center justify-between safe-area-bottom">
    <div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">CVs restants</p>
        <p class="text-lg font-black text-slate-900"><?= $remaining ?> <span class="text-slate-400 text-xs font-semibold">/ <?= $member['cv_allowance'] ?></span></p>
    </div>
    <?php if ($remaining > 0): ?>
    <a href="request.php"
       class="bg-sky-500 text-white font-black text-sm px-6 py-3 rounded-xl hover:bg-sky-600 transition-all active:scale-95 shadow-lg shadow-sky-200 uppercase tracking-wide">
        Nouveau CV +
    </a>
    <?php endif; ?>
</div>

<script>
lucide.createIcons();
// Auto-dismiss toast after 5s
const t = document.querySelector('.toast');
if (t) setTimeout(() => t.style.display='none', 5000);
</script>
</body>
</html>
