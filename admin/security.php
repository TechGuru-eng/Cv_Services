<?php
require_once 'auth.php';
require_once 'includes/csrf.php';
require_once 'includes/Security.php';
Security::init($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Requête invalide. Actualisez la page.');
    }
    foreach (['action', 'ip', 'reason', 'id'] as $field) {
        if (isset($_POST[$field]) && !is_string($_POST[$field])) {
            http_response_code(422);
            exit('Données invalides.');
        }
    }
    verify_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'ban_ip') {
            $ok = Security::banIP(trim($_POST['ip'] ?? ''), trim($_POST['reason'] ?? '') ?: 'Banni manuellement', $_SESSION['admin_email'] ?? 'Admin');
            $success = $ok ? 'Adresse IP bannie.' : 'Cette adresse IP est déjà bannie.';
        } elseif ($action === 'unban_ip') {
            Security::unbanIP((int)($_POST['id'] ?? 0));
            $success = 'Bannissement levé.';
        } elseif ($action === 'clear_logs') {
            $pdo->exec('DELETE FROM security_logs');
            $success = 'Historique vidé.';
        }
    } catch (InvalidArgumentException $e) { $error = $e->getMessage(); }
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

            <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <h2 class="text-xl font-bold mb-2">Protection active</h2>
                <p class="text-sm text-gray-600 mb-4">Les requêtes suspectes sont bloquées et journalisées. Après 3 détections ou 8 échecs de connexion en 15 minutes, l’IP est bannie. Localhost est journalisé sans bannissement automatique. Les adresses IP ne permettent pas d’identifier une personne avec certitude.</p>
                <form method="POST" class="flex flex-wrap gap-3 items-end">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="ban_ip">
                    <label>Adresse IP<input name="ip" required maxlength="45" placeholder="203.0.113.10" class="block border rounded p-2"></label>
                    <label>Raison<input name="reason" required maxlength="1000" class="block border rounded p-2"></label>
                    <button class="bg-red-600 text-white px-4 py-2 rounded" type="submit">Bannir cette IP</button>
                </form>
                <p class="text-sm mt-4">Les alertes apparaissent dans le journal ci-dessous. Actualisation automatique toutes les 30 secondes, sauf pendant la saisie. Aucun email de sécurité n’est envoyé.</p>
                <button type="button" onclick="location.reload()" class="text-blue-600 underline mt-2">Actualiser les alertes</button>
            </div>
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
                                                <?php csrf_field(); ?>
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
                            <?php csrf_field(); ?>
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
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="ban_ip">
                                                <input type="hidden" name="ip" value="<?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?>">
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
<script>
setInterval(() => {
    if (document.hidden) return;
    const typing = [...document.querySelectorAll('input:not([type="hidden"])')].some(input => input.value || input === document.activeElement);
    if (!typing) location.reload();
}, 30000);
</script>
</body>
</html>
