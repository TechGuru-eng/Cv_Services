<?php
// save-order.php
header('Content-Type: application/json');
session_start();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit(json_encode(['success' => false, 'message' => 'POST required.']));
}
foreach ($_POST as $value) {
    if (!is_string($value)) {
        http_response_code(422);
        exit(json_encode(['success' => false, 'message' => 'Données de commande invalides.']));
    }
}

require_once 'admin/config/db.php';
require_once 'admin/includes/security_helper.php';
require_once 'includes/upload_helper.php';
require_once 'includes/order-pricing.php';

// Rate Limiting
if (isset($_SESSION['last_order_attempt']) && (time() - $_SESSION['last_order_attempt'] < 10)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez patienter.']);
    exit;
}
$_SESSION['last_order_attempt'] = time();

// IP-based limit (the session limit above is bypassable by dropping the cookie)
if (!rate_limit_ok('save-order', 8, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Trop de tentatives. Réessayez plus tard.']);
    exit;
}

// Security Check: Blacklist
$rawWhatsapp = $_POST['whatsapp'] ?? '';
$rawEmail = $_POST['email'] ?? '';
if (isBlacklisted($rawWhatsapp, $pdo) || isBlacklisted($rawEmail, $pdo)) {
    echo json_encode(['success' => false, 'message' => 'Désolé, votre accès aux services HireMe a été restreint. Veuillez contacter le support.']);
    exit;
}

$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');
$country = htmlspecialchars($_POST['country'] ?? 'Non spécifié');
$city = htmlspecialchars($_POST['city'] ?? 'Non spécifié');
$paymentMethod = htmlspecialchars($_POST['paymentMethod'] ?? '');
$transactionNumber = htmlspecialchars($_POST['transactionId'] ?? ''); // Maps to transactionId from frontend
$amount = 0;
$originalAmount = 0;
$discountAmount = 0;
if (isset($_POST['amount']))
    $amount = floatval($_POST['amount']);
if (isset($_POST['original_amount']))
    $originalAmount = floatval($_POST['original_amount']);
if (isset($_POST['discount_amount']))
    $discountAmount = floatval($_POST['discount_amount']);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Nom et email valides requis.']);
    exit;
}
if ($amount <= 0 || $amount > 10000000 || $originalAmount < 0 || $discountAmount < 0) {
    echo json_encode(['success' => false, 'message' => 'Montant invalide.']);
    exit;
}
if (empty($transactionNumber)) {
    echo json_encode(['success' => false, 'message' => 'Numéro de transaction requis.']);
    exit;
}

if (mb_strlen($transactionNumber) > 100) {
    http_response_code(422);
    exit(json_encode(['success' => false, 'message' => 'Le numéro de transaction doit contenir au maximum 100 caractères.']));
}

$hasRecruiterList = 0;
$promoCodeUsed = $_POST['promo_code'] ?? null;
$affiliateCode = $_POST['affiliate_code'] ?? ($_SESSION['affiliate_code'] ?? null);

// Handle File Upload (validated: real MIME type, size, random server-side name)
$proofPath = null;
if (isset($_FILES['proofFile']) && $_FILES['proofFile']['error'] === UPLOAD_ERR_OK) {
    $proofPath = secure_upload($_FILES['proofFile'], ['jpg', 'jpeg', 'png', 'gif', 'pdf'], 'uploads/proofs', 'PROOF-');
    if ($proofPath === null) {
        echo json_encode(['success' => false, 'message' => 'Fichier de preuve invalide (JPG, PNG ou PDF, 5 Mo max).']);
        exit;
    }
}

