<?php
session_start();
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';

// Auto-migration: Ensure completed_cv_path exists to prevent SQL errors
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM orders LIKE 'completed_cv_path'");
    if (!$checkCol->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN completed_cv_path VARCHAR(255) DEFAULT NULL AFTER payment_proof");
    }
} catch (Exception $e) {
    // Continue execution even if check fails, though error is likely if column genuinely missing
    error_log("Auto-migration warning: " . $e->getMessage());
}

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

                // 3. Handle Affiliate Commission
                if (!empty($order['promo_code_used'])) {
                     // Check if it's an affiliate code (some apps use promo table for affiliates)
                     // In this app, affiliates and promos are separate but sometimes overlap.
                }
                
                if (!empty($order['affiliate_code'])) {
                    $stmtCheckAff = $pdo->prepare("SELECT id FROM affiliate_referrals WHERE order_id = ?");
                    $stmtCheckAff->execute([$id]);
                    if (!$stmtCheckAff->fetch()) {
                        $stmtAff = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ?");
                        $stmtAff->execute([$order['affiliate_code']]);
                        $affiliate = $stmtAff->fetch();

                        if ($affiliate) {
                            $affId = $affiliate['id'];
                            $pdo->prepare("UPDATE affiliates SET balance = balance + 500, total_earned = total_earned + 500 WHERE id = ?")->execute([$affId]);
                            $pdo->prepare("INSERT INTO affiliate_referrals (affiliate_id, order_id, commission_amount, status) VALUES (?, ?, 500, 'verified')")->execute([$affId, $id]);
                        }
                    }
                }
                
                // 4. Sync Prospects (NEW)
                // If this user was in the prospects list, mark them as converted immediately
                require_once 'includes/sync_helper.php';
                syncProspectsWithOrders($pdo);

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
        header("Location: order-details.php?id=$id"); 
        exit;
    }
}

// Apply Promo Code Logic (NEW)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promoCode = strtoupper(trim($_POST['promo_code']));
    $today = date('Y-m-d');

    $stmtPromo = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND (start_date <= ? AND end_date >= ?)");
    $stmtPromo->execute([$promoCode, $today, $today]);
    $promo = $stmtPromo->fetch();

    if ($promo) {
        $baseAmount = $order['original_amount'] > 0 ? $order['original_amount'] : $order['amount'];
        
        $newDiscount = 0;
        if ($promo['discount_type'] === 'percent') {
            $newDiscount = round($baseAmount * ($promo['discount_value'] / 100));
        } else {
            $newDiscount = $promo['discount_value'];
        }
        
        $newAmount = max(0, $baseAmount - $newDiscount);

        $pdo->beginTransaction();
        try {
            // Update Order
            $stmtUpdate = $pdo->prepare("UPDATE orders SET amount = ?, original_amount = ?, discount_amount = ?, promo_code_used = ? WHERE id = ?");
            $stmtUpdate->execute([$newAmount, $baseAmount, $newDiscount, $promoCode, $id]);

            // Update Revenue if verified
            if ($order['payment_status'] === 'verified') {
                $stmtRevUpdate = $pdo->prepare("UPDATE revenue SET amount = ? WHERE order_id = ?");
                $stmtRevUpdate->execute([$newAmount, $id]);
            }

            $pdo->commit();
            header("Location: order-details.php?id=$id&msg=promo_applied");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur promo: " . $e->getMessage();
        }
    } else {
        $error = "Code promo invalide ou expiré.";
    }
}


// Update Financials Logic (Manual Override - NEW)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_financials'])) {
    $newAmount = floatval($_POST['manual_amount']);
    $newMethod = trim($_POST['manual_method']);
    
    $pdo->beginTransaction();
    try {
        // Update Order
        $stmtUpdate = $pdo->prepare("UPDATE orders SET amount = ?, payment_method = ? WHERE id = ?");
        $stmtUpdate->execute([$newAmount, $newMethod, $id]);

        // Update Revenue if verified
        if ($order['payment_status'] === 'verified') {
            // Check if revenue record exists
            $stmtCheck = $pdo->prepare("SELECT id FROM revenue WHERE order_id = ?");
            $stmtCheck->execute([$id]);
            if ($stmtCheck->fetch()) {
                $stmtRevUpdate = $pdo->prepare("UPDATE revenue SET amount = ?, payment_method = ? WHERE order_id = ?");
                $stmtRevUpdate->execute([$newAmount, $newMethod, $id]);
            } else {
                 // Insert if missing but verified
                 $stmtRev = $pdo->prepare("INSERT INTO revenue (order_id, amount, payment_method, created_at) VALUES (?, ?, ?, NOW())");
                 $stmtRev->execute([$id, $newAmount, $newMethod]);
            }
        }

        $pdo->commit();
        header("Location: order-details.php?id=$id&msg=financials_updated");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur mise à jour financière: " . $e->getMessage();
    }
}

