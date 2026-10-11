<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Bulk Delete Action
if (isset($_POST['bulk_delete'])) {
    $ids = $_POST['order_ids'] ?? [];
    if (empty($ids)) {
        header('Location: cleanup_non_payers.php?error=no_selection');
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->beginTransaction();
    try {
        // 1. Get file paths to delete from server
        $stmtPaths = $pdo->prepare("SELECT completed_cv_path FROM orders WHERE id IN ($placeholders)");
        $stmtPaths->execute($ids);
        $paths = $stmtPaths->fetchAll(PDO::FETCH_COLUMN);

        // 2. Delete CV Details
        $stmtCv = $pdo->prepare("DELETE FROM cv_details WHERE order_id IN ($placeholders)");
        $stmtCv->execute($ids);

        // 3. Delete Orders
        $stmtOrders = $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
        $stmtOrders->execute($ids);

        // 4. Delete files if they exist
        foreach ($paths as $path) {
            if ($path && file_exists('../' . $path)) {
                unlink('../' . $path);
            }
        }

        $pdo->commit();
        header('Location: cleanup_non_payers.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: cleanup_non_payers.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Fetch expired non-payers
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT * FROM orders WHERE is_non_payer = 1 AND deletion_scheduled_at <= ? ORDER BY deletion_scheduled_at ASC");
$stmt->execute([$now]);
$expired = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">Nettoyage des CV Expirés</h1>
                <a href="orders.php" class="text-blue-600 font-bold hover:underline">← Retour Commandes</a>
            </div>

            <?php if ($msg === 'deleted'): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                    Données supprimées avec succès !
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                    Erreur: <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white shadow-xl rounded-2xl p-8 border border-red-100 mb-8">
                <h2 class="text-xl font-black text-red-600 mb-4 uppercase">CV en attente de suppression</h2>
                <p class="text-sm text-gray-500 mb-6 font-medium">Liste des commandes marquées comme "Non-payeur" dont la date de grâce est dépassée.</p>

                <form method="POST">
                    <div class="overflow-x-auto">
                        <table class="min-w-full leading-normal mb-6">
                            <thead>
                                <tr class="bg-red-50">
                                    <th class="px-4 py-3 text-left"><input type="checkbox" onclick="toggleAll(this)" class="rounded text-red-600"></th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-red-900 uppercase">Commande</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-red-900 uppercase">Client</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-red-900 uppercase">Date Expiration</th>
                                    <th class="px-5 py-3 text-left text-xs font-black text-red-900 uppercase">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($expired as $item): ?>
                                    <tr class="hover:bg-red-50/20 transition-colors">
                                        <td class="px-4 py-4">
                                            <input type="checkbox" name="order_ids[]" value="<?= $item['id'] ?>" class="order-chk rounded text-red-600">
                                        </td>
                                        <td class="px-5 py-4 font-mono text-sm font-bold">#<?= htmlspecialchars($item['order_ref']) ?></td>
                                        <td class="px-5 py-4">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-[10px] text-gray-400"><?= htmlspecialchars($item['email']) ?></div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="text-red-600 font-bold text-xs"><?= date('d/m/Y H:i', strtotime($item['deletion_scheduled_at'])) ?></span>
                                        </td>
                                        <td class="px-5 py-4 text-xs font-bold text-blue-600">
                                            <?= htmlspecialchars($item['whatsapp']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($expired)): ?>
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-gray-400 font-medium italic">
                                            Aucun CV n'est à supprimer pour le moment.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($expired)): ?>
                        <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50 p-6 rounded-xl border border-dashed border-gray-300">
                            <div class="text-sm font-bold text-gray-600">
                                <span id="selected-count">0</span> commande(s) sélectionnée(s)
                            </div>
                            <button type="submit" name="bulk_delete" onclick="return confirm('ATTENTION : Cette action supprimera DEFINITIVEMENT les CVs et les données clients. Continuer ?')"
                                class="bg-red-600 hover:bg-red-700 text-white font-black px-10 py-4 rounded-xl shadow-lg transition-transform hover:scale-105 active:scale-95 uppercase tracking-widest text-sm">
                                🗑️ SUPPRIMER LA SELECTION
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                    <h3 class="font-black text-blue-900 uppercase text-sm mb-2">💡 Note Admin</h3>
                    <p class="text-xs text-blue-700 leading-relaxed">
                        Cette page n'affiche que les CVs qui ont été explicitement marqués comme "Impayés" et dont le délai de grâce est passé. Les fichiers correspondants sur le serveur seront également supprimés.
                    </p>
                </div>
                <div class="bg-gray-900 p-6 rounded-2xl border border-gray-800 text-white">
                    <h3 class="font-black text-gray-400 uppercase text-sm mb-2">⚙️ Automatisation</h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        Pour automatiser cette tâche, vous pouvez configurer une tâche CRON qui appelle ce script périodiquement. Contactez le support technique pour plus de détails.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleAll(source) {
            checkboxes = document.getElementsByClassName('order-chk');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
            updateCount();
        }

        function updateCount() {
            let n = document.querySelectorAll('.order-chk:checked').length;
            document.getElementById('selected-count').innerText = n;
        }

        document.querySelectorAll('.order-chk').forEach(chk => {
            chk.addEventListener('change', updateCount);
        });
    </script>
</body>
</html>