try {
    if (in_array(strtoupper(trim($transactionNumber)), ['N/A', 'NA'], true)) {
        if (!$proofPath) throw new InvalidArgumentException('Joignez une preuve de paiement ou un numéro de transaction valide.');
        $transactionNumber = 'PROOF-' . strtoupper(bin2hex(random_bytes(8)));
    }
    $pdo->beginTransaction();
    $quote = order_quote($pdo, $_POST);
    $amount = $quote['amount'];
    $originalAmount = $quote['original'];
    $discountAmount = $quote['discount'];
    $promoCodeUsed = $quote['promo'];
    // Reject re-use of the same payment transaction reference
    $stmtDup = $pdo->prepare("SELECT id FROM orders WHERE transaction_number = ? AND payment_status <> 'failed' LIMIT 1");
    $stmtDup->execute([$transactionNumber]);
    if ($stmtDup->fetch()) {
        $pdo->rollBack();
        if ($proofPath) @unlink(__DIR__ . '/' . $proofPath);
        echo json_encode(['success' => false, 'message' => "Ce numéro de transaction a déjà été utilisé. Contactez le support si c'est une erreur."]);
        exit;
    }

    // Generate Order Ref (unguessable suffix)
    $orderRef = 'CMD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    // Default package if not provided
    $packageName = $quote['name'];
    $deliveryLabel = ['1000' => 'Livraison 24h', '2000' => 'Livraison 12h'][$_POST['delivery_time'] ?? '0'] ?? null;
    if ($deliveryLabel) $packageName .= ' + ' . $deliveryLabel;
    
    // Append Upsells to Package Name for Visibility
    $upsellLetter = intval($_POST['upsell_letter'] ?? 0);
    if ($upsellLetter && stripos($packageName, 'Lettre') === false) {
        $packageName .= ' + Lettre Motivation';
    }

    $ebookMap = [
        'upsellEbook1' => 'Guide 32 Cabinets RH',
        'upsellEbook2' => "50 Questions d'Entretien",
        'upsellEbook3' => 'Formation En Ligne',
        'upsellEbook4' => '100 Messages Pro',
        'upsellEbook5' => 'Pack 60 Modèles CV',
        'upsellEbook6' => 'Pack 300 Lettres',
        'upsellEbook7' => 'Annuaire Entreprises Cameroun'
    ];
    foreach ($ebookMap as $key => $title) {
        if (!empty($_POST[$key]) && intval($_POST[$key]) === 1 && stripos($packageName, $title) === false) {
            $packageName .= ' + ' . $title;
        }
    }

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO orders (order_ref, name, email, whatsapp, country, city, package_name, payment_method, transaction_number, payment_proof, created_at, payment_status, amount, original_amount, discount_amount, promo_code_used, affiliate_code, has_recruiter_list) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$orderRef, $name, $email, $whatsapp, $country, $city, $packageName, $paymentMethod, $transactionNumber, $proofPath, $amount, $originalAmount, $discountAmount, $promoCodeUsed, $affiliateCode, $hasRecruiterList]);
    $orderId = $pdo->lastInsertId();

    // Increment Promo Usage
    if ($promoCodeUsed) {
        // Atomic: honours status, validity dates and usage_limit in a single statement
        $stmtPromo = $pdo->prepare("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE code = ? AND status = 'active' AND start_date <= CURDATE() AND end_date >= CURDATE() AND (usage_limit IS NULL OR usage_count < usage_limit)");
        $stmtPromo->execute([strtoupper(trim($promoCodeUsed))]);
        if ($stmtPromo->rowCount() === 0) {
            throw new InvalidArgumentException('Ce code promo est épuisé. Actualisez votre commande.');
        }
    }

    // Mark Prospect as Converted (Optional, wrap in try-catch)
    try {
        $stmtConv = $pdo->prepare("UPDATE prospects SET status = 'converted' WHERE whatsapp = ? AND status = 'pending'");
        $stmtConv->execute([$whatsapp]);
    } catch (\Throwable $e) {
        // Ignore if prospects table doesn't exist
    }

    // Revenue is recorded when the admin verifies the payment (admin/order-details.php), not at order creation.

    // --- SPONSORSHIP LOGIC ---
    // Check if this is a sponsorship order.
    // We expect 'qty' in POST? Or we infer from amount/pack?
    // Let's assume the frontend sends 'sponsorship_qty' or we add it to the form.
    // Ideally, we should add 'sponsorship_qty' to the POST data in paiement.html.
    // For now, let's look for a POST field 'sponsorship_qty'. 
    $sponsorshipQty = $quote['qty'];
    
    // If pack name implies sponsorship (e.g., from sponsors.html logic)
    // sponsors.html sends pack names like 'Student', 'Pro', 'Don_Libre'.
    // Maybe we should detect a query param or hidden field 'is_sponsorship'.
    $isSponsorship = isset($_POST['is_sponsorship']) && $_POST['is_sponsorship'] == '1';

    if ($isSponsorship && $amount > 0) {
        // Calculate credits if not explicitly sent?
        // If 'Student' (5000), credits = amount / 5000.
        // If 'Pro' (10000), credits = amount / 10000.
        // If 'Don Libre', maybe 1 credit per 5000? Or just cash value?
        // The Plan said "Donor buys specific numbers".
        
        if ($sponsorshipQty <= 0) {
            // Fallback: Calculate based on amount and pack type if qty not sent
            // Prices: Expert=45000, Senior=15000, Pro=6000, Student=2500
            if (stripos($packageName, 'Expert') !== false) {
                $oneCvPrice = 27000;
            } elseif (stripos($packageName, 'Senior') !== false) {
                $oneCvPrice = 9000;
            } elseif (stripos($packageName, 'Pro') !== false) {
                $oneCvPrice = 3200;
            } else {
                $oneCvPrice = 1500; // Student or default
            }
            $sponsorshipQty = floor($amount / $oneCvPrice);
            if ($sponsorshipQty < 1) $sponsorshipQty = 1;
        }
        
        if ($sponsorshipQty > 0) {
            $stmtPool = $pdo->prepare("INSERT INTO sponsorship_pool 
                (order_id, donor_name, package_name, credits_total, credits_remaining, message) 
                VALUES (?, ?, ?, ?, 0, ?)");
            // credits_remaining stays 0 until the admin verifies the payment (admin/order-details.php unlocks it).
            $sponsorshipQty = min((int)$sponsorshipQty, 1000);
            $stmtPool->execute([$orderId, $name, $packageName, $sponsorshipQty, 'Soutien aux talents']);
        }
    }
    // -------------------------

    $pdo->commit();
    $_SESSION['order_ref'] = $orderRef;
    $_SESSION['exit_wheel_order_placed'] = true;
    echo json_encode(['success' => true, 'order_ref' => $orderRef]);

} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($proofPath) @unlink(__DIR__ . '/' . $proofPath);
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($proofPath) @unlink(__DIR__ . '/' . $proofPath);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur serveur."]);
}
?>
