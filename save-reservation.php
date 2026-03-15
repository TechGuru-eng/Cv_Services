<?php
// save-reservation.php
header('Content-Type: application/json');
session_start();

require_once 'admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

// 1. Get Order Data
$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');
$country_city = htmlspecialchars($_POST['location'] ?? '');
$packageName = $_POST['package'] ?? 'Standard';
$dueDate = $_POST['due_date'] ?? null;

// Determine Amount based on reservation prices
$amount = 2100;
if ($packageName === 'Pro') $amount = 4600;
elseif ($packageName === 'Senior') $amount = 12000;
elseif ($packageName === 'Expert') $amount = 35000;

if (!$name || !$email || !$whatsapp || !$dueDate) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. Create Order
    $orderRef = 'RSV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    
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
        $fName = $_FILES['cvFile']['name'];
        $tmpName = $_FILES['cvFile']['tmp_name'];
        $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];

        if (in_array($ext, $allowed)) {
            $newName = 'CV-RSV-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            if (move_uploaded_file($tmpName, 'uploads/' . $newName)) {
                $uploadedCvPath = 'uploads/' . $newName;
            }
        }
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