// Fetch CV Details
$stmtCv = $pdo->prepare("SELECT * FROM cv_details WHERE order_id = ?");
$stmtCv->execute([$id]);
$cvDetails = $stmtCv->fetch();

// Handle Completed CV Upload
$uploadError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['completed_cv'])) {
    $file = $_FILES['completed_cv'];
    
    // Check specific error codes
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $uploadError = "Le fichier est trop volumineux (Max " . ini_get('upload_max_filesize') . ").";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = "Erreur upload: code " . $file['error'];
    } else {
        // Success Logic
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'doc', 'docx'])) {
            $uploadDir = '../uploads/completed_cvs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = 'CV_FINAL_' . ($order['order_ref'] ?? $id) . '_' . time() . '.' . $ext; // Unique hash
            $destPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Save relative path to DB
                $dbPath = 'uploads/completed_cvs/' . $newFilename; 
                try {
                    $stmtUpd = $pdo->prepare("UPDATE orders SET completed_cv_path = ? WHERE id = ?");
                    $stmtUpd->execute([$dbPath, $id]);
                    
                    // Refresh
                    echo "<script>window.location.href='order-details.php?id=$id&msg=cv_uploaded';</script>";
                    exit;
                } catch (PDOException $e) {
                     $uploadError = "Erreur Base de données: " . $e->getMessage();
                }
            } else {
                $uploadError = "Erreur lors du déplacement du fichier. Vérifiez les permissions.";
            }
        } else {
            $uploadError = "Format invalide (PDF, DOC, DOCX uniquement).";
        }
    }
}


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
                <div class="flex items-start gap-4">
                    <div>
                        <a href="orders.php" class="text-gray-500 hover:text-gray-700 mb-2 inline-block text-sm">← Retour Commandes</a>
                        <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Commande <span class="text-blue-600">#<?= htmlspecialchars($order['order_ref'] ?? $order['id']) ?></span></h1>
                    </div>
                    <a href="receipt.php?id=<?= $id ?>" target="_blank" class="mt-8 bg-white border border-slate-200 text-slate-800 px-4 py-2 rounded-xl text-xs font-black shadow-sm hover:shadow-md hover:border-blue-400 transition-all flex items-center gap-2">
                        <span>🧾 GÉNÉRER REÇU</span>
                    </a>
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
                                <div class="font-bold"><?= htmlspecialchars((string)($order['name'] ?? 'Inconnu')) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Email</span>
                                <div class="font-bold"><?= htmlspecialchars((string)($order['email'] ?? 'N/A')) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">WhatsApp</span>
                                <div class="font-bold"><?= htmlspecialchars($order['whatsapp'] ?? '') ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Pays</span>
                                <div class="font-bold"><?= htmlspecialchars($order['country'] ?? '') ?></div>
                            </div>
                            <hr class="my-2">
                            <div>
                                <span class="text-gray-500 text-sm">Pack</span>
                                <div class="font-bold text-blue-600">
                                    <?= htmlspecialchars($order['package_name'] ?? 'Standard') ?></div>
                                    
                                    <?php if (!empty($order['has_recruiter_list'])): ?>
                                        <div class="mt-1 text-xs bg-indigo-100 text-indigo-700 font-bold px-2 py-1 rounded inline-block">
                                            + Bible Recruteur
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (stripos($order['package_name'], 'Lettre') !== false): ?>
                                        <div class="mt-1 text-xs bg-yellow-100 text-yellow-700 font-bold px-2 py-1 rounded inline-block">
                                            + Pack Lettres
                                        </div>
                                    <?php endif; ?>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Montant</span>
                                <div class="font-bold text-xl text-green-600">
                                    <?= number_format($order['amount'], 0, ',', ' ') ?> FCFA</div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Méthode</span>
                                <div class="font-bold"><?= htmlspecialchars((string)($order['payment_method'] ?? 'N/A')) ?></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Transaction ID</span>
                                <div class="font-mono bg-gray-100 p-1 rounded text-sm break-all">
                                    <?= htmlspecialchars((string)($order['transaction_number'] ?? 'N/A')) ?></div>
                            </div>
                            <div>
                                 <span class="text-gray-500 text-sm">Date de commande</span>
                                 <div class="font-bold"><?= $order['created_at'] ?></div>
                             </div>
                             <?php if (!empty($order['due_date'])): ?>
                                 <div>
                                     <span class="text-gray-500 text-sm">📅 Date de livraison souhaitée</span>
                                     <div class="font-bold text-red-600 text-lg"><?= date('d/m/Y', strtotime($order['due_date'])) ?></div>
                                 </div>
                             <?php endif; ?>
                        </div>
                    </div>

                    <?php if (($order['order_type'] ?? '') === 'advance'): 
                        $waConfMsg = "Bonjour " . explode(' ', trim($order['name']))[0] . " ! 👋\n\nJ'ai bien reçu votre réservation pour le " . ($order['package_name'] ?? 'Pack Standard') . ".\n\nVous avez demandé une livraison pour le " . date('d/m/Y', strtotime($order['due_date'])) . ". Est-ce que cela vous convient toujours ?\n\nNous attendons votre confirmation pour lancer la rédaction.";
                        $waPhone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                        if(strlen($waPhone) == 9 && substr($waPhone, 0, 1) == '6') $waPhone = '237' . $waPhone;
                        $waConfLink = "https://wa.me/$waPhone?text=" . urlencode($waConfMsg);
                    ?>
                        <div class="bg-purple-50 border border-purple-100 shadow rounded-lg p-6">
                            <h2 class="text-xl font-bold mb-4 border-b border-purple-200 pb-2 text-purple-800">🛠️ Action Réservation</h2>
                            <p class="text-sm text-purple-600 mb-4">Confirmez la réservation avec le client sur WhatsApp.</p>
                            <a href="<?= $waConfLink ?>" target="_blank" class="block w-full text-center bg-purple-600 text-white font-bold py-3 rounded-xl shadow hover:bg-purple-700 transition">
                                Confirmer sur WhatsApp
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Apply Promo Card (NEW) -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Appliquer un Coupon</h2>
                        <?php if (isset($error)): ?>
                            <div class="bg-red-100 text-red-700 p-2 rounded text-xs mb-4"><?= $error ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'promo_applied'): ?>
                            <div class="bg-green-100 text-green-700 p-2 rounded text-xs mb-4">Coupon appliqué avec succès !</div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Code Promo</label>
                                <input type="text" name="promo_code" value="<?= htmlspecialchars($order['promo_code_used'] ?? '') ?>" 
                                    class="w-full border p-2 rounded bg-gray-50 font-mono uppercase focus:bg-white" placeholder="EX: HIREME60">
                            </div>
                            <button type="submit" name="apply_promo" class="w-full bg-neutral text-white font-bold py-2 rounded shadow hover:bg-black transition">Appliquer à la commande</button>
                            <p class="text-[10px] text-gray-400 italic">Note: Appliquer un coupon écrasera le montant actuel de la commande. Utilisez avec précaution.</p>
                        </form>
                    </div>

                    <!-- Admin Manual Override -->
                    <div class="bg-red-50 border border-red-100 shadow rounded-lg p-6 mt-6">
                        <h2 class="text-xl font-bold mb-4 border-b border-red-200 pb-2 text-red-800">Zone Admin : Correction Financière</h2>
                        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'financials_updated'): ?>
                            <div class="bg-green-100 text-green-700 p-2 rounded text-xs mb-4">Mise à jour effectuée !</div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-red-400 uppercase mb-1">Montant Payé (FCFA)</label>
                                <input type="number" name="manual_amount" value="<?= $order['amount'] ?>" 
                                    class="w-full border p-2 rounded bg-white font-bold text-gray-800">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-red-400 uppercase mb-1">Méthode de Paiement</label>
                                <input type="text" name="manual_method" value="<?= htmlspecialchars((string)($order['payment_method'] ?? '')) ?>" 
                                    list="payment_methods"
                                    class="w-full border p-2 rounded bg-white">
                                <datalist id="payment_methods">
                                    <option value="WhatsApp">
                                    <option value="Om">
                                    <option value="Mtn">
                                    <option value="Cash">
                                    <option value="Virement">
                                </datalist>
                            </div>
                            <button type="submit" name="update_financials" class="w-full bg-red-600 text-white font-bold py-2 rounded shadow hover:bg-red-700 transition">
                                Force Update (Admin)
                            </button>
                            <p class="text-[10px] text-gray-500 italic">Attention : Utilisez cette fonction pour corriger des erreurs ou enregistrer des paiements partiels/spéciaux.</p>
                        </form>
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

                    <!-- CV Delivery Section (NEW) -->
                    <div class="bg-indigo-50 border border-indigo-200 shadow rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4 border-b border-indigo-200 pb-2 text-indigo-900">🚀 Livraison CV</h2>
                        
                        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cv_uploaded'): ?>
                            <div class="bg-green-100 text-green-700 p-2 rounded text-xs mb-4">CV final ajouté avec succès !</div>
                        <?php endif; ?>
                        
                        <?php if($uploadError): ?>
                            <div class="bg-red-100 text-red-700 p-2 rounded text-xs mb-4"><?= $uploadError ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-indigo-800 uppercase mb-1">Téléverser le CV Final</label>
                                <input type="file" name="completed_cv" required accept=".pdf,.doc,.docx" class="w-full text-sm">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded shadow hover:bg-indigo-700 transition">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Enregistrer le fichier
                            </button>
                        </form>

                        <?php if (!empty($order['completed_cv_path'])): 
                            $fullLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $order['completed_cv_path'];
                            
                            // Format Phone
                            $phone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                            // Default to Cameroon if simplified (starts with 6)
                            if(strlen($phone) == 9 && substr($phone, 0, 1) == '6') $phone = '237' . $phone;
                            
                            $msgName = explode(' ', trim($order['name']))[0]; // First Name
                            $waMessage = "Bonjour $msgName ! 👋\n\nVotre CV est prêt ! 📄✨\n\nVous pouvez le télécharger directement ici :\n👉 $fullLink\n\nMerci de votre confiance !\nL'équipe HireMe";
                            $waLink = "https://wa.me/$phone?text=" . urlencode($waMessage);
                        ?>
                            <div class="mt-6 pt-4 border-t border-indigo-200">
                                <div class="mb-3 text-center">
                                    <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-1 rounded">✅ Fichier prêt</span>
                                    <br>
                                    <a href="../<?= $order['completed_cv_path'] ?>" target="_blank" class="text-xs text-indigo-600 underline mt-1 inline-block">Voir le fichier</a>
                                </div>
                                <a href="<?= $waLink ?>" target="_blank" class="block w-full text-center bg-[#25D366] text-white font-bold py-3 rounded-full shadow hover:bg-[#20b858] transition transform hover:scale-105">
                                    <svg class="w-5 h-5 inline-block mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-6.912-11.975 1.701-26.241 15.688-26.241 8.528 0 15.467 6.938 15.467 15.466 0 8.527-6.939 15.466-15.468 15.466-4.088 0-7.947-1.585-10.957-4.469l-6.417 1.687zm9.648-9.982c-2.617-1.125-2.731-1.378-2.67-1.637.07-.3.738-1.527 1.258-1.848.514-.32.61-.267 1.053-.08.437.186 2.76.997 2.898 1.155.138.16.29.69.043 1.082-.246.392-.511.666-.889.85-.377.183-1.691.564-4.821-2.029-3.13-2.592-3.832-3.805-4.04-4.22-.208-.415-.06-.599.123-.781.183-.183.398-.291.603-.43.204-.138.384-.33.585-.544.199-.214.288-.358.418-.616.128-.258.07-.638-.179-1.23-.243-.591-1.31-3.155-1.742-3.486-.432-.33-.614-.234-.848-.052-.234.181-.782.529-1.157.94-.374.412-1.428 1.638-1.428 4.226 0 2.589 1.93 5.378 2.163 5.7.234.321 3.535 5.869 8.785 7.973 5.251 2.106 5.251 1.404 6.182 1.346.931-.059 3.016-1.23 3.447-2.433.432-1.203.432-2.228.307-2.433-.124-.204-.455-.325-.939-.567z"/></svg>
                                    Envoyer sur WhatsApp
                                </a>
                                <p class="text-[10px] text-gray-400 text-center mt-2">Cliquez pour ouvrir WhatsApp avec le message pré-rempli.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- CV Info -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg p-6 h-full">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Données du Formulaire CV</h2>
                        <?php if ($cvDetails):
                            $personal = json_decode($cvDetails['personal_info'] ?? '[]', true) ?: [];
                            $edu = json_decode($cvDetails['education'] ?? '[]', true) ?: [];
                            $exp = json_decode($cvDetails['experience'] ?? '[]', true) ?: [];
                            $skills = json_decode($cvDetails['skills'] ?? '[]', true) ?: [];
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
                                    <?= htmlspecialchars(is_string($exp) ? $exp : json_encode($exp, JSON_UNESCAPED_UNICODE)) ?></div>
                            </div>

                            <div class="mb-6">
                                <h3 class="font-bold text-gray-700 bg-gray-50 p-2 rounded mb-2">Formation & Diplômes</h3>
                                <div
                                    class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap bg-white border p-4 rounded">
                                    <?= htmlspecialchars(is_string($edu) ? $edu : json_encode($edu, JSON_UNESCAPED_UNICODE)) ?></div>
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