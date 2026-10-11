<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Unban Action
if (isset($_GET['unban'])) {
    $id = intval($_GET['unban']);
    $pdo->prepare("DELETE FROM blacklisted_clients WHERE id = ?")->execute([$id]);
    header('Location: blacklist.php?msg=unbanned');
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM blacklisted_clients";
$params = [];

if ($search) {
    $sql .= " WHERE identifier LIKE ? OR reason LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$banned = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">Liste Noire (Bannis)</h1>
                <div class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm font-bold">
                    Total: <?= count($banned) ?>
                </div>
            </div>

            <!-- Search -->
            <form class="mb-6 flex gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Rechercher un numéro ou email..."
                    class="w-full md:w-1/3 p-3 border rounded-lg focus:ring-red-500 focus:border-red-500 bg-gray-50">
                <button type="submit"
                    class="bg-gray-900 text-white px-8 py-3 rounded-lg hover:bg-black transition font-bold uppercase text-sm">Filtrer</button>
            </form>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-400 uppercase">Identifiant</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-400 uppercase">Raison</th>
                            <th class="px-6 py-4 text-left text-xs font-black text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-black text-gray-400 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($banned as $item): ?>
                            <tr class="hover:bg-red-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-black text-gray-900"><?= htmlspecialchars($item['identifier']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-[10px] font-black uppercase <?= $item['type'] == 'phone' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
                                        <?= $item['type'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($item['reason']) ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400 font-mono">
                                    <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="blacklist.php?unban=<?= $item['id'] ?>" 
                                       onclick="return confirm('Réautoriser cet utilisateur ?')"
                                       class="text-red-600 hover:text-red-900 font-black text-xs uppercase tracking-widest border-b-2 border-transparent hover:border-red-600 transition-all">
                                        Débloquer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($banned)): ?>
                            <tr>
                                <td colspan="5" class="py-20 text-center text-gray-400">
                                    <div class="text-4xl mb-4">🛡️</div>
                                    <p class="font-bold">Aucun utilisateur banni pour le moment.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
