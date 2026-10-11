<?php
// admin/litiges_dashboard.php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// --- Actions: Unban ---
if (isset($_GET['unban'])) {
    $id = intval($_GET['unban']);
    $pdo->prepare("DELETE FROM blacklisted_clients WHERE id = ?")->execute([$id]);
    header('Location: litiges_dashboard.php?msg=unbanned');
    exit;
}

// --- Data Aggregation ---

// 1. Core Summary Stats
$totalBanned = $pdo->query("SELECT COUNT(*) FROM blacklisted_clients")->fetchColumn();
$activeNonPayers = $pdo->query("SELECT COUNT(*) FROM orders WHERE is_non_payer = 1")->fetchColumn();
$revenueAtRisk = $pdo->query("SELECT SUM(amount) FROM orders WHERE is_non_payer = 1")->fetchColumn() ?: 0;
$avgReminder = $pdo->query("SELECT AVG(reminder_count) FROM orders WHERE is_non_payer = 1")->fetchColumn() ?: 0;

// 2. Bans Evolution (Last 30 Days)
$bansEvolution = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                              FROM blacklisted_clients 
                              WHERE created_at >= CURDATE() - INTERVAL 30 DAY 
                              GROUP BY DATE(created_at) 
                              ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Reminder Level Distribution
$remindersDistribution = $pdo->query("SELECT reminder_count, COUNT(*) as count 
                                       FROM orders 
                                       WHERE is_non_payer = 1 
                                       GROUP BY reminder_count 
                                       ORDER BY reminder_count ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Identifier Type Distribution
$typeStats = $pdo->query("SELECT type, COUNT(*) as count FROM blacklisted_clients GROUP BY type")->fetchAll(PDO::FETCH_ASSOC);

// 5. Recent Activity Table (Bans & Non-Payers)
$latestBans = $pdo->query("SELECT * FROM blacklisted_clients ORDER BY created_at DESC LIMIT 10")->fetchAll();
$latestNonPayers = $pdo->query("SELECT id, order_ref, name, whatsapp, amount, reminder_count, created_at 
                                FROM orders 
                                WHERE is_non_payer = 1 
                                ORDER BY created_at DESC LIMIT 10")->fetchAll();

?>
<?php include 'includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-10">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">Gestion des Litiges 🏴‍☠️</h1>
                    <p class="text-gray-500 font-medium italic">Surveillance des clients délinquants et bannissements.</p>
                </div>
                <div class="flex gap-3">
                    <a href="blacklist.php" class="bg-white border-2 border-gray-900 text-gray-900 px-6 py-3 rounded-xl font-black uppercase text-xs hover:bg-gray-900 hover:text-white transition shadow-sm">
                        Voir Liste Complète
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-widest">Utilisateurs Bannis</p>
                    <p class="text-3xl font-black text-red-600"><?= $totalBanned ?></p>
                    <div class="mt-2 text-[10px] text-gray-400">Accès restreint total</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-widest">Impayés Actifs</p>
                    <p class="text-3xl font-black text-orange-500"><?= $activeNonPayers ?></p>
                    <div class="mt-2 text-[10px] text-gray-400">En période de grâce</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-widest">Revenu à Risque</p>
                    <p class="text-3xl font-black text-green-600"><?= number_format($revenueAtRisk, 0, ',', ' ') ?> <span class="text-xs">FCFA</span></p>
                    <div class="mt-2 text-[10px] text-gray-400">Travail livré non payé</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-widest">Relance Moyenne</p>
                    <p class="text-3xl font-black text-blue-600"><?= round($avgReminder, 1) ?></p>
                    <div class="mt-2 text-[10px] text-gray-400">Rappels avant résolution/abandon</div>
                </div>
            </div>

            <!-- Visual Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-3"></span> Evolution des Bannissements
                    </h3>
                    <div class="h-[300px]">
                        <canvas id="bansChart"></canvas>
                    </div>
                </div>
                <div class="lg:col-span-1 bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h3 class="text-xl font-black text-slate-800 mb-8 flex items-center">
                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-3"></span> Niveaux de Relance
                    </h3>
                    <div class="h-[300px]">
                        <canvas id="remindersChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activity Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Latest Bans Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-red-50/50">
                        <h3 class="font-black text-gray-700 uppercase text-xs tracking-widest">Derniers Bannissements</h3>
                        <span class="text-[10px] font-bold text-red-600">Action Immédiate 🛡️</span>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                            <tr>
                                <th class="p-4">Identifiant</th>
                                <th class="p-4">Raison</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($latestBans as $b): ?>
                            <tr class="hover:bg-red-50/20 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-gray-800 text-xs"><?= htmlspecialchars($b['identifier']) ?></div>
                                    <div class="text-[9px] text-gray-400"><?= $b['type'] ?> • <?= date('d M', strtotime($b['created_at'])) ?></div>
                                </td>
                                <td class="p-4 text-[10px] text-gray-500 italic max-w-[150px] truncate">
                                    <?= htmlspecialchars($b['reason']) ?>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="?unban=<?= $b['id'] ?>" onclick="return confirm('Réautoriser cet utilisateur ?')" 
                                       class="text-[9px] font-black text-blue-600 uppercase hover:underline">Débloquer</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Active Litiges Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-orange-50/50">
                        <h3 class="font-black text-gray-700 uppercase text-xs tracking-widest">Impayés Actifs (Suivi)</h3>
                        <span class="text-[10px] font-bold text-orange-600">Période de grâce ⏳</span>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-black">
                            <tr>
                                <th class="p-4">Client</th>
                                <th class="p-4 text-center">Niveau</th>
                                <th class="p-4 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($latestNonPayers as $lnp): ?>
                            <tr class="hover:bg-orange-50/20 transition-colors">
                                <td class="p-4">
                                    <a href="order-details.php?id=<?= $lnp['id'] ?>" class="font-bold text-gray-800 text-xs hover:text-blue-600">
                                        <?= htmlspecialchars($lnp['name']) ?>
                                    </a>
                                    <div class="text-[9px] text-gray-400">Ref #<?= $lnp['order_ref'] ?> • <?= date('d M', strtotime($lnp['created_at'])) ?></div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="bg-<?= $lnp['reminder_count'] > 3 ? 'red' : 'blue' ?>-100 text-<?= $lnp['reminder_count'] > 3 ? 'red' : 'blue' ?>-700 px-2 py-0.5 rounded-full text-[9px] font-black">
                                        L<?= $lnp['reminder_count'] ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right font-black text-gray-700 text-xs">
                                    <?= number_format($lnp['amount'], 0, ',', ' ') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Data from PHP
        const bansEvolution = <?= json_encode($bansEvolution) ?>;
        const distributionData = <?= json_encode($remindersDistribution) ?>;

        // 1. Bans Evolution Chart
        new Chart(document.getElementById('bansChart'), {
            type: 'line',
            data: {
                labels: bansEvolution.map(d => d.date),
                datasets: [{
                    label: 'Nouveaux Bannissements',
                    data: bansEvolution.map(d => d.count),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });

        // 2. Reminders Level Distribution Chart
        new Chart(document.getElementById('remindersChart'), {
            type: 'bar',
            data: {
                labels: distributionData.map(d => 'L' + d.reminder_count),
                datasets: [{
                    label: 'Nombre de clients',
                    data: distributionData.map(d => d.count),
                    backgroundColor: [
                        '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8', '#1e40af'
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    </script>
</body>
</html>
