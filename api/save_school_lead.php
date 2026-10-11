<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/cors.php';
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Extract fields
$schoolName = trim($input['school_name'] ?? '');
$city = trim($input['city'] ?? '');
$studentsCount = intval($input['students_count'] ?? 0);
$contactName = trim($input['contact_name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$package = trim($input['package'] ?? 'Non spécifié');

// Validation
$errors = [];
if (empty($schoolName)) $errors[] = "Nom de l'établissement requis.";
if (empty($city)) $errors[] = "Ville requise.";
if ($studentsCount < 1) $errors[] = "Nombre d'étudiants invalide.";
if (empty($contactName)) $errors[] = "Nom du contact requis.";
if (empty($phone)) $errors[] = "Téléphone requis.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO school_leads (school_name, city, students_count, package, contact_name, phone, email, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$schoolName, $city, $studentsCount, $package, $contactName, $phone, $email]);

    echo json_encode(['success' => true, 'message' => 'Demande reçue avec succès via API backend.']);
} catch (PDOException $e) {
    error_log("School Lead Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer via WhatsApp.']);
}
?>
