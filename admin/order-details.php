<?php
session_start();
    ini_set('display_errors', 0);
    error_reporting(E_ALL); ini_set('log_errors', 1);
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/db.php';
require_once __DIR__ . '/../includes/order-notifications.php';

function slugifyName($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

// Auto-migration: Ensure completed_cv_path exists to prevent SQL errors
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM orders LIKE 'completed_cv_path'");
    if (!$checkCol->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN completed_cv_path VARCHAR(255) DEFAULT NULL AFTER payment_proof");
    }
    
    // Auto-migrate draft columns
    $checkDraft = $pdo->query("SHOW COLUMNS FROM orders LIKE 'draft_cv_path'");
    if (!$checkDraft->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN draft_cv_path VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE orders ADD COLUMN draft_balance_due INT DEFAULT 0");
    }
    $checkCleanDraft = $pdo->query("SHOW COLUMNS FROM orders LIKE 'clean_draft_cv_path'");
    if (!$checkCleanDraft->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN clean_draft_cv_path VARCHAR(255) DEFAULT NULL");
    }

    // Auto-migrate job_title and skills columns
    $checkJobTitle = $pdo->query("SHOW COLUMNS FROM orders LIKE 'job_title'");
    if (!$checkJobTitle->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN job_title VARCHAR(255) DEFAULT NULL");
    }
    $checkSkills = $pdo->query("SHOW COLUMNS FROM orders LIKE 'skills'");
    if (!$checkSkills->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN skills TEXT DEFAULT NULL");
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
    if (!in_array($newStatus, ['pending', 'pending_draft', 'verified', 'failed'], true)) {
        die('Statut invalide.');
    }
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
                
                // 3b. Unlock sponsorship credits now that payment is verified
                $pdo->prepare("UPDATE sponsorship_pool SET credits_remaining = credits_total WHERE order_id = ? AND credits_remaining = 0 AND credits_total > 0 AND NOT EXISTS (SELECT 1 FROM sponsorship_requests r WHERE r.allocated_pool_id = sponsorship_pool.id)")->execute([$id]);

                // 4. Sync Prospects (NEW)
                // If this user was in the prospects list, mark them as converted immediately
                require_once 'includes/sync_helper.php';
                syncProspectsWithOrders($pdo);

            } elseif ($oldStatus === 'verified' && $newStatus !== 'verified') {
                // Changing from Verified to Unverified (Remove Revenue)
                $stmtDel = $pdo->prepare("DELETE FROM revenue WHERE order_id = ?");
                $stmtDel->execute([$id]);
                // Lock unused sponsorship credits again
                $pdo->prepare("UPDATE sponsorship_pool SET credits_remaining = 0 WHERE order_id = ? AND NOT EXISTS (SELECT 1 FROM sponsorship_requests r WHERE r.allocated_pool_id = sponsorship_pool.id)")->execute([$id]);
            }

            if ($newStatus === 'verified') {
                queue_order_event($pdo, 'cv', $order, 'approved');
            }
            $pdo->commit();
            send_order_notifications($pdo);
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

// Gemini API key is managed server-side via environment variables.
// The application does not persist secrets in project files.

// Compile client info for prompt
$clientSummary = "";
if ($cvDetails) {
    $personal = json_decode($cvDetails['personal_info'] ?? '[]', true) ?: [];
    $edu = json_decode($cvDetails['education'] ?? '[]', true) ?: '';
    $exp = json_decode($cvDetails['experience'] ?? '[]', true) ?: '';
    $skills = json_decode($cvDetails['skills'] ?? '[]', true) ?: [];
    
    $clientSummary .= "Nom : " . ($personal['name'] ?? $order['name']) . "\n";
    $clientSummary .= "WhatsApp : " . ($personal['whatsapp'] ?? $order['whatsapp']) . "\n";
    $clientSummary .= "Ville/Pays : " . ($personal['location'] ?? $order['country']) . "\n";
    $clientSummary .= "\nFORMATION :\n" . (is_string($edu) ? $edu : json_encode($edu, JSON_UNESCAPED_UNICODE)) . "\n";
    $clientSummary .= "\nEXPÉRIENCE PRO :\n" . (is_string($exp) ? $exp : json_encode($exp, JSON_UNESCAPED_UNICODE)) . "\n";
    $clientSummary .= "\nCOMPÉTENCES :\n" . ($skills['skills'] ?? '') . "\n";
    $clientSummary .= "LANGUES : " . ($skills['languages'] ?? '') . "\n";
} else {
    $clientSummary .= "Nom : " . $order['name'] . "\n";
    $clientSummary .= "WhatsApp : " . $order['whatsapp'] . "\n";
    $clientSummary .= "Pays : " . $order['country'] . "\n";
}
$clientSummary = trim($clientSummary);

// Handle SMS Sending
$smsNotificationMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_custom_sms'])) {
    $smsRecipient = $_POST['sms_recipient'] ?? '';
    $smsMsg = $_POST['sms_message'] ?? '';
    
    if (!empty($smsRecipient) && !empty($smsMsg)) {
        require_once 'config/sms_helper.php';
        $res = SMSHelper::sendSMS($smsRecipient, $smsMsg);
        if ($res['success']) {
            $smsNotificationMessage = '<div class="bg-green-100 border border-green-200 text-green-700 p-3 rounded-lg mb-4 text-xs font-bold">✓ SMS préparé. Approuvez son envoi dans le tableau de bord SMS.</div>';
        } else {
            $smsNotificationMessage = '<div class="bg-red-100 border border-red-200 text-red-700 p-3 rounded-lg mb-4 text-xs font-bold">✗ Échec de l\'envoi du SMS : ' . htmlspecialchars($res['error']) . '</div>';
        }
    }
}

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
        if (in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'])) {
            $uploadDir = '../uploads/completed_cvs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = 'CV_FINAL_' . ($order['order_ref'] ?? $id) . '_' . time() . '.' . $ext; // Unique hash
            $destPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // Save relative path to DB
                $dbPath = 'uploads/completed_cvs/' . $newFilename; 
                try {
                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        $stmtUpd = $pdo->prepare("UPDATE orders SET completed_cv_path = ?, clean_draft_cv_path = ? WHERE id = ?");
                        $stmtUpd->execute([$dbPath, $dbPath, $id]);
                    } else {
                        $stmtUpd = $pdo->prepare("UPDATE orders SET completed_cv_path = ? WHERE id = ?");
                        $stmtUpd->execute([$dbPath, $id]);
                    }
                    
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
            $uploadError = "Format invalide (PDF, DOC, DOCX, JPG, JPEG, PNG uniquement).";
        }
    }
}

