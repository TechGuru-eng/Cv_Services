<?php
// save-order.php
header('Content-Type: application/json');
session_start();

// PHPMailer Setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('PHPMailer/src/PHPMailer.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
}

require_once 'admin/config/db.php';
require_once 'admin/includes/security_helper.php';
require_once 'includes/upload_helper.php';

// Rate Limiting
if (isset($_SESSION['last_order_attempt']) && (time() - $_SESSION['last_order_attempt'] < 10)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez patienter.']);
    exit;
}
$_SESSION['last_order_attempt'] = time();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}
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

$hasRecruiterList = intval($_POST['upsell_recruiter'] ?? 0);
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
    // Reject re-use of the same payment transaction reference
    $stmtDup = $pdo->prepare("SELECT id FROM orders WHERE transaction_number = ? AND payment_status <> 'failed' LIMIT 1");
    $stmtDup->execute([$transactionNumber]);
    if ($stmtDup->fetch()) {
        echo json_encode(['success' => false, 'message' => "Ce numéro de transaction a déjà été utilisé. Contactez le support si c'est une erreur."]);
        exit;
    }

    // Generate Order Ref (unguessable suffix)
    $orderRef = 'CMD-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

    // Default package if not provided
    $packageName = $_POST['package_name'] ?? 'Standard';
    
    // Append Upsells to Package Name for Visibility
    $upsellLetter = intval($_POST['upsell_letter'] ?? 0);
    if ($upsellLetter && stripos($packageName, 'Lettre') === false) {
        $packageName .= ' + Lettre Motivation';
    }
    $upsellLinkedin = intval($_POST['upsell_linkedin'] ?? 0);
    if ($upsellLinkedin && stripos($packageName, 'LinkedIn') === false) {
        $packageName .= ' + Boost LinkedIn';
    }
    $upsellCoaching = intval($_POST['upsell_coaching'] ?? 0);
    if ($upsellCoaching && stripos($packageName, 'Coaching') === false) {
        $packageName .= ' + Coaching Entretien';
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
            // Invalid/expired/exhausted code: flag it so the admin does not honour the client-side discount
            $pdo->prepare("UPDATE orders SET promo_code_used = CONCAT('INVALID:', promo_code_used) WHERE id = ?")->execute([$orderId]);
        }
    }

    // Mark Prospect as Converted (Optional, wrap in try-catch)
    try {
        $stmtConv = $pdo->prepare("UPDATE prospects SET status = 'converted' WHERE whatsapp = ? AND status = 'pending'");
        $stmtConv->execute([$whatsapp]);
    } catch (Exception $e) {
        // Ignore if prospects table doesn't exist
    }

    // Revenue is recorded when the admin verifies the payment (admin/order-details.php), not at order creation.

    // --- SPONSORSHIP LOGIC ---
    // Check if this is a sponsorship order.
    // We expect 'qty' in POST? Or we infer from amount/pack?
    // Let's assume the frontend sends 'sponsorship_qty' or we add it to the form.
    // Ideally, we should add 'sponsorship_qty' to the POST data in paiement.html.
    // For now, let's look for a POST field 'sponsorship_qty'. 
    $sponsorshipQty = intval($_POST['sponsorship_qty'] ?? 0);
    
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

    /* 
    // Send Admin Email - DISABLED
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'newsletter@hiremeguide.com';
    $mail->Password = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('newsletter@hiremeguide.com', 'HireMe System');
    $mail->addAddress('moncvpro@hiremeguide.com');
    $mail->Subject = "Nouvelle Commande: $name";

    $adminBody = "
    <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
        <div style='background: #111827; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0; font-size: 20px;'>Nouvelle Commande Reçue</h1>
        </div>
        <div style='padding: 30px; background: #ffffff;'>
            <p style='color: #64748b; font-size: 14px; margin-top: 0;'>Une nouvelle commande vient d'être passée sur MonCV.</p>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;'>Réf:</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: bold; text-align: right;'>$orderRef</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;'>Client:</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: bold; text-align: right;'>$name</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;'>Pack:</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: bold; text-align: right; color: #0ea5e9;'>$packageName</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;'>Montant:</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: bold; text-align: right;'>$amount FCFA</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; color: #64748b;'>Méthode:</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-weight: bold; text-align: right;'>$paymentMethod</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; color: #64748b;'>Transaction:</td>
                    <td style='padding: 10px 0; font-weight: bold; text-align: right;'>$transactionNumber</td>
                </tr>
            </table>
        </div>
        <div style='background: #f8fafc; padding: 20px; text-align: center;'>
            <a href='https://moncvpro.hiremeguide.com/admin/orders.php' style='display: inline-block; background: #0ea5e9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;'>Gérer les commandes</a>
        </div>
    </div>";
    $mail->Body = $adminBody;
    $mail->isHTML(true);

    if ($proofPath)
        $mail->addAttachment($proofPath);

    $mail->send();
    */
    /*
    // Send Customer Confirmation Email - DISABLED
    $custMail = new PHPMailer(true);
    $custMail->isSMTP();
    $custMail->Host = 'smtp.hostinger.com';
    $custMail->SMTPAuth = true;
    $custMail->Username = 'newsletter@hiremeguide.com';
    $custMail->Password = getenv('SMTP_PASSWORD');
    $custMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $custMail->Port = 465;
    $custMail->setFrom('newsletter@hiremeguide.com', 'HireMe CV Services');
    $custMail->addAddress($email);
    $custMail->Subject = "Confirmation de votre commande - HireMe CV";
    $custMail->CharSet = 'UTF-8';

    $custBody = "
    <div style='font-family: sans-serif; max-width: 600px; margin: auto; background: #f8fafc; padding: 20px;'>
        <div style='background: #ffffff; border-radius: 15px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='background: #e0f2fe; color: #0ea5e9; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; display: inline-block; font-size: 30px;'>🎉</div>
                <h2 style='color: #111827; margin: 20px 0 10px;'>Merci pour votre confiance, $name !</h2>
                <p style='color: #64748b; margin: 0;'>Nous avons bien reçu votre commande.</p>
            </div>

            <div style='background: #f1f5f9; padding: 20px; border-radius: 10px; margin-bottom: 30px;'>
                <h3 style='margin: 0 0 15px; font-size: 14px; color: #94a3b8; text-transform: uppercase;'>Résumé de commande</h3>
                <table style='width: 100%;'>
                    <tr>
                        <td style='color: #475569; padding-bottom: 8px;'>Référence :</td>
                        <td style='font-weight: bold; color: #111827; text-align: right; padding-bottom: 8px;'>#$orderRef</td>
                    </tr>
                    <tr>
                        <td style='color: #475569; padding-bottom: 8px;'>Service :</td>
                        <td style='font-weight: bold; color: #0ea5e9; text-align: right; padding-bottom: 8px;'>$packageName</td>
                    </tr>
                    <tr>
                        <td style='color: #475569;'>Statut :</td>
                        <td style='color: #f59e0b; font-weight: bold; text-align: right;'>En attente de vérification</td>
                    </tr>
                </table>
            </div>

            <h4 style='color: #111827; margin: 0 0 10px;'>Et après ?</h4>
            <p style='color: #64748b; font-size: 14px; margin-bottom: 25px;'>
                Notre équipe vérifie actuellement votre paiement. Une fois validé, un expert vous contactera sur WhatsApp pour lancer la rédaction de votre CV.
            </p>

            <div style='text-align: center;'>
                <a href='https://wa.me/237654250688' style='display: inline-block; background: #22c55e; color: white; padding: 14px 30px; text-decoration: none; border-radius: 30px; font-weight: bold; box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.4);'>Nous contacter sur WhatsApp</a>
            </div>

            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 40px 0 20px;'>
            <p style='color: #94a3b8; font-size: 12px; text-align: center; margin: 0;'>
                HireMe CV Services — L'excellence au service de votre carrière.<br>
                &copy; 2024 Tous droits réservés.
            </p>
        </div>
    </div>";
    
    $custMail->Body = $custBody;
    $custMail->isHTML(true);
    $custMail->send();
    */
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur serveur."]);
}
?>