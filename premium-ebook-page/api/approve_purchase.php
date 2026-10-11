<?php
require 'db.php';
require '../vendor/autoload.php'; // PHPMailer from Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['id'])) {
    die("ID manquant.");
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
$stmt->execute([$id]);
$purchase = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$purchase) {
    die("Commande introuvable.");
}

if ($purchase['status'] === 'approved' || $purchase['delivery_status'] === 'sent') {
    die("Cette commande a déjà été approuvée et livrée.");
}

// Generate token for secure links
$token = md5($purchase['id'] . $purchase['email'] . 'hireme_secret');
$baseUrl = 'https://hireme.cm/premium-ebook-page/api'; // Replace with actual domain in production
// For local testing in Laragon, we can use HTTP_HOST
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/premium-ebook-page/api';
}

$ebook1Link = $baseUrl . "/download.php?id={$id}&type=ebook1&token={$token}";
$ebook2Link = $baseUrl . "/download.php?id={$id}&type=ebook2&token={$token}";
$ebook3Link = $baseUrl . "/download.php?id={$id}&type=ebook3&token={$token}";
$ebook4Link = $baseUrl . "/download.php?id={$id}&type=ebook4&token={$token}";
$cvLink = $baseUrl . "/download.php?id={$id}&type=cv&token={$token}";

// File Paths for attachments
$ebook1Path = __DIR__ . '/../assets/Guide_Ultime.pdf';
$ebook2Path = __DIR__ . '/../assets/Bonus_1.pdf';
$ebook3Path = __DIR__ . '/../assets/Bonus_2.pdf';
$ebook4Path = __DIR__ . '/../assets/Bonus_3.pdf';
$cvPath = __DIR__ . '/../assets/Modele_CV_Pro.zip';

// Prepare email body
$htmlBody = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;'>
    <h2 style='color: #D4AF37;'>Merci pour votre commande !</h2>
    <p>Bonjour <strong>" . htmlspecialchars($purchase['full_name']) . "</strong>,</p>
    <p>Nous avons bien reçu votre paiement et votre commande est maintenant confirmée.</p>
    
    <div style='background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin: 20px 0;'>
        <h3 style='margin-top: 0;'>Reçu de Paiement</h3>
        <p><strong>Montant payé :</strong> " . number_format($purchase['price_paid'], 0, ',', ' ') . " XAF</p>";
        if ($purchase['coupon_used']) {
            $htmlBody .= "<p><strong>Code Promo Utilisé :</strong> " . htmlspecialchars($purchase['coupon_used']) . "</p>";
        }
$htmlBody .= "</div>
    
    <h3>Voici vos accès (Liens de téléchargement) :</h3>
    <p>Vous pouvez télécharger vos fichiers de manière sécurisée en cliquant sur les liens ci-dessous :</p>
    <ul>
        <li><a href='{$ebook1Link}' style='color: #0066cc; font-weight: bold;'>Télécharger Le Guide Ultime (PDF)</a></li>
        <li><a href='{$ebook2Link}' style='color: #0066cc; font-weight: bold;'>Télécharger Bonus 1 (PDF)</a></li>
        <li><a href='{$ebook3Link}' style='color: #0066cc; font-weight: bold;'>Télécharger Bonus 2 (PDF)</a></li>
        <li><a href='{$ebook4Link}' style='color: #0066cc; font-weight: bold;'>Télécharger Bonus 3 (PDF)</a></li>";
        
if ($purchase['upsell_cv']) {
    $htmlBody .= "<br><li><a href='{$cvLink}' style='color: #0066cc; font-weight: bold;'>Télécharger le Modèle de CV Pro (ZIP)</a></li>";
}

$htmlBody .= "
    </ul>
    
    <p><em>Note : Pour plus de facilité, nous avons également mis ces fichiers en <strong>pièces jointes</strong> de cet email.</em></p>

    <br>
    <p>Merci pour votre confiance,</p>
    <p><strong>L'équipe HireMe</strong></p>
</div>
";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'ebook@hiremeguide.com';
    $mail->Password   = (getenv('SMTP_PASSWORD') ?: '');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom('ebook@hiremeguide.com', 'HireMe Premium');
    $mail->addAddress($purchase['email'], $purchase['full_name']);

    // Attachments
    if (file_exists($ebook1Path)) $mail->addAttachment($ebook1Path, 'Le_Guide_Ultime.pdf');
    if (file_exists($ebook2Path)) $mail->addAttachment($ebook2Path, 'Bonus_1.pdf');
    if (file_exists($ebook3Path)) $mail->addAttachment($ebook3Path, 'Bonus_2.pdf');
    if (file_exists($ebook4Path)) $mail->addAttachment($ebook4Path, 'Bonus_3.pdf');

    if ($purchase['upsell_cv'] && file_exists($cvPath)) {
        $mail->addAttachment($cvPath, 'Modele_CV_Pro.zip');
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Votre Commande HireMe : Le Guide Ultime des Cabinets RH';
    $mail->Body    = $htmlBody;

    $mail->send();
    
    // Update DB status
    $updateStmt = $pdo->prepare("UPDATE purchases SET status = 'approved', delivery_status = 'delivered_email' WHERE id = ?");
    $updateStmt->execute([$id]);

    header("Location: ../admin/index.php?success=delivered");
    exit();

} catch (Exception $e) {
    die("L'email n'a pas pu être envoyé. Erreur Mailer: {$mail->ErrorInfo}");
}
?>
