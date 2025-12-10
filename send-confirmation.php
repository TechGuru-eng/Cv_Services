<?php
// send-confirmation.php
// Backend script to send confirmation emails using PHPMailer and Hostinger SMTP.

// IMPORTANT: Install PHPMailer via Composer or Manual Include
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// 1. Dependency Loading
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('PHPMailer/src/PHPMailer.php')) {
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Configuration Error: PHPMailer not found.']);
    exit;
}

header('Content-Type: application/json');

// 2. Rate Limiting (Simple Session-Based)
session_start();
if (!isset($_SESSION['last_submission'])) {
    $_SESSION['last_submission'] = 0;
}
if (time() - $_SESSION['last_submission'] < 10) { // 10 seconds delay
    echo json_encode(['success' => false, 'message' => 'Veuillez patienter quelques instants avant de renvoyer le formulaire.']);
    exit;
}
$_SESSION['last_submission'] = time();

// 3. Input Handling (FormData -> $_POST)
$input = $_POST; // Since we use FormData, data is in $_POST, not php://input

if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue via POST.']);
    exit;
}

// 4. Input Sanitization
$name = isset($input['name']) ? htmlspecialchars(strip_tags($input['name'])) : 'Client';
$email = isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : '';
$whatsapp = isset($input['whatsapp']) ? htmlspecialchars(strip_tags($input['whatsapp'])) : 'Non spécifié';
$location = isset($input['location']) ? htmlspecialchars(strip_tags($input['location'])) : 'Non spécifié';

// Additional dynamic fields
$detailsHtml = "<ul>";
if ($input['hasCv'] === 'yes') {
    $instructions = isset($input['instructions']) ? nl2br(htmlspecialchars($input['instructions'])) : 'Aucune';
    $detailsHtml .= "<li><strong>Type :</strong> Refonte CV Existant</li>";
    $detailsHtml .= "<li><strong>Instructions :</strong> $instructions</li>";
} else {
    $targetJob = isset($input['targetJob']) ? htmlspecialchars($input['targetJob']) : '-';
    $detailsHtml .= "<li><strong>Type :</strong> Création Complète</li>";
    $detailsHtml .= "<li><strong>Poste Visé :</strong> $targetJob</li>";
    // We can add the full text details if needed, or just attach them as a summary
}
$detailsHtml .= "</ul>";

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide.']);
    exit;
}

// 5. Secure File Upload Logic
$uploadedFile = null;
if (isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['cvFile']['tmp_name'];
    $fileName = $_FILES['cvFile']['name'];
    $fileSize = $_FILES['cvFile']['size'];
    $fileType = $_FILES['cvFile']['type'];

    // Validate Extension
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    $allowedfileExtensions = array('pdf', 'doc', 'docx', 'jpg', 'png');

    // Validate MIME Type (Basic check)
    $allowedMimeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    if (in_array($fileExtension, $allowedfileExtensions) && in_array($mime, $allowedMimeTypes)) {
        // Limit size (5MB)
        if ($fileSize < 5 * 1024 * 1024) {
            // New Name (Randomized)
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = './uploads/';
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedFile = $dest_path; // Success
            }
        }
    }
}

// 6. Send Email
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'newsletter@hiremeguide.com'; // Use env vars in prod
    $mail->Password = 'Dreamer.Sophia#27';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    //Recipients
    $mail->setFrom('newsletter@hiremeguide.com', 'HireMe CV Services');
    $mail->addAddress($email, $name);
    // Also send admin notification
    $mail->addBCC('moncvpro@hiremeguide.com');
    $mail->addReplyTo('moncvpro@hiremeguide.com', 'Support HireMe');

    // Attachments
    if ($uploadedFile) {
        $mail->addAttachment($uploadedFile);
    }

    //Content
    $mail->isHTML(true);
    $mail->Subject = "Confirmation de commande - HireMe CV";

    $bodyContent = "
    <div style='font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #0ea5e9;'>Bonjour $name,</h2>
        <p>Nous avons bien reçu vos informations. Notre équipe va maintenant traiter votre commande.</p>
        
        <h3>Récapitulatif :</h3>
        <ul>
            <li><strong>Nom :</strong> $name</li>
            <li><strong>Email :</strong> $email</li>
            <li><strong>WhatsApp :</strong> $whatsapp</li>
            <li><strong>Ville :</strong> $location</li>
        </ul>
        
        $detailsHtml

        <p>Si vous avez envoyé un CV, il est en pièce jointe de ce mail pour confirmation.</p>
        
        <p style='margin-top:20px; font-size:0.9em; color:#666;'>Besoin d'aide ? Contactez-nous sur WhatsApp au 654 250 688.</p>
    </div>
    ";

    $mail->Body = $bodyContent;
    $mail->AltBody = strip_tags($bodyContent);

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erreur d'envoi: {$mail->ErrorInfo}"]);
}
?>