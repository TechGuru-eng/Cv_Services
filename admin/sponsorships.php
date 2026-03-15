<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_request'])) {
        $reqId = $_POST['request_id'];
        
        // Find an Available Pool (First One with credits > 0 AND Verified Payment)
        // Link to orders table to check payment status
        $stmtPool = $pdo->query("
            SELECT p.* 
            FROM sponsorship_pool p
            JOIN orders o ON p.order_id = o.id
            WHERE p.credits_remaining > 0 
            AND o.payment_status = 'verified'
            ORDER BY p.created_at ASC 
            LIMIT 1
        ");
        $pool = $stmtPool->fetch();
        
        if ($pool) {
            $pdo->beginTransaction();
            try {
                // 1. Decrement Pool
                $stmtDec = $pdo->prepare("UPDATE sponsorship_pool SET credits_remaining = credits_remaining - 1 WHERE id = ?");
                $stmtDec->execute([$pool['id']]);
                
                // 2. Approve Request & Link
                $stmtApp = $pdo->prepare("UPDATE sponsorship_requests SET status = 'approved', allocated_pool_id = ? WHERE id = ?");
                $stmtApp->execute([$pool['id'], $reqId]);
                
                $pdo->commit();
                $msg = "Candidature approuvée ! Financée par " . htmlspecialchars($pool['donor_name']);
                
                // Here we could send a WhatsApp notification via API if we had one
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur allocation: " . $e->getMessage();
            }
        } else {
            $error = "Aucun crédit de parrainage validé disponible. Vérifiez les paiements.";
        }
    }
    
    if (isset($_POST['reject_request'])) {
        $reqId = $_POST['request_id'];
        $stmtRej = $pdo->prepare("UPDATE sponsorship_requests SET status = 'rejected' WHERE id = ?");
        $stmtRej->execute([$reqId]);
        $msg = "Candidature rejetée/archivée.";
    }
}

// Fetch Stats (Only Verified)
$totalRequests = $pdo->query("SELECT COUNT(*) FROM sponsorship_requests WHERE status = 'pending'")->fetchColumn();

