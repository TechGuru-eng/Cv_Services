<?php
// api/linkedin/submit_order.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../admin/config/db.php';
require_once __DIR__ . '/../../includes/upload_helper.php';
require_once __DIR__ . '/../../includes/order-notifications.php';

function generateUuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateOrderReference($pdo) {
    do {
        $ref = 'HM-LI-' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM linkedin_orders WHERE reference = ?");
        $stmt->execute([$ref]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $ref;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    // 1. Gather & Sanitize Inputs
    $name = trim($_POST['name'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? 'Cameroun');
    $positionStatus = trim($_POST['position_status'] ?? '');
    $targetProfileType = trim($_POST['target_profile_type'] ?? '');
    $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
    $mainObjective = trim($_POST['main_objective'] ?? '');
    $targetJob = trim($_POST['target_job'] ?? '');
    $targetSector = trim($_POST['target_sector'] ?? '');
    $targetCompanies = trim($_POST['target_companies'] ?? '');
    $targetLocations = trim($_POST['target_locations'] ?? '');
    $experienceLevel = trim($_POST['experience_level'] ?? '');
    $careerGoals = trim($_POST['career_goals'] ?? '');

    $packageId = (int)($_POST['package_id'] ?? 0);
    $bundleId = (int)($_POST['bundle_id'] ?? 0);
    $selectedAddonIds = isset($_POST['addon_ids']) ? (array)$_POST['addon_ids'] : [];
    $selectedProductIds = isset($_POST['product_ids']) ? (array)$_POST['product_ids'] : [];

    $paymentMethod = trim($_POST['payment_method'] ?? 'MTN');
    $paymentPhone = trim($_POST['payment_phone'] ?? '');
    $transactionRef = trim($_POST['transaction_ref'] ?? '');

    $international = $paymentMethod === 'CHARIOW';
    $checkoutUrl = null;
    if ($international) {
        $stmtLink = $pdo->prepare('SELECT setting_value FROM linkedin_settings WHERE setting_key=?');
        $stmtLink->execute(['chariow_url_' . $packageId]);
        $checkoutUrl = $stmtLink->fetchColumn();
        if (!$checkoutUrl || !filter_var($checkoutUrl, FILTER_VALIDATE_URL) || parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https') {
            echo json_encode(['success'=>false,'message'=>'Le lien Chariow de ce forfait sera disponible prochainement. Contactez-nous sur WhatsApp.']); exit;
        }
        if ($bundleId || $selectedAddonIds || $selectedProductIds || !empty($_POST['promo_code'])) {
            echo json_encode(['success'=>false,'message'=>'Les options et codes promo du site sont disponibles uniquement avec Mobile Money.']); exit;
        }
    }
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Le nom est obligatoire.';
    if (empty($firstName)) $errors[] = 'Le prénom est obligatoire.';
    if (empty($phone)) $errors[] = 'Le numéro de téléphone / WhatsApp est obligatoire.';
    if (!$email) $errors[] = 'L\'adresse email est invalide.';
    if (empty($linkedinUrl)) {
        $errors[] = 'L\'URL du profil LinkedIn est obligatoire.';
    } elseif (!preg_match('~^https?://(?:www\.)?linkedin\.com/in/[a-zA-Z0-9_%\-]+/?(?:\?[^\s]*)?$~i', $linkedinUrl)) {
        $errors[] = 'Le lien doit être un profil LinkedIn valide.';
    }
    if (!in_array($paymentMethod, ['MTN', 'ORANGE', 'CHARIOW'], true)) $errors[] = 'Mode de paiement invalide.';
    if ($packageId <= 0 && $bundleId <= 0) $errors[] = 'Veuillez sélectionner une formule d\'optimisation ou un pack visibilité.';
    if (!$international && empty($paymentPhone)) $errors[] = 'Le numéro de téléphone utilisé pour le paiement est requis.';
    if (!$international && empty($transactionRef)) $errors[] = 'La référence / ID de la transaction est requise.';

    if (empty($positionStatus) || empty($mainObjective) || empty($targetJob) || empty($targetSector)) $errors[] = 'Veuillez remplir tous les champs obligatoires avant de continuer.';
    foreach (['cv_file' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'payment_proof' => ['pdf', 'jpg', 'jpeg', 'png']] as $field => $extensions) {
        $file = $_FILES[$field] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            if ($field === 'cv_file') $errors[] = 'Veuillez joindre votre CV.';
            continue;
        }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > 10 * 1024 * 1024 || !in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $extensions, true)) $errors[] = 'Fichier invalide ou supérieur à 10 Mo.';
        elseif (!in_array((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), UPLOAD_MIME_MAP[strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))] ?? [], true)) $errors[] = 'Fichier invalide ou supérieur à 10 Mo.';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }

    // 2. SERVER-SIDE AUTHORITATIVE PRICE CALCULATION
    $lineItems = []; // Array of line items to store in linkedin_order_items
    $totalAmount = 0.00;

    // Check if Bundle is selected (e.g., PACK VISIBILITÉ PRO)
    if ($bundleId > 0) {
        $stmtB = $pdo->prepare("SELECT id, name, bundle_price FROM linkedin_bundles WHERE id = ? AND is_active = 1");
        $stmtB->execute([$bundleId]);
        $bundle = $stmtB->fetch();

        if (!$bundle) {
            echo json_encode(['success' => false, 'message' => 'Le pack sélectionné est invalide.']);
            exit;
        }
        if ($bundle) {
            // Find corresponding LinkedIn package for reference (e.g., Pack Pro)
            $pkgPro = $pdo->query("SELECT id FROM linkedin_packages WHERE slug = 'pro' AND is_active = 1")->fetch();
            if (!$pkgPro) {
                echo json_encode(['success' => false, 'message' => 'Le pack sélectionné est indisponible.']);
                exit;
            }
            $packageId = (int)$pkgPro['id'];

            $bPrice = (float)$bundle['bundle_price'];
            $totalAmount += $bPrice;

            $lineItems[] = [
                'type' => 'bundle',
                'item_id' => $bundle['id'],
                'name' => $bundle['name'],
                'unit_price' => $bPrice,
                'discount' => 0.00,
                'final_price' => $bPrice
            ];
        }
    }

    // Standard Package calculation if no bundle or bundle item breakdown
    if (empty($lineItems)) {
        $stmtPkg = $pdo->prepare("SELECT id, name, price FROM linkedin_packages WHERE id = ? AND is_active = 1");
        $stmtPkg->execute([$packageId]);
        $package = $stmtPkg->fetch();
        if (!$package) {
            echo json_encode(['success' => false, 'message' => 'Le forfait sélectionné est invalide.']);
            exit;
        }

        $pkgPrice = (float)$package['price'];
        $totalAmount += $pkgPrice;

        $lineItems[] = [
            'type' => 'package',
            'item_id' => $package['id'],
            'name' => $package['name'],
            'unit_price' => $pkgPrice,
            'discount' => 0.00,
            'final_price' => $pkgPrice
        ];
    }

    // Calculate selected Add-ons
    if (!empty($selectedAddonIds)) {
        $inClause = implode(',', array_fill(0, count($selectedAddonIds), '?'));
        $stmtAddons = $pdo->prepare("SELECT id, name, price FROM linkedin_addons WHERE id IN ($inClause) AND is_active = 1");
        $stmtAddons->execute(array_map('intval', $selectedAddonIds));
        $addons = $stmtAddons->fetchAll();

        foreach ($addons as $ad) {
            $adPrice = (float)$ad['price'];
            $totalAmount += $adPrice;

            $lineItems[] = [
                'type' => 'addon',
                'item_id' => $ad['id'],
                'name' => $ad['name'],
                'unit_price' => $adPrice,
                'discount' => 0.00,
                'final_price' => $adPrice
            ];
        }
    }

    // Calculate selected Upsell Products
    if (!empty($selectedProductIds)) {
        $inClause = implode(',', array_fill(0, count($selectedProductIds), '?'));
        $stmtProds = $pdo->prepare("SELECT id, name, price, promo_price FROM linkedin_products WHERE id IN ($inClause) AND is_active = 1");
        $stmtProds->execute(array_map('intval', $selectedProductIds));
        $prods = $stmtProds->fetchAll();

        foreach ($prods as $pr) {
            $unitPrice = (float)$pr['price'];
            $finalPrice = $pr['promo_price'] !== null ? (float)$pr['promo_price'] : $unitPrice;
            $discount = $unitPrice - $finalPrice;
            $totalAmount += $finalPrice;

            $lineItems[] = [
                'type' => 'product',
                'item_id' => $pr['id'],
                'name' => $pr['name'],
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'final_price' => $finalPrice
            ];
        }
    }

    // Process Promo Code (Server-side Authoritative Validation)
    $submittedPromoCode = strtoupper(trim($_POST['promo_code'] ?? ''));
    $discountAmount = 0.00;
    if (!empty($submittedPromoCode)) {
        $today = date('Y-m-d');
        $stmtP = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND status = 'active' AND (start_date <= ? AND end_date >= ?)");
        $stmtP->execute([$submittedPromoCode, $today, $today]);
        $promo = $stmtP->fetch();

        if ($promo && ($promo['usage_limit'] === null || $promo['usage_count'] < $promo['usage_limit'])) {
            if ($promo['discount_type'] === 'percent') {
                $discountAmount = round($totalAmount * ((float)$promo['discount_value'] / 100));
            } else {
                $discountAmount = min($totalAmount, (float)$promo['discount_value']);
            }
            $totalAmount = max(0.00, $totalAmount - $discountAmount);


        } else {
            $submittedPromoCode = null;
        }
    } else {
        $submittedPromoCode = null;
    }

    // 3. Handle File Uploads (CV & Payment Proof)
    $uploadDir = __DIR__ . '/../../uploads/linkedin/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $htaccessPath = $uploadDir . '.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents($htaccessPath, "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phps|cgi|pl|exe|sh|js)$\">\n    Order Deny,Allow\n    Deny from all\n</FilesMatch>\nHeader set Content-Disposition attachment\n");
    }

    $uploadedCvPath = null;
    $uploadedCvName = null;
    $uploadedCvSize = 0;
    $uploadedCvType = '';

    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts)) {
            echo json_encode(['success' => false, 'message' => 'Le fichier CV doit être au format PDF, Word (DOC/DOCX) ou Image (PNG/JPG).']);
            exit;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La taille du fichier CV ne doit pas dépasser 10 Mo.']);
            exit;
        }
        $safeCvName = 'CV_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $firstName . '_' . $name) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $safeCvName;
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $uploadedCvPath = 'uploads/linkedin/' . $safeCvName;
            $uploadedCvName = $file['name'];
            $uploadedCvSize = (int)$file['size'];
            $uploadedCvType = $ext;
        } else { throw new RuntimeException('CV upload failed'); }
    }

    $uploadedProofPath = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['payment_proof'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        if (in_array($ext, $allowedExts) && $file['size'] <= 10 * 1024 * 1024) {
            $safeProofName = 'PROOF_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destPath = $uploadDir . $safeProofName;
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $uploadedProofPath = 'uploads/linkedin/' . $safeProofName;
            } else { throw new RuntimeException('Proof upload failed'); }
        }
    }

    // 4. Begin DB Transaction
    $pdo->beginTransaction();
    if ($submittedPromoCode) {
        $claim = $pdo->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE id = ? AND (usage_limit IS NULL OR usage_count < usage_limit)");
        $claim->execute([$promo['id']]);
        if ($claim->rowCount() !== 1) throw new RuntimeException('Promo unavailable');
    }

    $uuid = generateUuid();
    $reference = generateOrderReference($pdo);
    $accessToken = bin2hex(random_bytes(16));

    $stmtOrder = $pdo->prepare("INSERT INTO linkedin_orders (
        uuid, reference, access_token, name, first_name, phone, email, city, country,
        position_status, target_profile_type, linkedin_url, main_objective, target_job,
        target_sector, target_companies, target_locations, experience_level, career_goals,
        package_id, promo_code, discount_amount, total_amount, payment_status, order_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmtOrder->execute([
        $uuid, $reference, $accessToken, $name, $firstName, $phone, $email, $city, $country,
        $positionStatus, $targetProfileType, $linkedinUrl, $mainObjective, $targetJob,
        $targetSector, $targetCompanies, $targetLocations, $experienceLevel, $careerGoals,
        $packageId, $submittedPromoCode, $discountAmount, $totalAmount, $international ? 'PENDING' : 'PAYMENT_SUBMITTED', $international ? 'COMMANDE_RECUE' : 'PAIEMENT_EN_VERIFICATION'
    ]);

    $orderId = $pdo->lastInsertId();

    // Insert Itemized Line Items with Price Snapshots
    $stmtItemInsert = $pdo->prepare("INSERT INTO linkedin_order_items (
        order_id, item_type, item_id, item_name_snapshot, unit_price_snapshot, discount_snapshot, final_price_snapshot
    ) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($lineItems as $item) {
        $stmtItemInsert->execute([
            $orderId, $item['type'], $item['item_id'], $item['name'],
            $item['unit_price'], $item['discount'], $item['final_price']
        ]);

        // Also populate legacy linkedin_order_addons table for backwards compatibility
        if ($item['type'] === 'addon') {
            $pdo->prepare("INSERT INTO linkedin_order_addons (order_id, addon_id, price_at_purchase) VALUES (?, ?, ?)")
                ->execute([$orderId, $item['item_id'], $item['final_price']]);
        }

        // Log purchase analytics for upsell products
        if ($item['type'] === 'product') {
            $pdo->prepare("INSERT INTO linkedin_upsell_analytics (product_id, event_type, order_id) VALUES (?, 'purchase', ?)")
                ->execute([$item['item_id'], $orderId]);
        }
    }

    // Insert Payment Record
    $stmtPay = $pdo->prepare("INSERT INTO linkedin_payments (order_id, payment_method, phone_number, transaction_ref, proof_file, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtPay->execute([$orderId, $paymentMethod, $paymentPhone, $transactionRef, $uploadedProofPath, $international ? 'PENDING' : 'SUBMITTED']);

    // Insert CV Upload Record
    if ($uploadedCvPath) {
        $stmtUp = $pdo->prepare("INSERT INTO linkedin_uploads (order_id, file_path, file_name, file_type, file_size, category) VALUES (?, ?, ?, ?, ?, 'cv')");
        $stmtUp->execute([$orderId, $uploadedCvPath, $uploadedCvName, $uploadedCvType, $uploadedCvSize]);
    }

    // Insert Audit History
    $stmtHist = $pdo->prepare("INSERT INTO linkedin_order_history (order_id, action, note, created_by) VALUES (?, ?, ?, 'Client')");
    $stmtHist->execute([$orderId, 'COMMANDE_CREEE', 'Commande créée (Total: ' . number_format($totalAmount, 0, ',', ' ') . ' FCFA, Réf Tx: ' . $transactionRef . ')']);

    if (preg_match('/^[a-f0-9]{64}$/', $_POST['lead_token'] ?? '')) {
        $pdo->prepare("UPDATE linkedin_checkout_leads SET status=?,order_reference=?,last_step=5 WHERE token_hash=?")->execute([$international ? 'awaiting_payment' : 'converted',$reference,hash('sha256',$_POST['lead_token'])]);
    }
    if (!$international) queue_order_event($pdo, 'linkedin', ['id' => $orderId, 'reference' => $reference, 'name' => $name, 'first_name' => $firstName, 'total_amount' => $totalAmount, 'access_token' => $accessToken], 'completed');
    $pdo->commit();
    send_order_notifications($pdo);

    // 5. Success Response
    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'access_token' => $accessToken,
        'checkout_url' => $checkoutUrl,
        'total_amount' => $totalAmount,
        'redirect_url' => 'linkedin/commande.php?ref=' . $reference . '&token=' . $accessToken,
        'message' => 'Votre commande a été enregistrée avec succès.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.'
    ]);
}
?>
