<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

$id = $_GET['id'] ?? 0;

// Fetch Order first to get current status
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order)
    die("Commande introuvable.");

// Update Status Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];
    $oldStatus = $order['payment_status'];

    if ($newStatus !== $oldStatus) {
        $pdo->beginTransaction();
        try {
            // 1. Update Order Status
            $stmtUpdate = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);

            // 2. Handle Revenue Logic
            if ($newStatus === 'verified' && $oldStatus !== 'verified') {
                // Changing to Verified (Add Revenue)
                // Check if already exists to avoid dupes (defensive)
                $stmtCheck = $pdo->prepare("SELECT id FROM revenue WHERE order_id = ?");
                $stmtCheck->execute([$id]);
                if (!$stmtCheck->fetch()) {
                    $stmtRev = $pdo->prepare("INSERT INTO revenue (order_id, amount, payment_method, created_at) VALUES (?, ?, ?, NOW())");
                    $stmtRev->execute([$id, $order['amount'], $order['payment_method']]);
                }
            } elseif ($oldStatus === 'verified' && $newStatus !== 'verified') {
                // Changing from Verified to Unverified (Remove Revenue)
                $stmtDel = $pdo->prepare("DELETE FROM revenue WHERE order_id = ?");
                $stmtDel->execute([$id]);
            }

            $pdo->commit();
            header("Location: order-details.php?id=$id&msg=updated");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur mise à jour: " . $e->getMessage();
        }
    } else {
        header("Location: order-details.php?id=$id"); // No change
        exit;
    }
}

// Fetch CV Details
$stmtCv = $pdo->prepare("SELECT * FROM cv_details WHERE order_id = ?");
$stmtCv->execute([$id]);
$cvDetails = $stmtCv->fetch();

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Détails Commande #<?= $order['order_ref'] ?? $id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include 'includes/nav.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="orders.php" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">← Retour
                        Commandes</a>
                    <h1 class="text-3xl font-bold text-gray-800">Commande <span
                            class="text-blue-600">#<?= htmlspecialchars($order['order_ref'] ?? $order['id']) ?></span>
                    </h1>
                </div>

                <form method="POST" class="flex items-center space-x-2 bg-white p-2 rounded shadow-sm">
                    <label class="font-bold text-sm text-gray-600 mr-2">Statut:</label>
                    <select name="status"
                        class="bg-gray-50 border p-2 rounded focus:ring-blue-500 focus:border-blue-500">
                        <option value="pending" <?= $order['payment_status'] == 'pending' ? 'selected' : '' ?>>En attente
                        </option>
                        <option value="verified" <?= $order['payment_status'] == 'verified' ? 'selected' : '' ?>>✅ Vérifié
                            (Payé)</option>
                        <option value="failed" <?= $order['payment_status'] == 'failed' ? 'selected' : '' ?>>❌ Échoué /
                            Annulé</option>
                    </select>
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">Sauvegarder</button>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Order Info -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Infos Client & Paiement</h2>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-500 text-sm">Client</span>
                                <div class="font-bold"><?= htmlspecialchars($order['name']) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Email</span>
                                <div class="font-bold"><?= htmlspecialchars($order['email']) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">WhatsApp</span>
                                <div class="font-bold"><?= htmlspecialchars($order['whatsapp']) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Pays</span>
                                <div class="font-bold"><?= htmlspecialchars($order['country']) ?></div>
                            </div>
                            <hr class="my-2">
                            <div>
                                <span class="text-gray-500 text-sm">Pack</span>
                                <div class="font-bold text-blue-600">
                                    <?= htmlspecialchars($order['package_name'] ?? 'Standard') ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Montant</span>
                                <div class="font-bold text-xl text-green-600">
                                    <?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Méthode</span>
                                <div class="font-bold"><?= htmlspecialchars($order['payment_method']) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Transaction ID</span>
                                <div class="font-mono bg-gray-100 p-1 rounded text-sm break-all">
                                    <?= htmlspecialchars($order['transaction_number']) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Date</span>
                                <div class="font-bold"><?= $order['created_at'] ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Preuve de Paiement</h2>
                        <?php if ($order['payment_proof']): ?>
                            <a href="../<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank">
                                <img src="../<?= htmlspecialchars($order['payment_proof']) ?>" alt="Preuve"
                                    class="w-full h-auto rounded border hover:opacity-75 transition">
                            </a>
                            <div class="mt-2 text-center">
                                <a href="../<?= htmlspecialchars($order['payment_proof']) ?>" download
                                    class="text-sm text-blue-500 underline">Télécharger l'image</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 bg-gray-50 rounded border border-dashed">
                                <span class="text-gray-400">Aucune preuve téléversée par le client.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CV Info -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg p-6 h-full">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Données du Formulaire CV</h2>
                        <?php if ($cvDetails):
                            $personal = json_decode($cvDetails['personal_info'], true);
                            $edu = json_decode($cvDetails['education'], true);
                            $exp = json_decode($cvDetails['experience'], true);
                            $skills = json_decode($cvDetails['skills'], true);
                            ?>
                            <div class="mb-6">
                                <h3 class="font-bold text-gray-700 bg-gray-50 p-2 rounded mb-2">Liens & Fichiers</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <strong>LinkedIn:</strong>
                                        <?php if (!empty($cvDetails['linkedin'])): ?>
                                            <a href="<?= htmlspecialchars($cvDetails['linkedin']) ?>" target="_blank"
                                                class="text-blue-500 hover:underline"><?= htmlspecialchars($cvDetails['linkedin']) ?></a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Non fourni</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong>Ancien CV:</strong>
                                        <?php if ($cvDetails['uploaded_cv_path']): ?>
                                            <a href="../<?= htmlspecialchars($cvDetails['uploaded_cv_path']) ?>" target="_blank"
                                                class="bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200 transition inline-flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                    </path>
                                                </svg>
                                                Télécharger le CV Original
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">Aucun fichier</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h3 class="font-bold text-gray-700 bg-gray-50 p-2 rounded mb-2">Expérience Professionnelle
                                </h3>
                                <div
                                    class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap bg-white border p-4 rounded">
                                    <?= htmlspecialchars($exp) ?></div>
                            </div>

                            <div class="mb-6">
                                <h3 class="font-bold text-gray-700 bg-gray-50 p-2 rounded mb-2">Formation & Diplômes</h3>
                                <div
                                    class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap bg-white border p-4 rounded">
                                    <?= htmlspecialchars($edu) ?></div>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-700 bg-gray-50 p-2 rounded mb-2">Compétences & Langues</h3>
                                <div
                                    class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap bg-white border p-4 rounded">
                                    <?= htmlspecialchars($skills['skills'] ?? '') ?></div>
                            </div>

                        <?php else: ?>
                            <div class="text-center py-12 bg-gray-50 rounded border border-dashed text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <p>Le client n'a pas encore rempli le formulaire de détails (Étape 2).</p>
                                <p class="text-sm">Seules les informations de commande (Étape 1) sont disponibles.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>

</html>