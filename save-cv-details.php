<?php
// save-cv-details.php
header('Content-Type: application/json');
session_start();

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email manquant.']);
    exit;
}

// Prepare JSON Data
$personalInfo = json_encode([
    'name' => $_POST['name'] ?? '',
    'whatsapp' => $_POST['whatsapp'] ?? '',
    'location' => $_POST['location'] ?? ''
], JSON_UNESCAPED_UNICODE);

$education = json_encode($_POST['education'] ?? '', JSON_UNESCAPED_UNICODE);
$experience = json_encode($_POST['experience'] ?? '', JSON_UNESCAPED_UNICODE);
$skills = json_encode([
    'skills' => $_POST['skills'] ?? '',
    'languages' => $_POST['languages'] ?? ''
], JSON_UNESCAPED_UNICODE);
$linkedin = htmlspecialchars($_POST['linkedin'] ?? '');
$hasCv = $_POST['hasCv'] ?? 'no';

// Basic Validation for mandatory CV
if ($hasCv === 'yes') {
    if (!isset($_FILES['cvFile']) || $_FILES['cvFile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Veuillez télécharger votre CV actuel.']);
        exit;
    }
}

$uploadedCvPath = '';
if (isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] === UPLOAD_ERR_OK) {
    $fName = $_FILES['cvFile']['name'];
    $tmpName = $_FILES['cvFile']['tmp_name'];
    $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];

    if (in_array($ext, $allowed)) {
        $newName = 'CV-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($tmpName, 'uploads/' . $newName)) {
            $uploadedCvPath = 'uploads/' . $newName;
        }
    }
}