// Count ONLY Verified Credits
$creditsAvailable = $pdo->query("
    SELECT SUM(p.credits_remaining) 
    FROM sponsorship_pool p
    JOIN orders o ON p.order_id = o.id
    WHERE o.payment_status = 'verified'
")->fetchColumn() ?: 0;

// Fetch Pending Requests
$requests = $pdo->query("SELECT * FROM sponsorship_requests WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll();

// Fetch History (Approved)
$history = $pdo->query("
    SELECT r.*, p.donor_name, p.order_id, o.whatsapp as donor_whatsapp, o.email as donor_email
    FROM sponsorship_requests r 
    LEFT JOIN sponsorship_pool p ON r.allocated_pool_id = p.id 
    LEFT JOIN orders o ON p.order_id = o.id
    WHERE r.status = 'approved' 
    ORDER BY r.created_at DESC 
    LIMIT 50
")->fetchAll();

// Fetch Pools (Only Verified)
$pools = $pdo->query("
    SELECT p.* 
    FROM sponsorship_pool p
    JOIN orders o ON p.order_id = o.id
    WHERE p.credits_remaining > 0 
    AND o.payment_status = 'verified'
    ORDER BY p.created_at DESC
")->fetchAll();

// Data for Analytics (Last 15 days)
$analytics = $pdo->query("
    SELECT date, SUM(donations) as donations, SUM(attributions) as attributions
    FROM (
        -- Only count donations from Verified orders
        SELECT DATE(p.created_at) as date, SUM(p.credits_total) as donations, 0 as attributions
        FROM sponsorship_pool p
        JOIN orders o ON p.order_id = o.id
        WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        AND o.payment_status = 'verified'
        GROUP BY DATE(p.created_at)
        
        UNION ALL
        
        SELECT DATE(created_at) as date, 0 as donations, COUNT(*) as attributions
        FROM sponsorship_requests 
        WHERE status = 'approved' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
    ) combined
    GROUP BY date
    ORDER BY date ASC
")->fetchAll();

$chartLabels = [];
$chartDonations = [];
$chartAttributions = [];
foreach ($analytics as $row) {
    $chartLabels[] = date('d M', strtotime($row['date']));
    $chartDonations[] = (int)$row['donations'];
    $chartAttributions[] = (int)$row['attributions'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight">Gestion des Parrainages center</h1>
                    <p class="text-gray-500 mt-1">Gérez l'impact social et l'attribution des CV solidaires.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="bg-teal-50 text-teal-600 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border border-teal-100 italic">
                        <?= $creditsAvailable ?> CV disponibles
                    </span>
                </div>
            </div>

            <?php if (isset($msg)): ?>
                <div class="bg-green-50 border border-green-100 text-green-700 p-4 rounded-2xl mb-8 flex items-center gap-3 animate-fade-in">
                    <span class="text-xl">✨</span> <?= $msg ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-100 text-red-700 p-4 rounded-2xl mb-8 flex items-center gap-3 animate-fade-in">
                    <span class="text-xl">⚠️</span> <?= $error ?>
                </div>
            <?php endif; ?>

            <style>
                .stat-card-glass {
                    background: rgba(255, 255, 255, 0.8);
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.4);
                    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05);
                }
                @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
                .animate-fade-in { animation: fade-in 0.5s ease-out forwards; }
            </style>

            <!-- Stats & Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-10">
                <!-- Stat 1 -->
                <div class="stat-card-glass p-6 rounded-[2rem] flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 group-hover:rotate-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" /></svg>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Offres en Stock</p>
                    </div>
                    <div>
                        <p class="text-4xl font-black text-gray-800 tracking-tighter"><?= $creditsAvailable ?></p>
                        <p class="text-xs font-bold text-gray-400 mt-1 uppercase">CVs à attribuer</p>
                    </div>
                </div>

                <!-- Stat 2 -->
                <div class="stat-card-glass p-6 rounded-[2rem] flex flex-col justify-between group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 group-hover:-rotate-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Demandes</p>
                    </div>
                    <div>
                        <p class="text-4xl font-black text-gray-800 tracking-tighter"><?= $totalRequests ?></p>
                        <p class="text-xs font-bold text-gray-400 mt-1 uppercase">Candidats en attente</p>
                    </div>
                </div>

                <!-- Graph Card -->
                <div class="lg:col-span-2 stat-card-glass p-6 rounded-[2rem]">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest italic">Courbe d'Impact Social (15j)</p>
                        <div class="flex gap-4">
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span class="text-[10px] font-bold text-gray-500 uppercase">Dons</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-teal-500"></span><span class="text-[10px] font-bold text-gray-500 uppercase">Reçus</span></div>
                        </div>
                    </div>
                    <div class="h-28">
                        <canvas id="impactChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left: Requests List -->
                <div class="lg:col-span-2 space-y-6">
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Candidatures Prioritaires (<?= count($requests) ?>)</h2>
                    
                    <?php if (empty($requests)): ?>
                        <div class="bg-white p-12 rounded-[2rem] shadow-sm border border-gray-100 text-center">
                            <div class="text-5xl mb-4">🌈</div>
                            <p class="text-gray-400 font-medium italic">Aucune demande en attente. Tout est sous contrôle !</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <div class="bg-white rounded-[2rem] shadow-sm p-8 border border-gray-100 relative group hover:shadow-md transition-shadow animate-fade-in">
                                <span class="absolute top-8 right-8 text-[10px] font-black text-gray-300 uppercase tracking-widest"><?= date('d M Y', strtotime($req['created_at'])) ?></span>
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center font-black text-white text-2xl shadow-lg group-hover:scale-110 transition-transform">
                                        <?= strtoupper(substr($req['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-xl text-gray-900 leading-tight"><?= htmlspecialchars($req['name']) ?></h3>
                                        <div class="flex items-center gap-3 mt-1">
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $req['whatsapp']) ?>" target="_blank" class="text-[10px] font-black text-green-600 uppercase tracking-wider hover:underline flex items-center gap-1">
                                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> WhatsApp: <?= htmlspecialchars($req['whatsapp']) ?>
                                            </a> 
                                            <span class="text-gray-300">•</span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter"><?= htmlspecialchars($req['email']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-6 rounded-2xl text-gray-800 text-sm leading-relaxed mb-8 border border-gray-100 italic relative">
                                    <svg class="absolute -top-3 -left-3 w-8 h-8 text-gray-200" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H16.017C14.9124 8 14.017 7.10457 14.017 6V3L14.017 2H21.017V9.017L21.017 15C21.017 17.5 19.017 21 15.017 21H14.017ZM3.017 21L3.017 18C3.017 16.8954 3.91243 16 5.017 16H8.017C8.56928 16 9.017 15.5523 9.017 15V9C9.017 8.44772 8.56928 8 8.017 8H5.017C3.91243 8 3.017 7.10457 3.017 6V3L3.017 2H10.017V9.017L10.017 15C10.017 17.5 8.017 21 4.017 21H3.017Z"/></svg>
                                    "<?= nl2br(htmlspecialchars($req['motivation'])) ?>"
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <?php if ($creditsAvailable > 0): ?>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" name="approve_request" class="bg-teal-600 text-white px-8 py-4 rounded-2xl shadow-xl shadow-teal-900/10 hover:bg-teal-700 font-black text-sm transition-all active:scale-95 flex items-center gap-2">
                                                ✅ Attribuer un CV
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button disabled class="bg-gray-100 text-gray-400 px-8 py-4 rounded-2xl font-black text-sm cursor-not-allowed italic border border-gray-200">
                                            Stock épuisé (trouvez des parrains)
                                        </button>
                                    <?php endif; ?>
                                    
                                    <form method="POST" onsubmit="return confirm('Confirmer le rejet ?');">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="reject_request" class="bg-red-50 text-red-500 px-8 py-4 rounded-2xl hover:bg-red-100 font-black text-sm transition-all border border-red-50/50">
                                            Rejeter
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Right: Pools & History -->
                <div class="space-y-8">
                    
                    <!-- Funding Pools -->
                    <div class="bg-white rounded-[2rem] shadow-sm p-6 border border-gray-100">
                        <h2 class="font-black text-gray-900 border-b border-gray-50 pb-4 mb-6 uppercase tracking-widest text-[10px]">Pools de Financement</h2>
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 no-scrollbar">
                            <?php foreach ($pools as $p): ?>
                                <div class="flex justify-between items-center text-sm bg-gray-50 p-4 rounded-2xl border border-gray-100 group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center font-black text-blue-600 text-xs shadow-sm">
                                            <?= strtoupper(substr($p['donor_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-black text-gray-800 leading-tight"><?= htmlspecialchars($p['donor_name']) ?></div>
                                            <div class="text-[9px] font-bold text-gray-400 uppercase"><?= $p['package_name'] ?></div>
                                        </div>
                                    </div>
                                    <div class="bg-teal-50 text-teal-600 px-3 py-1.5 rounded-xl font-black text-[10px] border border-teal-100">
                                        <?= $p['credits_remaining'] ?> CV
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($pools)) echo '<p class="text-gray-400 text-center text-xs italic py-8">Désert de dons...</p>'; ?>
                        </div>
                    </div>

                    <!-- History -->
                     <div class="bg-white rounded-[2rem] shadow-sm p-6 border border-gray-100">
                        <h2 class="font-black text-gray-900 border-b border-gray-50 pb-4 mb-6 uppercase tracking-widest text-[10px]">Historique d'Impact (Sponsored)</h2>
                         <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2 no-scrollbar">
                            <?php foreach ($history as $h): ?>
                                <div class="text-sm border-l-4 border-teal-500 bg-gray-50 p-5 rounded-r-2xl border-white group relative">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="font-black text-gray-900 text-base"><?= htmlspecialchars($h['name']) ?></div>
                                            <div class="flex flex-col gap-1 mt-1">
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $h['whatsapp']) ?>" target="_blank" class="text-[10px] font-bold text-green-600 hover:underline">📱 <?= htmlspecialchars($h['whatsapp']) ?></a>
                                                <span class="text-[10px] font-medium text-gray-500">📧 <?= htmlspecialchars($h['email']) ?></span>
                                            </div>
                                        </div>
                                        <div class="text-[9px] text-gray-400 font-bold"><?= date('d/m/Y', strtotime($h['created_at'])) ?></div>
                                    </div>
                                    
                                    <div class="bg-white p-3 rounded-xl border border-gray-100 text-[11px] text-gray-600 mb-4 italic">
                                        "<?= nl2br(htmlspecialchars(mb_strimwidth($h['motivation'], 0, 100, "..."))) ?>"
                                        <?php if(strlen($h['motivation']) > 100): ?>
                                            <button onclick="alert(<?= htmlspecialchars(json_encode($h['motivation'])) ?>)" class="text-blue-500 font-bold ml-1">Lire plus</button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="pt-3 border-t border-gray-100">
                                        <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Parrain / Donor</div>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center font-black text-blue-600 text-[10px]">
                                                <?= strtoupper(substr($h['donor_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-800 text-xs"><?= htmlspecialchars($h['donor_name'] ?: 'Anonyme') ?></div>
                                                <div class="flex gap-2 mt-0.5">
                                                    <?php if($h['donor_whatsapp']): ?>
                                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $h['donor_whatsapp']) ?>" target="_blank" class="text-[9px] font-bold text-blue-500">WA</a>
                                                    <?php endif; ?>
                                                    <?php if($h['donor_email']): ?>
                                                        <span class="text-[9px] font-bold text-gray-400"><?= htmlspecialchars($h['donor_email']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                             <?php if(empty($history)) echo '<p class="text-gray-400 text-center text-xs italic py-8">L\'histoire commence ici...</p>'; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- Chart.js and Analytics Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('impactChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Dons Receus',
                        data: <?= json_encode($chartDonations) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    },
                    {
                        label: 'CVs Attribués',
                        data: <?= json_encode($chartAttributions) ?>,
                        borderColor: '#14b8a6',
                        backgroundColor: 'rgba(20, 184, 166, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 0,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9, weight: 'bold' }, color: '#94a3b8' }
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