// Handle Draft CV Upload (Pay After Delivery)
$draftUploadError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['draft_cv'])) {
    $file = $_FILES['draft_cv'];
    $balanceDue = intval($_POST['draft_balance_due'] ?? 0);
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $uploadDir = '../uploads/drafts/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $newFilename = 'DRAFT_' . ($order['order_ref'] ?? $id) . '_' . time() . '.jpg'; 
            $destPath = $uploadDir . $newFilename;
            
            // Add Watermark using PHP GD
            $sourceImage = @imagecreatefromstring(file_get_contents($file['tmp_name']));
            
            if ($sourceImage !== false) {
                // Save clean original draft version to completed_cv_path automatically (for easy direct display once paid)
                $cleanUploadDir = '../uploads/completed_cvs/';
                if (!is_dir($cleanUploadDir)) mkdir($cleanUploadDir, 0777, true);
                $cleanFilename = 'CLEAN_DRAFT_' . ($order['order_ref'] ?? $id) . '_' . time() . '.' . $ext;
                $cleanDestPath = $cleanUploadDir . $cleanFilename;
                copy($file['tmp_name'], $cleanDestPath);
                $cleanDbPath = 'uploads/completed_cvs/' . $cleanFilename;

                $width = imagesx($sourceImage);
                $height = imagesy($sourceImage);
                
                $watermarkText = "CV NON PAYE - A LIRE SEULEMENT";
                $fontSize = 5;
                
                $textColor = imagecolorallocatealpha($sourceImage, 255, 0, 0, 70); 
                
                for ($y = 50; $y < $height; $y += 150) {
                    for ($x = -100; $x < $width; $x += 250) {
                        imagestringup($sourceImage, $fontSize, $x + ($y % 200), $y, $watermarkText, $textColor);
                    }
                }
                
                $bigColor = imagecolorallocatealpha($sourceImage, 255, 0, 0, 50);
                imagestring($sourceImage, 5, (int)(($width / 2) - 100), (int)($height / 2), "CV NON PAYE", $bigColor);
                imagestring($sourceImage, 5, (int)(($width / 2) - 100), (int)(($height / 2) + 20), "Veuillez valider votre paiement", $bigColor);
                
                imagejpeg($sourceImage, $destPath, 85);
                imagedestroy($sourceImage);
                
                $dbPath = 'uploads/drafts/' . $newFilename; 
                try {
                    $stmtUpd = $pdo->prepare("UPDATE orders SET draft_cv_path = ?, draft_balance_due = ?, clean_draft_cv_path = ?, completed_cv_path = ? WHERE id = ?");
                    $stmtUpd->execute([$dbPath, $balanceDue, $cleanDbPath, $cleanDbPath, $id]);
                    
                    echo "<script>window.location.href='order-details.php?id=$id&msg=draft_uploaded';</script>";
                    exit;
                } catch (PDOException $e) {
                     $draftUploadError = "Erreur Base de données: " . $e->getMessage();
                }
            } else {
                $draftUploadError = "Image invalide ou corrompue. GD n'a pas pu la lire.";
            }
        } else {
            $draftUploadError = "Veuillez uploader une IMAGE (JPG ou PNG).";
        }
    } else {
        $draftUploadError = "Erreur d'upload: " . $file['error'];
    }
}

