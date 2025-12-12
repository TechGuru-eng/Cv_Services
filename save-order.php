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

// Rate Limiting
if (isset($_SESSION['last_order_attempt']) && (time() - $_SESSION['last_order_attempt'] < 10)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez patienter.']);
    exit;
}
$_SESSION['last_order_attempt'] = time();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST requimred.']);
    exit;
}

$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');
$country = htmlspecialchars($_POST['country'] ?? 'Non spécifié');
$paymentMethod = htmlspecialchars($_POST['paymentMethod'] ?? '');
$transactionNumber = htmlspecialchars($_POST['transactionId'] ?? ''); // Maps to transactionId from frontend
$amount = 0; // Or fetch from POST if available.
if (isset($_POST['amount']))
    $amount = floatval($_POST['amount']);

// Handle File Upload
$proofPath = '';
if (isset($_FILES['proofFile']) && $_FILES['proofFile']['error'] === UPLOAD_ERR_OK) {
    $fName = $_FILES['proofFile']['name'];
    $tmpName = $_FILES['proofFile']['tmp_name'];
    $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($ext, $allowed)) {
        $newName = 'PAY-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($tmpName, 'uploads/' . $newName)) {
            $proofPath = 'uploads/' . $newName;
        }
    }
}

try {
    // Generate Order Ref
    $orderRef = 'CMD-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

    // Default package if not provided
    $packageName = $_POST['package_name'] ?? 'Standard';

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO orders (order_ref, name, email, whatsapp, country, package_name, payment_method, transaction_number, payment_proof, created_at, payment_status, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?)");
    $stmt->execute([$orderRef, $name, $email, $whatsapp, $country, $packageName, $paymentMethod, $transactionNumber, $proofPath, $amount]);
    $orderId = $pdo->lastInsertId();

    // Insert into Revenue (Optional, but good for tracking)
    if ($amount > 0) {
        $stmtRev = $pdo->prepare("INSERT INTO revenue (order_id, amount, payment_method) VALUES (?, ?, ?)");
        $stmtRev->execute([$orderId, $amount, $paymentMethod]);
    }

    // Send Admin Email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'newsletter@hiremeguide.com';
    $mail->Password = 'Dreamer.Sophia#27';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->setFrom('newsletter@hiremeguide.com', 'HireMe System');
    $mail->addAddress('moncvpro@hiremeguide.com');
    $mail->Subject = "Nouvelle Commande: $name";

    $body = "<h2>Nouvelle Commande Reçue</h2>";
    $body .= "<p><strong>Client:</strong> $name</p>";
    $body .= "<p><strong>Email:</strong> $email</p>";
    $body .= "<p><strong>Transaction:</strong> $transactionNumber</p>";
    $body .= "<p><strong>Preuve:</strong> " . ($proofPath ? "Oui" : "Non") . "</p>";
    $mail->Body = $body;
    $mail->isHTML(true);

    if ($proofPath)
        $mail->addAttachment($proofPath);

    $mail->send();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur serveur."]);
}
?>