try {
    // Link to Order
    $stmtOrder = $pdo->prepare("SELECT id FROM orders WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmtOrder->execute([$email]);
    $order = $stmtOrder->fetch();
    $orderId = $order ? $order['id'] : null;

    if (!$orderId) {
        // Create a placeholder order if none exists (Handling edge case)
        $orderRef = 'CMD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $stmtNew = $pdo->prepare("INSERT INTO orders (email, name, order_ref, package_name, created_at, payment_status, whatsapp) VALUES (?, 'Inconnu', ?, 'Standard', NOW(), 'pending', 'Non spécifié')");
        $stmtNew->execute([$email, $orderRef]);
        $orderId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT INTO cv_details (order_id, personal_info, education, experience, skills, linkedin, uploaded_cv_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$orderId, $personalInfo, $education, $experience, $skills, $linkedin, $uploadedCvPath]);

    /*
    // Send Email - DISABLED
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'newsletter@hiremeguide.com';
    $mail->Password = 'Dreamer.Sophia#27';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('newsletter@hiremeguide.com', 'HireMe details');
    $mail->addAddress('moncvpro@hiremeguide.com');
    // Also send confirmation to user? User only said "Send ALL CV info to moncvpro@hiremeguide.com". 
    // But details-cv.html implies user gets "validation". I'll add user to CC or just send separate if needed.
    // Prompt: "Send ALL CV info to moncvpro@hiremeguide.com"

    $mail->Subject = "Détails CV Reçus: " . ($_POST['name'] ?? 'Client');
    
    // Format JSON data for readable email
    $info = json_decode($personalInfo, true);
    $edu = json_decode($education, true);
    $exp = json_decode($experience, true);
    $skl = json_decode($skills, true);

    $adminBody = "
    <div style='font-family: sans-serif; line-height: 1.5; color: #333; max-width: 650px; margin: auto;'>
        <div style='background: #0ea5e9; color: white; padding: 25px; border-radius: 10px 10px 0 0;'>
            <h2 style='margin: 0;'>📄 Nouveaux Détails CV</h2>
            <p style='margin: 5px 0 0; opacity: 0.9;'>Client: " . ($info['name'] ?? 'Inconnu') . "</p>
        </div>
        
        <div style='padding: 20px; border: 1px solid #e2e8f0; border-top: 0; border-radius: 0 0 10px 10px;'>
            <h3 style='color: #0ea5e9; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;'>1. Infos Personnelles</h3>
            <p><strong>WhatsApp:</strong> " . ($info['whatsapp'] ?? '-') . "</p>
            <p><strong>Ville/Pays:</strong> " . ($info['location'] ?? '-') . "</p>
            <p><strong>LinkedIn:</strong> " . ($linkedin ?: '-') . "</p>

            <h3 style='color: #0ea5e9; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-top: 30px;'>2. Éducation</h3>
            <div style='background: #f8fafc; padding: 15px; border-radius: 8px; white-space: pre-wrap;'>" . ($edu ?: 'Non renseigné') . "</div>

            <h3 style='color: #0ea5e9; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-top: 30px;'>3. Expérience</h3>
            <div style='background: #f8fafc; padding: 15px; border-radius: 8px; white-space: pre-wrap;'>" . ($exp ?: 'Non renseigné') . "</div>

            <h3 style='color: #0ea5e9; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-top: 30px;'>4. Compétences & Langues</h3>
            <p><strong>Compétences:</strong> " . ($skl['skills'] ?? '-') . "</p>
            <p><strong>Langues:</strong> " . ($skl['languages'] ?? '-') . "</p>
            " . (isset($_POST['instructions']) ? "<p><strong>Instructions:</strong> " . htmlspecialchars($_POST['instructions']) . "</p>" : "") . "
        </div>
    </div>";

    $mail->Body = $adminBody;
    $mail->isHTML(true);
    if ($uploadedCvPath) $mail->addAttachment($uploadedCvPath);
    $mail->send();
    */
    /*
    // Send Customer Confirmation - DISABLED
    $custMail = new PHPMailer(true);
    $custMail->isSMTP();
    $custMail->Host = 'smtp.hostinger.com';
    $custMail->SMTPAuth = true;
    $custMail->Username = 'newsletter@hiremeguide.com';
    $custMail->Password = 'Dreamer.Sophia#27';
    $custMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $custMail->Port = 465;
    $custMail->setFrom('newsletter@hiremeguide.com', 'HireMe CV Services');
    $custMail->addAddress($email);
    $custMail->Subject = "Nous avons bien reçu vos détails - HireMe CV";
    $custMail->CharSet = 'UTF-8';

    $custName = $_POST['name'] ?? 'Client';
    $custBody = "
    <div style='font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;'>
        <div style='background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='background: #dcfce7; color: #16a34a; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; display: inline-block; font-size: 30px;'>✅</div>
                <h2 style='color: #111827; margin: 20px 0 10px;'>Parfait, $custName !</h2>
                <p style='color: #64748b; Vos informations ont été transmises à nos experts.</p>
            </div>
            
            <p style='color: #334155;'>Nous avons maintenant tout ce qu'il nous faut pour créer votre nouveau CV. Voici la suite des événements :</p>
            <ul style='color: #475569; padding-left: 20px;'>
                <li style='margin-bottom: 10px;'>Analyse de votre parcours et de vos objectifs.</li>
                <li style='margin-bottom: 10px;'>Rédaction et mise en page optimisée ATS.</li>
                <li style='margin-bottom: 10px;'>Livraison de votre première version sous 48h.</li>
            </ul>

            <div style='margin-top: 35px; padding: 20px; background: #f0f9ff; border-radius: 10px; text-align: center;'>
                <p style='margin: 0 0 10px; font-size: 14px; color: #0ea5e9;'>Une question ? Nous sommes là.</p>
                <a href='https://wa.me/237654250688' style='font-weight: bold; color: #0ea5e9; text-decoration: none;'>Contactez-nous sur WhatsApp →</a>
            </div>
        </div>
    </div>";

    $custMail->Body = $custBody;
    $custMail->isHTML(true);
    $custMail->send();
    */
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("CV Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur interne."]);
}
?>