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

    // Send Email
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
    $mail->Body = "<pre>" . print_r($_POST, true) . "</pre>";
    if ($uploadedCvPath)
        $mail->addAttachment($uploadedCvPath);

    $mail->send();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("CV Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur interne."]);
}
?>