<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'ban_ip') {
            $ip = $_POST['ip'];
            $reason = $_POST['reason'] ?? 'Banni manuellement';
            if (Security::banIP($ip, $reason, $_SESSION['admin']['username'] ?? 'Admin')) {
                $success = "IP $ip a été bannie.";
            } else {
                $error = "Impossible de bannir l'IP (peut-être déjà bannie).";
            }
        } elseif ($_POST['action'] === 'unban_ip') {
            $id = $_POST['id'];
            if (Security::unbanIP($id)) {
                $success = "Le bannissement a été levé.";
            } else {
                $error = "Erreur lors du débannissement.";
            }
        } elseif ($_POST['action'] === 'clear_logs') {
            $pdo->exec("TRUNCATE TABLE security_logs");
            $success = "Historique vidé.";
        }
    }
}

// Fetch Data
$logs = $pdo->query("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 100")->fetchAll();
$bans = $pdo->query("SELECT * FROM banned_ips ORDER BY banned_at DESC")->fetchAll();

?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <title>Sécurité - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Sécurité & Bannissements 🔒</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Banned IPs -->
                <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden">
                    <div class="p-6 bg-red-50 border-b border-red-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-red-800">🚫 IP Bannies</h2>
                        <span class="bg-red-200 text-red-800 text-xs font-bold px-2 py-1 rounded-full"><?= count($bans) ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                                <tr>
                                    <th class="p-4">IP</th>
                                    <th class="p-4">Raison</th>
                                    <th class="p-4">Date</th>
                                    <th class="p-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($bans as $ban): ?>
                                    <tr class="hover:bg-red-50/30 transition">
                                        <td class="p-4 font-mono text-sm"><?= htmlspecialchars($ban['ip_address']) ?></td>
                                        <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($ban['reason']) ?></td>
                                        <td class="p-4 text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($ban['banned_at'])) ?></td>
                                        <td class="p-4 text-right">
                                            <form method="POST" onsubmit="return confirm('Débannir cette IP ?');">
                                                <input type="hidden" name="action" value="unban_ip">
                                                <input type="hidden" name="id" value="<?= $ban['id'] ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-bold text-xs bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded-lg transition">Levé</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($bans)): ?>
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-400 italic">Aucune IP bannie pour le moment.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Security Logs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800">📋 Journal d'activité (100 derniers)</h2>
                        <form method="POST" onsubmit="return confirm('Vider tout l\'historique ?');">
                            <input type="hidden" name="action" value="clear_logs">
                            <button type="submit" class="text-xs text-gray-500 hover:text-red-500 underline">Tout effacer</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs font-bold">
                                <tr>
                                    <th class="p-4">IP / Détails</th>
                                    <th class="p-4">Raison</th>
                                    <th class="p-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4">
                                            <div class="font-mono text-sm font-bold text-blue-600"><?= htmlspecialchars($log['ip_address']) ?></div>
                                            <div class="text-[10px] text-gray-400 mt-1" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                                <?= date('d/m H:i', strtotime($log['created_at'])) ?> • <?= htmlspecialchars(substr($log['request_uri'], 0, 20)) ?>...
                                            </div>
                                        </td>
                                        <td class="p-4 text-xs text-red-600 font-medium bg-red-50/50 rounded-lg">
                                            <?= htmlspecialchars($log['reason']) ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <form method="POST" onsubmit="return confirm('Bannir cette IP ?');">
                                                <input type="hidden" name="action" value="ban_ip">
                                                <input type="hidden" name="ip" value="<?= $log['ip_address'] ?>">
                                                <input type="hidden" name="reason" value="Suite à log: <?= htmlspecialchars($log['reason']) ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-bold text-xs bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">Bannir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="3" class="p-6 text-center text-gray-400 italic">Rien à signaler.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
