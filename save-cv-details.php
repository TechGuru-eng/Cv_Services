<?php
// save-cv-details.php
header('Content-Type: application/json');
session_start();

require_once 'admin/config/db.php';
require_once 'includes/order-notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    require_once 'includes/upload_helper.php';
    $uploadedCvPath = secure_upload($_FILES['cvFile'], ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'uploads', 'CV-') ?? '';
}

if ($hasCv === 'yes' && !$uploadedCvPath) {
    exit(json_encode(['success' => false, 'message' => 'Fichier CV invalide.']));
}

try {
    $pdo->beginTransaction();
    $orderRef = $_POST['order_ref'] ?? ($_SESSION['order_ref'] ?? '');
    if (!$orderRef) throw new InvalidArgumentException('Commande introuvable. Veuillez reprendre votre paiement.');
    $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ? AND email = ? FOR UPDATE");
    $stmtOrder->execute([$orderRef, $email]);
    $order = $stmtOrder->fetch();
    if (!$order) throw new InvalidArgumentException('Commande introuvable.');
    $orderId = $order['id'];

    $stmt = $pdo->prepare("INSERT INTO cv_details (order_id, personal_info, education, experience, skills, linkedin, uploaded_cv_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$orderId, $personalInfo, $education, $experience, $skills, $linkedin, $uploadedCvPath]);

    queue_order_event($pdo, 'cv', $order, 'completed');
    $pdo->commit();
    send_order_notifications($pdo);
    echo json_encode(['success' => true, 'order_ref' => $order['order_ref']]);

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("CV Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur interne."]);
}
?>