// Handle Job Title and Skills update
$metaError = null;
$metaSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_professional_meta'])) {
    $jobTitle = trim($_POST['job_title'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    
    try {
        $stmtMeta = $pdo->prepare("UPDATE orders SET job_title = ?, skills = ? WHERE id = ?");
        $stmtMeta->execute([$jobTitle, $skills, $id]);
        $metaSuccess = true;
        // Refresh to reload state
        echo "<script>window.location.href='order-details.php?id=$id&msg=meta_updated';</script>";
        exit;
    } catch (PDOException $e) {
        $metaError = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
    <title>Détails Commande #<?= $order['order_ref'] ?? $id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Shared HireMe favicon -->
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48.png?v=20261011">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32.png?v=20261011">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16.png?v=20261011">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon-180.png?v=20261011">
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
                        <option value="pending_draft" <?= $order['payment_status'] == 'pending_draft' ? 'selected' : '' ?>>🟡 En attente (Brouillon)
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

            <?php if (isset($smsNotificationMessage) && $smsNotificationMessage !== null) echo $smsNotificationMessage; ?>

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
                                <div class="font-bold flex items-center justify-between">
                                    <span><?= htmlspecialchars($order['whatsapp'] ?? '') ?></span>
                                    <?php 
                                        $waPhone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                                        if (strlen($waPhone) == 9 && substr($waPhone, 0, 1) == '6') {
                                            $waPhone = '237' . $waPhone;
                                        }
                                        $orderRef = htmlspecialchars($order['order_ref'] ?? $order['id']);
                                        $clientFirstName = explode(' ', trim($order['name']))[0];
                                        
                                        $isReadyAndPaid = (!empty($order['completed_cv_path']) && $order['payment_status'] === 'verified');
                                        
                                        if ($isReadyAndPaid) {
                                            $waMsg = "Bonjour $clientFirstName ! 👋\n\nExcellente nouvelle, votre CV final premium rédigé par nos experts est prêt et disponible ! 🚀\n\nVous pouvez le télécharger instantanément sans mot de passe en cliquant sur votre lien magique personnalisé sécurisé ici :\n👉 https://moncvpro.hiremeguide.com/retrieve-cv.php?ref=" . urlencode($orderRef);
                                            $btnLabel = "👑 ENVOYER LA LIVRAISON";
                                            $btnClass = "bg-blue-100 hover:bg-blue-200 text-blue-800";
                                        } else {
                                            $waMsg = "Bonjour $clientFirstName ! 👋\n\nVotre commande de CV professionnel est en cours de traitement. 🛠️\n\nVous pouvez suivre son avancement en temps réel, relire le brouillon et valider les étapes sans mot de passe via votre lien personnalisé ici :\n👉 https://moncvpro.hiremeguide.com/track-cv.php?ref=" . urlencode($orderRef);
                                            $btnLabel = "💬 ENVOYER LE SUIVI";
                                            $btnClass = "bg-green-100 hover:bg-green-200 text-green-800";
                                        }
                                        
                                        $waLink = "https://wa.me/$waPhone?text=" . urlencode($waMsg);
                                    ?>
                                    <a href="<?= $waLink ?>" target="_blank" class="<?= $btnClass ?> text-[10px] font-black px-2 py-1 rounded transition flex items-center gap-1 shadow-sm">
                                        <?= $btnLabel ?>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Alerte SMS Action Trigger [NEW] -->
                            <div>
                                <span class="text-gray-500 text-sm">Alerte SMS (TextBee)</span>
                                <div class="flex items-center justify-between mt-1">
                                    <form method="POST" class="w-full flex items-center gap-2">
                                        <input type="hidden" name="sms_recipient" value="<?= htmlspecialchars($order['whatsapp'] ?? '') ?>">
                                        <?php
                                            if ($isReadyAndPaid) {
                                                $smsMsg = "Bonjour $clientFirstName! Votre CV final premium est pret! Telechargez-le ici: https://moncvpro.hiremeguide.com/retrieve-cv.php?ref=" . urlencode($orderRef) . "&src=sms";
                                                $smsBtnLabel = "📲 SMS Livraison";
                                                $smsBtnClass = "bg-sky-600 hover:bg-sky-700 text-white";
                                            } else {
                                                $smsMsg = "Bonjour $clientFirstName! Suivez l'avancement de votre CV, relisez le brouillon et validez les etapes ici: https://moncvpro.hiremeguide.com/track-cv.php?ref=" . urlencode($orderRef) . "&src=sms";
                                                $smsBtnLabel = "💬 SMS Suivi / Brouillon";
                                                $smsBtnClass = "bg-emerald-600 hover:bg-emerald-700 text-white";
                                            }
                                        ?>
                                        <input type="hidden" name="sms_message" value="<?= htmlspecialchars($smsMsg) ?>">
                                        <button type="submit" name="trigger_custom_sms" class="w-full <?= $smsBtnClass ?> text-[10px] font-extrabold py-1 px-3 rounded transition shadow-sm text-center uppercase tracking-wider">
                                            <?= $smsBtnLabel ?>
                                        </button>
                                    </form>
                                </div>
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
                        
                        <?php if($draftUploadError): ?>
                            <div class="bg-red-100 text-red-700 p-2 rounded text-xs mb-4"><?= $draftUploadError ?></div>
                        <?php endif; ?>

                        <!-- DRAFT UPLOAD (Pay After Delivery) -->
                        <div class="mb-6 p-4 border-2 border-dashed border-orange-300 bg-orange-50 rounded-lg">
                            <h3 class="text-xs font-black text-orange-800 uppercase mb-3"><span class="text-lg">👁️</span> Uploader Brouillon (Pay After Delivery)</h3>
                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Image du CV (JPG ou PNG) - Le filigrane sera ajouté auto</label>
                                    <input type="file" name="draft_cv" required accept=".jpg,.jpeg,.png" class="w-full text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Montant restant à payer (FCFA)</label>
                                    <input type="number" name="draft_balance_due" required min="0" value="<?= ($order['order_type'] ?? '') === 'advance' ? ($order['amount'] / 2) : $order['amount'] ?>" class="w-full text-sm border-gray-300 rounded">
                                </div>
                                <button type="submit" class="w-full bg-orange-600 text-white font-bold py-2 rounded shadow hover:bg-orange-700 transition">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Générer & Sauvegarder
                                </button>
                            </form>

                            <?php if (!empty($order['draft_cv_path'])): 
                                $draftLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/view-draft.php?id=" . $id . "&ref=" . urlencode($order['order_ref']);
                                
                                $phone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                                if(strlen($phone) == 9 && substr($phone, 0, 1) == '6') $phone = '237' . $phone;
                                $msgName = explode(' ', trim($order['name']))[0];
                                $packName = trim($order['package_name'] ?? 'CV');
                                
                                $draftMsg = "Bonjour $msgName !\n\n";
                                $draftMsg .= "Excellente nouvelle : votre $packName est prêt ! 🥳\n\n";
                                $draftMsg .= "Veuillez cliquer sur ce lien sécurisé pour jeter un coup d'œil à votre Brouillon :\n-> $draftLink\n\n";
                                $draftMsg .= "Si tout est correct, vous pourrez effectuer le paiement restant pour entrer en possession de la version finale (PDF Haute Qualité) et de tous les bonus.\n\n";
                                $draftMsg .= "L'équipe HireMe reste à votre disposition.";
                                
                                $waDraftLink = "https://wa.me/$phone?text=" . urlencode($draftMsg);
                            ?>
                                <div class="mt-4 pt-3 border-t border-orange-200 text-center">
                                    <a href="../<?= $order['draft_cv_path'] ?>" target="_blank" class="text-xs text-orange-800 underline mb-3 inline-block">Voir l'image générée</a>
                                    <a href="<?= $waDraftLink ?>" target="_blank" class="block w-full text-center bg-[#25D366] text-white font-bold py-2 rounded shadow hover:bg-[#20b858] transition">
                                        📲 Envoyer le brouillon (WhatsApp)
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- FINAL CV UPLOAD -->
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-indigo-800 uppercase mb-1">Téléverser le CV Final</label>
                                <input type="file" name="completed_cv" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm">
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
                            $clientName = trim($order['name'] ?? 'Client');
                            $clientEmail = trim($order['email'] ?? 'votre adresse email');
                            $packName = trim($order['package_name'] ?? 'Pack CV');
                            
                            $hour = (int)date('H');
                            $greeting = ($hour >= 18 || $hour < 5) ? 'Bonsoir' : 'Bonjour';
                            
                            $waMessage = "$greeting $msgName !\n\n";
                            $waMessage .= "Excellente nouvelle : votre $packName est prêt !\n\n";
                            $waMessage .= "[ 1 ] TÉLÉCHARGEMENT DIRECT :\n";
                            $waMessage .= "Vous pouvez télécharger votre CV final (PDF) directement ici :\n-> $fullLink\n\n";
                            $waMessage .= "[ 2 ] VOTRE ESPACE DE RÉCUPÉRATION (Valable 1 An) :\n";
                            $waMessage .= "En cas de perte, votre dossier complet (incluant la photo professionnelle créée spécialement pour vous et tous vos autres fichiers) est sauvegardé sur notre plateforme.\n";
                            $waMessage .= "Accédez à votre dossier complet ici : https://moncvpro.hiremeguide.com/retrieve-cv\n";
                            $waMessage .= "Vos identifiants :\n";
                            $waMessage .= "- Nom : $clientName\n";
                            $waMessage .= "- Email : $clientEmail\n\n";
                            $waMessage .= "Si vous êtes satisfait de notre travail, n'hésitez pas à aider vos proches à booster leur carrière en leur recommandant Mon CV Pro !\n\n";
                            $waMessage .= "Merci de votre confiance et beaucoup de succès !\nL'équipe HireMe";
                            
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
                                <p class="text-[10px] text-gray-400 text-center mt-2 mb-4">Cliquez pour ouvrir WhatsApp avec le message pré-rempli.</p>

                                <?php
                                $feedbackMessage = "Bonjour $msgName,\n\n";
                                $feedbackMessage .= "J'espère que vous allez bien et que votre nouveau $packName vous donne entière satisfaction !\n\n";
                                $feedbackMessage .= "Votre avis compte énormément pour nous. Pourriez-vous prendre 1 minute pour partager votre expérience sur notre site ?\n";
                                $feedbackMessage .= "👉 Laissez votre avis ici : https://moncvpro.hiremeguide.com/index.html#laisser-un-avis\n\n";
                                $feedbackMessage .= "Merci d'avance pour votre soutien ! 🙏";
                                $waFeedbackLink = "https://wa.me/$phone?text=" . urlencode($feedbackMessage);
                                ?>
                                <a href="<?= $waFeedbackLink ?>" target="_blank" class="block w-full text-center bg-blue-600 text-white font-bold py-3 rounded-full shadow hover:bg-blue-700 transition transform hover:scale-105">
                                    ⭐ Demander un Avis (WhatsApp)
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- PROFESSIONAL METADATA & QR CODE PANEL (FEATURE 4) -->
                    <div class="bg-sky-50 border border-sky-200 shadow-sm rounded-xl p-6 mt-6">
                        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-sky-100">
                            <span class="text-xl">📱</span>
                            <h3 class="font-extrabold text-sky-900 text-xs uppercase tracking-wider">Métadonnées Portfolio & QR Code</h3>
                        </div>

                        <!-- Form to update metadata -->
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-sky-800 uppercase mb-1">Titre Professionnel</label>
                                <input type="text" name="job_title" value="<?= htmlspecialchars($order['job_title'] ?? '') ?>" placeholder="Ex: Chef de Projet BTP, Comptable Agréé" class="w-full text-xs border border-sky-200 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-200 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-sky-800 uppercase mb-1">Compétences (Séparées par des virgules)</label>
                                <textarea name="skills" rows="2" placeholder="Ex: PHP, BTP, Gestion d'équipe, AutoCAD" class="w-full text-xs border border-sky-200 p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-200 bg-white"><?= htmlspecialchars($order['skills'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" name="update_professional_meta" class="w-full bg-sky-600 text-white font-bold py-2.5 rounded-lg shadow hover:bg-sky-700 transition text-xs uppercase tracking-wider">
                                💾 Mettre à jour le Profil Pro
                            </button>
                        </form>

                        <!-- Live QR Code Display if CV is ready -->
                        <?php if (!empty($order['completed_cv_path']) && $order['payment_status'] === 'verified'): 
                            $cName = $order['name'] ?? '';
                            $cSlug = slugifyName($cName);
                            
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                            $host = $_SERVER['HTTP_HOST'];
                            $scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /MonCv/admin/order-details.php
                            $dir = str_replace('\\', '/', dirname(dirname($scriptName)));
                            if ($dir !== '/') {
                                $dir = rtrim($dir, '/');
                            } else {
                                $dir = '';
                            }
                            $baseUrl = $protocol . "://" . $host . $dir . "/";
                            
                            $pUrl = $baseUrl . "cv.php?name=" . $cSlug;
                            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($pUrl);
                        ?>
                            <div class="mt-6 pt-4 border-t border-sky-200 text-center space-y-3">
                                <span class="text-xs font-bold text-sky-700 bg-sky-100 px-2.5 py-1 rounded-full">QR Code Actif</span>
                                <div class="bg-white p-3 rounded-lg border border-sky-100 w-36 h-36 mx-auto flex items-center justify-center shadow-sm mt-2">
                                    <img src="<?= $qrUrl ?>" alt="QR Code" class="w-full h-full object-contain">
                                </div>
                                <a href="<?= $pUrl ?>" target="_blank" class="text-xs text-sky-700 underline font-bold hover:text-sky-800 transition block mt-1">
                                    🔗 Visiter le Portfolio dynamique
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Litige & Impayés Card -->
                    <div class="bg-red-50 border border-red-200 shadow-sm rounded-xl p-6 mt-6">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-xl">⚠️</span>
                            <h2 class="text-xl font-black text-red-900 uppercase tracking-tight">Litige & Impayés</h2>
                        </div>
                        
                        <div id="litige-info" class="space-y-4">
                            <?php 
                                $isNonPayer = !empty($order['is_non_payer']);
                                $deletionDate = $order['deletion_scheduled_at'];
                                $reminderCount = $order['reminder_count'] ?? 0;
                            ?>
                            
                            <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-red-100">
                                <span class="text-sm font-bold text-gray-700">Client Délinquant ?</span>
                                <button onclick="toggleNonPayer(<?= $isNonPayer ? 0 : 1 ?>)" 
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none <?= $isNonPayer ? 'bg-red-600' : 'bg-gray-200' ?>">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?= $isNonPayer ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                </button>
                            </div>

                            <?php if ($isNonPayer): ?>
                                <div class="bg-white p-3 rounded-lg border border-red-100 space-y-2">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500 uppercase font-black">Rappels envoyés</span>
                                        <span class="text-red-600 font-black" id="reminder-count"><?= $reminderCount ?></span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500 uppercase font-black">Suppression prévue</span>
                                        <span class="text-red-600 font-bold"><?= $deletionDate ? date('d/m/Y', strtotime($deletionDate)) : 'Non défini' ?></span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-[10px] font-black uppercase text-red-400 mb-1">
                                        <span>Niveau de Relance</span>
                                        <span id="reminder-level"><?= min(5, $reminderCount + 1) ?>/5</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-red-100 rounded-full overflow-hidden flex gap-0.5 p-0.5 mb-4">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <div class="flex-1 h-full rounded-full <?= ($i <= $reminderCount) ? 'bg-red-600' : (($i == $reminderCount + 1) ? 'bg-blue-500 animate-pulse' : 'bg-gray-200') ?>"></div>
                                        <?php endfor; ?>
                                    </div>

                                    <?php
                                        $firstName = explode(' ', trim($order['name']))[0];
                                        $fmtAmount = number_format($order['amount'], 0, ',', ' ');
                                        $delDateFmt = $deletionDate ? date('d/m/Y', strtotime($deletionDate)) : 'très bientôt';
                                        $waPhone = preg_replace('/[^0-9]/', '', $order['whatsapp']);
                                        if(strlen($waPhone) == 9 && substr($waPhone, 0, 1) == '6') $waPhone = '237' . $waPhone;
                                        
                                        $templates = [
                                            1 => [
                                                'label' => 'L1: Rappel Doux (Prêt)',
                                                'icon' => '👋',
                                                'msg' => "Bonjour $firstName ! Votre CV est finalisé et prêt à être livré. 📄 Il ne manque plus que le règlement de $fmtAmount FCFA pour que nous puissions vous l'envoyer directement. À très vite !"
                                            ],
                                            2 => [
                                                'label' => 'L2: Qualité & Carrière',
                                                'icon' => '🎨',
                                                'msg' => "Re-bonjour $firstName. Votre dossier est prêt ! ✨ Ne laissez pas votre carrière attendre pour seulement $fmtAmount FCFA. Un CV optimisé est votre meilleur investissement. Reprenons ici !"
                                            ],
                                            3 => [
                                                'label' => 'L3: Alerte Suppression',
                                                'icon' => '⚠️',
                                                'msg' => "Bonjour $firstName, Alerte Impayé. Votre CV sera SUPPRIMÉ le $delDateFmt si le paiement de $fmtAmount FCFA n'est pas reçu. C'est dommage de perdre ce travail déjà effectué !"
                                            ],
                                            4 => [
                                                'label' => 'L4: Restriction d\'accès',
                                                'icon' => '🚫',
                                                'msg' => "Attention $firstName. Sans règlement de $fmtAmount FCFA sous 24h, nous devrons restreindre votre accès futur à nos services et annuler votre dossier. Merci de régulariser maintenant."
                                            ],
                                            5 => [
                                                'label' => 'L5: Avis Final / Ban',
                                                'icon' => '🏴‍☠️',
                                                'msg' => "DERNIER RAPPEL $firstName : Nous archivons votre dossier aujourd'hui. Le montant de $fmtAmount FCFA reste dû. C'est votre dernière chance avant la suppression et le bannissement de nos listes."
                                            ]
                                        ];

                                        $nextLevel = min(5, $reminderCount + 1);
                                    ?>
                                    
                                    <div class="relative group/wa" id="wa-dropdown-container">
                                        <button class="w-full bg-white border-2 border-green-600 text-green-600 font-black py-3 rounded-xl hover:bg-green-50 transition flex items-center justify-center gap-2 shadow-sm">
                                            <span>WhatsApp Relance L<?= $nextLevel ?></span>
                                            <svg class="w-4 h-4 transition-transform group-hover/wa:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>
                                        
                                        <div class="absolute bottom-full left-0 right-0 mb-2 bg-white border border-gray-200 rounded-xl shadow-2xl opacity-0 invisible group-hover/wa:opacity-100 group-hover/wa:visible transition-all z-50 overflow-hidden">
                                            <?php foreach($templates as $lvl => $tpl): 
                                                $waUrl = "https://wa.me/$waPhone?text=" . urlencode($tpl['msg']);
                                                $isNext = ($lvl == $nextLevel);
                                            ?>
                                                <a href="<?= $waUrl ?>" target="_blank" onclick="incrementReminder()" 
                                                   class="flex items-center gap-3 px-4 py-3 text-xs border-b border-gray-50 last:border-0 hover:bg-green-50 transition-colors <?= $isNext ? 'bg-green-50/50' : 'opacity-70' ?>">
                                                    <span class="text-base"><?= $tpl['icon'] ?></span>
                                                    <div class="flex-1">
                                                        <div class="font-black <?= $isNext ? 'text-green-700' : 'text-gray-600' ?>"><?= $tpl['label'] ?></div>
                                                        <?php if($isNext): ?>
                                                            <div class="text-[8px] text-green-500 uppercase font-bold">Conseillé ✨</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <button onclick="banUser()" 
                                        class="block w-full text-center bg-red-900 text-white font-black py-2 rounded-xl hover:bg-black transition text-sm">
                                        ⛔ BANNIR DÉFINITIVEMENT
                                    </button>
                                </div>
                            <?php else: ?>
                                <p class="text-[10px] text-gray-500 italic">Activez le bouton ci-dessus pour planifier une suppression et préparer un message de litige.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <script>
                    async function toggleNonPayer(newStatus) {
                        try {
                            const formData = new FormData();
                            formData.append('action', 'set_non_payer');
                            formData.append('order_id', '<?= $id ?>');
                            formData.append('status', newStatus);

                            const response = await fetch('api/manage_litige.php', { method: 'POST', body: formData });
                            const data = await response.json();
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert("Erreur: " + data.message);
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    }

                    async function incrementReminder() {
                        try {
                            const formData = new FormData();
                            formData.append('action', 'increment_reminder');
                            formData.append('order_id', '<?= $id ?>');

                            const response = await fetch('api/manage_litige.php', { method: 'POST', body: formData });
                            const data = await response.json();
                            if (data.success) {
                                document.getElementById('reminder-count').innerText = data.new_count;
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    }

                    async function banUser() {
                        if (!confirm("Êtes-vous sûr de vouloir bannir ce client ? Il ne pourra plus jamais commander sur le site.")) return;
                        
                        try {
                            const formData = new FormData();
                            formData.append('action', 'ban_client');
                            formData.append('order_id', '<?= $id ?>');

                            const response = await fetch('api/manage_litige.php', { method: 'POST', body: formData });
                            const data = await response.json();
                            if (data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert("Erreur: " + data.message);
                            }
                        } catch (e) {
                            console.error(e);
                        }
                    }
                </script>

                <!-- CV Info -->
                <div class="lg:col-span-2">
                    
                    <!-- AI COVER LETTER GENERATOR -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <div class="flex items-center justify-between border-b pb-4 mb-4">
                            <h2 class="text-xl font-bold flex items-center gap-2 text-indigo-900">
                                <span>📝</span> Rédiger Lettre de Motivation (IA)
                            </h2>
                            <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">BETA</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Poste ciblé *</label>
                                <input type="text" id="ai_target_position" class="w-full border p-2.5 rounded bg-gray-50 focus:bg-white text-sm" placeholder="Ex: Comptable, Développeur PHP">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Entreprise ciblée (Optionnel)</label>
                                <input type="text" id="ai_target_company" class="w-full border p-2.5 rounded bg-gray-50 focus:bg-white text-sm" placeholder="Ex: Orange Cameroun, Afriland First Bank">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Localisation de l'entreprise (Optionnel)</label>
                                <input type="text" id="ai_target_location" class="w-full border p-2.5 rounded bg-gray-50 focus:bg-white text-sm" placeholder="Ex: Douala, Yaoundé, Paris">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ton de la lettre</label>
                                <select id="ai_tone" class="w-full border p-2.5 rounded bg-gray-50 focus:bg-white text-sm">
                                    <option value="Professionnel">Professionnel & Convaincant</option>
                                    <option value="Dynamique">Dynamique & Moderne</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Langue de rédaction</label>
                                <select id="ai_language" class="w-full border p-2.5 rounded bg-gray-50 focus:bg-white text-sm">
                                    <option value="Français">Français</option>
                                    <option value="Anglais">Anglais</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4 border border-gray-100 bg-gray-50/55 rounded-xl p-3 text-xs text-gray-600">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-bold uppercase tracking-wide">⚙️ CONFIGURATION API</span>
                                <span class="rounded-full bg-emerald-100 text-emerald-700 px-2 py-1 font-bold text-[10px]">Côté serveur</span>
                            </div>
                            <p class="mt-2">La clé API Gemini est gérée côté serveur via les variables d'environnement. Aucun secret n'est stocké dans le dépôt ni dans les fichiers du projet.</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 mb-6">
                            <button onclick="generateDirectAI()" id="btn_generate_direct" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span>✨</span> Générer Directement par l'IA
                            </button>
                            <button onclick="copyPromptToClipboard()" class="flex-1 bg-white border-2 border-slate-200 hover:bg-slate-50 text-slate-700 font-bold py-3 px-4 rounded-xl shadow-sm transition flex items-center justify-center gap-2">
                                <span>📋</span> Copier le Prompt (ChatGPT gratuit)
                            </button>
                        </div>

                        <!-- Results -->
                        <div id="ai_result_box" class="hidden border border-indigo-100 rounded-xl p-4 bg-indigo-50/30">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-xs font-bold text-indigo-800">LETTRE DE MOTIVATION PRO GÉNÉRÉE :</span>
                                <button onclick="copyLetterResult()" id="btn_copy_letter" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-800 text-xs font-black py-1.5 px-3 rounded-lg transition">
                                    📋 Copier la lettre
                                </button>
                            </div>
                            <textarea id="ai_letter_result" rows="12" class="w-full p-4 rounded-xl border border-indigo-200 bg-white font-serif text-sm leading-relaxed text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>

                    <script>
                        const clientSummaryText = <?= json_encode($clientSummary) ?>;
                        
                        function copyPromptToClipboard() {
                            const targetPos = document.getElementById('ai_target_position').value.trim();
                            const targetComp = document.getElementById('ai_target_company').value.trim();
                            const targetLoc = document.getElementById('ai_target_location').value.trim();
                            const tone = document.getElementById('ai_tone').value;
                            const lang = document.getElementById('ai_language').value;

                            if (!targetPos) {
                                alert("Veuillez saisir au moins le poste ciblé.");
                                return;
                            }

                            let prompt = `Rédige une lettre de motivation professionnelle de haut niveau.\n`;
                            prompt += `Langue de rédaction : ${lang === 'Anglais' ? 'English' : 'Français'}\n`;
                            prompt += `Ton de la lettre : ${tone === 'Dynamique' ? 'Dynamique et Moderne' : 'Professionnel et Convaincant'}\n`;
                            prompt += `Poste ciblé : ${targetPos}\n`;
                            if (targetComp) {
                                prompt += `Entreprise ciblée : ${targetComp}\n`;
                            }
                            if (targetLoc) {
                                prompt += `Localisation de l'entreprise : ${targetLoc}\n`;
                            }
                            prompt += `\nDonnées du candidat :\n${clientSummaryText}\n\n`;
                            prompt += `Règles :\n`;
                            prompt += `1. Format standard et élégant.\n`;
                            prompt += `2. Salutation formelle adaptée.\n`;
                            prompt += `3. Pas de placeholders génériques comme '[Votre Nom]', laisse des espaces vides ou utilise les données du candidat.\n`;
                            prompt += `4. Si l'entreprise est mentionnée, explique pourquoi le candidat souhaite la rejoindre spécifiquement.\n`;
                            prompt += `5. Termine par une formule de politesse chaleureuse et professionnelle.\n`;

                            navigator.clipboard.writeText(prompt);
                            alert("📋 Prompt copié avec succès ! Collez-le maintenant dans ChatGPT ou Gemini gratuit.");
                        }

                        async function generateDirectAI() {
                            const targetPos = document.getElementById('ai_target_position').value.trim();
                            const targetComp = document.getElementById('ai_target_company').value.trim();
                            const targetLoc = document.getElementById('ai_target_location').value.trim();
                            const tone = document.getElementById('ai_tone').value;
                            const lang = document.getElementById('ai_language').value;
                            const btn = document.getElementById('btn_generate_direct');
                            const resultBox = document.getElementById('ai_result_box');
                            const textResult = document.getElementById('ai_letter_result');

                            if (!targetPos) {
                                alert("Veuillez saisir le poste ciblé.");
                                return;
                            }

                            const originalHtml = btn.innerHTML;
                            btn.disabled = true;
                            btn.innerHTML = `<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Rédaction en cours...`;

                            try {
                                const response = await fetch('api/generate-letter.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        target_position: targetPos,
                                        target_company: targetComp,
                                        target_location: targetLoc,
                                        tone: tone,
                                        language: lang,
                                        client_data: clientSummaryText
                                    })
                                });

                                const data = await response.json();
                                if (data.success) {
                                    resultBox.classList.remove('hidden');
                                    textResult.value = data.letter;
                                    textResult.scrollIntoView({ behavior: 'smooth' });
                                } else {
                                    alert("Erreur de génération : " + data.message);
                                }
                            } catch (e) {
                                console.error(e);
                                alert("Impossible de contacter le serveur d'IA.");
                            } finally {
                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            }
                        }

                        function copyLetterResult() {
                            const letterText = document.getElementById('ai_letter_result').value;
                            navigator.clipboard.writeText(letterText);
                            const btn = document.getElementById('btn_copy_letter');
                            const original = btn.innerText;
                            btn.innerText = "✅ Copiée !";
                            setTimeout(() => btn.innerText = original, 2000);
                        }
                    </script>

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