<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$msg = '';

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_target') {
        $type = $_POST['type'];
        $period = $_POST['period'];
        $amount = $_POST['amount'];
        $start = $_POST['start_date'];
        $end = $_POST['end_date'];

        // Simple: Delete previous target of same type/period overlap (for simplicity in this SaaS v1)
        // Or just Insert new one. Let's just Insert diverse targets.
        $stmt = $pdo->prepare("INSERT INTO targets (type, period, target_amount, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$type, $period, $amount, $start, $end])) {
            $msg = "Objectif défini avec succès ! 🚀";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_target') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM targets WHERE id = ?")->execute([$id]);
        $msg = "Objectif supprimé.";
    }
}

// --- Data Fetching ---
$targets = $pdo->query("SELECT * FROM targets ORDER BY end_date DESC")->fetchAll();

// --- Calculate Progress for Each Target ---
foreach ($targets as &$target) {
    if ($target['type'] === 'revenue') {
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE payment_status = 'verified' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$target['start_date'] . ' 00:00:00', $target['end_date'] . ' 23:59:59']);
        $current = $stmt->fetchColumn() ?: 0;
    } else { // Sales count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE payment_status = 'verified' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$target['start_date'] . ' 00:00:00', $target['end_date'] . ' 23:59:59']);
        $current = $stmt->fetchColumn() ?: 0;
    }

    $target['current'] = $current;
    $target['percent'] = ($target['target_amount'] > 0) ? min(100, round(($current / $target['target_amount']) * 100)) : 0;
    
    // Days remaining
    $now = time();
    $end = strtotime($target['end_date']);
    $diff = $end - $now;
    $daysLeft = max(0, ceil($diff / (60 * 60 * 24)));
    $target['days_left'] = $daysLeft;
}
unset($target); // Break reference

?>
<!DOCTYPE html>
<html lang="fr">
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50 font-sans text-gray-800">

    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Objectifs Business 🎯</h1>

            <?php if ($msg): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 font-bold"><?= $msg ?></div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Target Setter Form -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-fit">
                    <h3 class="font-bold text-lg mb-4">Définir un Nouvel Objectif</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="set_target">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type d'objectif</label>
                            <select name="type" class="w-full bg-gray-50 border p-2 rounded-lg">
                                <option value="revenue">Chiffre d'Affaires (Revenue)</option>
                                <option value="sales">Nombre de Ventes (Volume)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Objectif (Cible)</label>
                            <input type="number" name="amount" required placeholder="Ex: 1000000 ou 50" class="w-full bg-gray-50 border p-2 rounded-lg font-bold text-blue-600">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                                <select name="period" class="w-full bg-gray-50 border p-2 rounded-lg">
                                    <option value="monthly">Mensuel</option>
                                    <option value="yearly">Annuel</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                                <input type="date" name="end_date" required value="<?= date('Y-m-t') ?>" class="w-full bg-gray-50 border p-2 rounded-lg">
                            </div>
                        </div>
                        
                        <input type="hidden" name="start_date" value="<?= date('Y-m-01') ?>"> <!-- Default start of month, user can edit if needed but keeping simple for v1 -->

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-200">
                            Fixer le Cap 🚀
                        </button>
                    </form>
                </div>

                <!-- Active Targets Display -->
                <div class="lg:col-span-2 space-y-6">
                    <h3 class="font-bold text-gray-700">Vos Missions en cours</h3>
                    
                    <?php if(empty($targets)): ?>
                        <div class="bg-white p-8 rounded-xl text-center text-gray-400 italic">
                            Aucun objectif défini. Cliquez sur "Fixer le Cap" pour démarrer !
                        </div>
                    <?php endif; ?>

                    <?php foreach ($targets as $t): ?>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="bg-blue-50 text-blue-600 text-[10px] uppercase font-black px-2 py-1 rounded">
                                        <?= ($t['type'] === 'revenue') ? '💰 Revenu' : '📦 Ventes' ?>
                                    </span>
                                    <span class="text-xs text-gray-400 ml-2 uppercase tracking-wide"><?= $t['period'] ?></span>
                                    <h4 class="text-2xl font-black mt-2">
                                        <?= ($t['type'] === 'revenue') ? number_format($t['current'], 0, ',', ' ') . ' / ' . number_format($t['target_amount'], 0, ',', ' ') . ' XAF' : $t['current'] . ' / ' . $t['target_amount'] . ' ventes' ?>
                                    </h4>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-black <?= $t['percent'] >= 100 ? 'text-green-500' : 'text-blue-600' ?>">
                                        <?= $t['percent'] ?>%
                                    </div>
                                    <div class="text-[10px] text-gray-400">Accompli</div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-100 rounded-full h-4 mb-2 overflow-hidden">
                                <div class="bg-blue-600 h-4 rounded-full transition-all duration-1000 ease-out" style="width: <?= $t['percent'] ?>%"></div>
                            </div>

                            <div class="flex justify-between items-center text-xs text-gray-500 mt-2">
                                <span>🚀 Restant : <?= ($t['type'] === 'revenue') ? number_format($t['target_amount'] - $t['current'], 0, ',', ' ') . ' XAF' : ($t['target_amount'] - $t['current']) . ' ventes' ?></span>
                                <span class="<?= $t['days_left'] < 5 ? 'text-red-500 font-bold' : '' ?>">⏳ <?= $t['days_left'] ?> jours restants</span>
                            </div>

                            <form method="POST" class="absolute top-4 right-4" onsubmit="return confirm('Abandonner cet objectif ?')">
                                <input type="hidden" name="action" value="delete_target">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <button class="text-gray-300 hover:text-red-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                            </form>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>

        </main>
    </div>
</body>
</html>
