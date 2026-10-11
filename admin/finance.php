<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// --- Handle Form Submissions ---
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_expense') {
        $desc = $_POST['description'];
        $amt = $_POST['amount'];
        $cat = $_POST['category'];
        $date = $_POST['expense_date'];

        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, category, expense_date) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$desc, $amt, $cat, $date])) {
            $msg = "Dépense ajoutée avec succès !";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_expense') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
        $msg = "Dépense supprimée.";
    }
}

// --- Data Fetching: KPIs ---
$totalRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0;
$totalExpenses = $pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
$netProfit = $totalRevenue - $totalExpenses;
$margin = ($totalRevenue > 0) ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC LIMIT 50")->fetchAll();

// --- Data Fetching: Charts ---
// 1. Daily Revenue (Last 30 Days)
$dailyRevData = $pdo->query("
    SELECT DATE(created_at) as date, SUM(amount) as total 
    FROM orders 
    WHERE payment_status = 'verified' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fill missing days with 0 for continuous chart
$chartDates = [];
$chartValues = [];
$currentDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');
$dbDataIndexed = [];
foreach($dailyRevData as $row) { $dbDataIndexed[$row['date']] = $row['total']; }

while(strtotime($currentDate) <= strtotime($endDate)) {
    $chartDates[] = date('d/m', strtotime($currentDate));
    $chartValues[] = isset($dbDataIndexed[$currentDate]) ? $dbDataIndexed[$currentDate] : 0;
    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
}

// 2. Revenue by Pack
$packRevData = $pdo->query("
    SELECT COALESCE(package_name, 'Inconnu') as pack, SUM(amount) as total 
    FROM orders 
    WHERE payment_status = 'verified' 
    GROUP BY pack 
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$packLabels = []; $packValues = [];
foreach($packRevData as $row) {
    $packLabels[] = $row['pack'];
    $packValues[] = $row['total'];
}

// 3. Revenue by Payment Method
$methodRevData = $pdo->query("
    SELECT COALESCE(payment_method, 'Autre') as method, SUM(amount) as total 
    FROM orders 
    WHERE payment_status = 'verified' 
    GROUP BY method 
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

$methodLabels = []; $methodValues = [];
foreach($methodRevData as $row) {
    $methodLabels[] = $row['method'] ? $row['method'] : 'Autre';
    $methodValues[] = $row['total'];
}
?>
<?php include 'includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Tableau de Bord Financier 📈</h1>
                <p class="text-gray-500 mt-1">Analyse des revenus, marges et méthodes de paiement.</p>
            </div>

            <?php if ($msg): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 font-bold shadow-sm flex items-center gap-2">
                    ✅ <?= $msg ?>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- Revenue -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full flex items-center justify-center -mr-2 -mt-2">
                        <span class="text-2xl">💰</span>
                    </div>
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-wider">Chiffre d'Affaires</p>
                    <p class="text-2xl font-black text-blue-600"><?= number_format($totalRevenue, 0, ',', ' ') ?> XAF</p>
                </div>
                <!-- Expenses -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-full flex items-center justify-center -mr-2 -mt-2">
                        <span class="text-2xl">📉</span>
                    </div>
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-wider">Dépenses Totales</p>
                    <p class="text-2xl font-black text-red-500">-<?= number_format($totalExpenses, 0, ',', ' ') ?> XAF</p>
                </div>
                <!-- Net Profit -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden md:col-span-2 bg-gradient-to-br from-gray-900 to-gray-800 text-white">
                    <p class="text-gray-400 text-[10px] font-black uppercase mb-1 tracking-wider">Bénéfice Net (Profit)</p>
                    <p class="text-3xl font-black <?= $netProfit >= 0 ? 'text-green-400' : 'text-red-400' ?>">
                        <?= number_format($netProfit, 0, ',', ' ') ?> XAF
                    </p>
                    <div class="absolute right-6 top-6 bg-white/10 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-bold border border-white/20">
                        Marge Nette: <?= $margin ?>%
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Line Chart: Daily Revenue -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>📊</span> Évolution des Revenus (30 Derniers Jours)
                    </h3>
                    <div class="relative h-[300px] w-full">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Packs -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>🛍️</span> Revenus par Pack
                    </h3>
                    <div class="relative h-[250px] w-full flex justify-center">
                        <canvas id="packChart"></canvas>
                    </div>
                </div>
                
                <!-- Doughnut Chart: Payment Methods -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-1">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span>💳</span> Moyens de Paiement
                    </h3>
                    <div class="relative h-[250px] w-full flex justify-center">
                        <canvas id="methodChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Expenses Manager -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Expense Form -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <span>💸</span> Ajouter une Dépense
                    </h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_expense">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Description</label>
                            <input type="text" name="description" required placeholder="Ex: Hébergement Serveur, Publicité FB..." class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-xl text-sm outline-none focus:border-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Montant (XAF)</label>
                                <input type="number" name="amount" required class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-xl text-sm outline-none focus:border-red-500 text-red-600 font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                                <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-xl text-sm outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catégorie</label>
                            <select name="category" class="w-full bg-gray-50 border border-gray-200 p-2.5 rounded-xl text-sm outline-none">
                                <option value="Marketing">Marketing / Pubs</option>
                                <option value="Tech">Tech / Serveurs</option>
                                <option value="Salaire">Salaires / Freelances</option>
                                <option value="Admin">Administratif</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-md mt-2">
                            Enregistrer la dépense
                        </button>
                    </form>
                </div>

                <!-- Expense List -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
                    <h3 class="p-6 border-b border-gray-100 font-bold text-gray-800 flex items-center gap-2">
                        <span>📜</span> Historique des Sorties
                    </h3>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-400 uppercase text-[10px] font-black tracking-wider">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4">Description</th>
                                <th class="p-4">Catégorie</th>
                                <th class="p-4 text-right">Montant</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($expenses as $exp): ?>
                                <tr class="hover:bg-red-50/30 transition">
                                    <td class="p-4 text-xs font-bold text-gray-500"><?= date('d/m/Y', strtotime($exp['expense_date'])) ?></td>
                                    <td class="p-4 font-bold text-gray-800 text-sm"><?= htmlspecialchars($exp['description']) ?></td>
                                    <td class="p-4"><span class="bg-gray-100 border border-gray-200 px-2 py-1 rounded text-[10px] font-bold text-gray-600"><?= htmlspecialchars($exp['category']) ?></span></td>
                                    <td class="p-4 text-right font-black text-red-500 text-sm">-<?= number_format($exp['amount'], 0, ',', ' ') ?></td>
                                    <td class="p-4 text-right">
                                        <form method="POST" onsubmit="return confirm('Supprimer cette dépense ?')">
                                            <input type="hidden" name="action" value="delete_expense">
                                            <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                            <button class="text-gray-400 hover:text-red-600 text-[10px] font-bold uppercase tracking-wider underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($expenses)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-400 text-sm">Aucune dépense enregistrée. Tout est profit ! 🚀</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Chart Configuration -->
    <script>
        // 1. Revenue Line Chart
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 400);
        gradientRev.addColorStop(0, 'rgba(59, 130, 246, 0.5)'); // Blue-500 with opacity
        gradientRev.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartDates) ?>,
                datasets: [{
                    label: 'Chiffre d\'Affaires (XAF)',
                    data: <?= json_encode($chartValues) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: gradientRev,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { 
                        beginAtZero: true,
                        grid: { color: '#f1f5f9', borderDash: [5, 5] },
                        ticks: {
                            callback: function(value) { return value.toLocaleString() + ' F'; }
                        }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });

        // Shared Colors for Doughnut Charts
        const pieColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6366f1'];

        // 2. Packs Doughnut Chart
        new Chart(document.getElementById('packChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($packLabels) ?>,
                datasets: [{
                    data: <?= json_encode($packValues) ?>,
                    backgroundColor: pieColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });

        // 3. Methods Doughnut Chart
        new Chart(document.getElementById('methodChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($methodLabels) ?>,
                datasets: [{
                    data: <?= json_encode($methodValues) ?>,
                    backgroundColor: ['#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6'], // Custom colors for Momo/OM etc.
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    </script>
</body>
</html>
