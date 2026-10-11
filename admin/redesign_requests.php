<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE cv_redesign_requests SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $requestId]);
    
    header('Location: redesign_requests.php?success=1');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT r.*, o.order_ref, o.package_name FROM cv_redesign_requests r LEFT JOIN orders o ON r.order_id = o.id";
$params = [];

if ($statusFilter) {
    $sql .= " WHERE r.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = "Demandes de Refonte (Garantie)";
?>
<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <title><?= $pageTitle ?> - HireMe Admin</title>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><?= $pageTitle ?></h1>
                
                <div class="flex gap-2">
                    <a href="redesign_requests.php" class="<?= empty($statusFilter) ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' ?> px-3 py-1.5 rounded shadow text-sm font-bold">Toutes</a>
                    <a href="redesign_requests.php?status=started" class="<?= $statusFilter == 'started' ? 'bg-gray-600 text-white' : 'bg-white text-gray-700' ?> px-3 py-1.5 rounded shadow text-sm font-bold">Abandonnées</a>
                    <a href="redesign_requests.php?status=pending" class="<?= $statusFilter == 'pending' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700' ?> px-3 py-1.5 rounded shadow text-sm font-bold">En attente</a>
                    <a href="redesign_requests.php?status=in_progress" class="<?= $statusFilter == 'in_progress' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700' ?> px-3 py-1.5 rounded shadow text-sm font-bold">En cours</a>
                    <a href="redesign_requests.php?status=done" class="<?= $statusFilter == 'done' ? 'bg-green-600 text-white' : 'bg-white text-gray-700' ?> px-3 py-1.5 rounded shadow text-sm font-bold">Terminées</a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    Statut mis à jour avec succès.
                </div>
            <?php endif; ?>

            <div class="bg-white shadow-md rounded-lg overflow-hidden overflow-x-auto">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Raison
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Preuves (Uploads)
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="font-bold"><?= htmlspecialchars($req['customer_name']) ?></div>
                                    <?php if ($req['order_id']): ?>
                                        <a href="order-details.php?id=<?= $req['order_id'] ?>" class="text-xs text-blue-600 hover:underline">
                                            Voir Commande (#<?= htmlspecialchars($req['order_ref'] ?? $req['order_id']) ?>)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Non rattaché (Manuel)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <div class="text-gray-500"><?= htmlspecialchars($req['customer_email']) ?></div>
                                    <div class="text-gray-500 font-mono"><?= htmlspecialchars($req['customer_whatsapp']) ?></div>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?php 
                                        if ($req['reason'] === 'no_success_3_months') echo "Aucun succès (3 mois)";
                                        elseif ($req['reason'] === 'recruiter_criticism') echo "Critiqué par recruteur";
                                        else echo "-";
                                    ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?php if ($req['payment_proof_path']): ?>
                                        <a href="../<?= htmlspecialchars($req['payment_proof_path']) ?>" target="_blank" class="text-blue-500 hover:underline block mb-1">Voir Reçu</a>
                                    <?php endif; ?>
                                    <?php if ($req['old_cv_path']): ?>
                                        <a href="../<?= htmlspecialchars($req['old_cv_path']) ?>" target="_blank" class="text-indigo-500 hover:underline block">Voir Ancien CV</a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="border border-gray-300 rounded px-2 py-1 text-xs font-bold
                                            <?php
                                            if ($req['status'] == 'done') echo 'bg-green-100 text-green-800';
                                            elseif ($req['status'] == 'pending') echo 'bg-yellow-100 text-yellow-800';
                                            elseif ($req['status'] == 'in_progress') echo 'bg-indigo-100 text-indigo-800';
                                            else echo 'bg-gray-100 text-gray-800';
                                            ?>
                                        ">
                                            <option value="started" <?= $req['status'] == 'started' ? 'selected' : '' ?>>Abandon (Formulaire Incomplet)</option>
                                            <option value="pending" <?= $req['status'] == 'pending' ? 'selected' : '' ?>>En attente</option>
                                            <option value="in_progress" <?= $req['status'] == 'in_progress' ? 'selected' : '' ?>>En cours de refonte</option>
                                            <option value="done" <?= $req['status'] == 'done' ? 'selected' : '' ?>>Refonte Terminée</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($requests) == 0): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500">
                                    Aucune demande trouvée.
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
