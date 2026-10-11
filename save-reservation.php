<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
// save-reservation.php
header('Content-Type: application/json');
session_start();

require_once 'admin/config/db.php';
require_once 'admin/includes/security_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

// Security Check: Blacklist
$rawWhatsapp = $_POST['whatsapp'] ?? '';
$rawEmail = $_POST['email'] ?? '';
if (isBlacklisted($rawWhatsapp, $pdo) || isBlacklisted($rawEmail, $pdo)) {
    echo json_encode(['success' => false, 'message' => 'Désolé, votre accès aux services HireMe a été restreint. Veuillez contacter le support.']);
    exit;
}

// 1. Get Order Data
$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');
$country_city = htmlspecialchars($_POST['location'] ?? '');
$packageName = $_POST['package'] ?? 'Standard';
$dueDate = $_POST['due_date'] ?? null;

// Determine Amount based on reservation prices (including 500 XAF fee)
$amount = 2600;
if ($packageName === 'Pro') $amount = 5100;
elseif ($packageName === 'Senior') $amount = 12500;
elseif ($packageName === 'Expert') $amount = 35500;

if (!$name || !$email || !$whatsapp || !$dueDate) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Create Order
    $orderRef = 'RSV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    
    $stmtOrder = $pdo->prepare("INSERT INTO orders (
        order_ref, name, email, whatsapp, country, city, package_name, 
        payment_status, amount, order_type, due_date, created_at
    ) VALUES (?, ?, ?, ?, '', ?, ?, 'pending', ?, 'advance', ?, NOW())");
    
    $stmtOrder->execute([
        $orderRef, $name, $email, $whatsapp, $country_city, $packageName, 
        $amount, $dueDate
    ]);
    
    $orderId = $pdo->lastInsertId();

    // 3. Handle CV Details
    $personalInfo = json_encode([
        'name' => $name,
        'whatsapp' => $whatsapp,
        'location' => $country_city
    ], JSON_UNESCAPED_UNICODE);

    $education = json_encode($_POST['education'] ?? '', JSON_UNESCAPED_UNICODE);
    $experience = json_encode($_POST['experience'] ?? '', JSON_UNESCAPED_UNICODE);
    $skills = json_encode([
        'skills' => $_POST['skills'] ?? '',
        'languages' => $_POST['languages'] ?? ''
    ], JSON_UNESCAPED_UNICODE);
    
    $linkedin = htmlspecialchars($_POST['linkedin'] ?? '');
    $hasCv = $_POST['hasCv'] ?? 'no';
    $targetJob = htmlspecialchars($_POST['targetJob'] ?? '');

    // Combine manual target job with experience for consistency if needed
    if ($targetJob) {
        $experience = json_encode("Poste Visé: $targetJob\n\n" . ($_POST['experience'] ?? ''), JSON_UNESCAPED_UNICODE);
    }

    $uploadedCvPath = '';
    if ($hasCv === 'yes' && isset($_FILES['cvFile']) && $_FILES['cvFile']['error'] === UPLOAD_ERR_OK) {
        require_once 'includes/upload_helper.php';
        $uploadedCvPath = secure_upload($_FILES['cvFile'], ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], 'uploads', 'CV-RSV-') ?? '';
    }

    $stmtCv = $pdo->prepare("INSERT INTO cv_details (
        order_id, personal_info, education, experience, skills, 
        linkedin, uploaded_cv_path, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmtCv->execute([
        $orderId, $personalInfo, $education, $experience, $skills, 
        $linkedin, $uploadedCvPath
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'order_ref' => $orderRef]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Reservation Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur: " . $e->getMessage()]);
}
?>
