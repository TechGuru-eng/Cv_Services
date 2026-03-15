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

// --- Data Fetching ---
// 1. Totals
$totalRevenue = $pdo->query("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified'")->fetchColumn() ?: 0;
$totalExpenses = $pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn() ?: 0;
$netProfit = $totalRevenue - $totalExpenses;

// 2. Recent Expenses
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, created_at DESC LIMIT 50")->fetchAll();

// 3. Profit Margin Calculation
$margin = ($totalRevenue > 0) ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Finance & Cashflow 💰</h1>

            <?php if ($msg): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 font-bold"><?= $msg ?></div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Revenue -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-xs font-black uppercase mb-1">Chiffre d'Affaires</p>
                    <p class="text-3xl font-black text-blue-600"><?= number_format($totalRevenue, 0, ',', ' ') ?> XAF</p>
                </div>
                <!-- Expenses -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <p class="text-gray-400 text-xs font-black uppercase mb-1">Dépenses Totales</p>
                    <p class="text-3xl font-black text-red-500">-<?= number_format($totalExpenses, 0, ',', ' ') ?> XAF</p>
                </div>
                <!-- Net Profit -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                    <p class="text-gray-400 text-xs font-black uppercase mb-1">Bénéfice Net (Profit)</p>
                    <p class="text-3xl font-black <?= $netProfit >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                        <?= number_format($netProfit, 0, ',', ' ') ?> XAF
                    </p>
                    <div class="absolute right-4 top-4 bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold">
                        Marge: <?= $margin ?>%
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Add Expense Form -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                    <h3 class="font-bold text-lg mb-4">Ajouter une Dépense</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_expense">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" required placeholder="Ex: Hébergement Serveur, Publicité FB..." class="w-full bg-gray-50 border p-2 rounded-lg">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant (XAF)</label>
                                <input type="number" name="amount" required class="w-full bg-gray-50 border p-2 rounded-lg text-red-600 font-bold">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>" class="w-full bg-gray-50 border p-2 rounded-lg">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                            <select name="category" class="w-full bg-gray-50 border p-2 rounded-lg">
                                <option value="Marketing">Marketing / Pubs</option>
                                <option value="Tech">Tech / Serveurs</option>
                                <option value="Salaire">Salaires / Freelances</option>
                                <option value="Admin">Administratif</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-red-200">
                            Enregistrer la dépense 💸
                        </button>
                    </form>
                </div>

                <!-- Expense List -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden overflow-x-auto">
                    <h3 class="p-6 border-b font-bold bg-gray-50 text-gray-700">Historique des Sorties</h3>
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4">Description</th>
                                <th class="p-4">Catégorie</th>
                                <th class="p-4 text-right">Montant</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($expenses as $exp): ?>
                                <tr class="hover:bg-red-50/30 transition">
                                    <td class="p-4 text-sm text-gray-500"><?= date('d/m/Y', strtotime($exp['expense_date'])) ?></td>
                                    <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($exp['description']) ?></td>
                                    <td class="p-4"><span class="bg-gray-100 px-2 py-1 rounded text-xs text-gray-600"><?= htmlspecialchars($exp['category']) ?></span></td>
                                    <td class="p-4 text-right font-black text-red-500">-<?= number_format($exp['amount'], 0, ',', ' ') ?></td>
                                    <td class="p-4 text-right">
                                        <form method="POST" onsubmit="return confirm('Supprimer cette dépense ?')">
                                            <input type="hidden" name="action" value="delete_expense">
                                            <input type="hidden" name="id" value="<?= $exp['id'] ?>">
                                            <button class="text-gray-400 hover:text-red-600 text-xs underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($expenses)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-400 italic">Aucune dépense enregistrée. Tout est profit ! (Ou pas encore noté 😅)</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
