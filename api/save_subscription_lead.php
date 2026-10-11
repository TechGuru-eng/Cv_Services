<?php
header('Content-Type: application/json');
require_once '../admin/config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$fullName = trim($data['full_name'] ?? '');
$whatsapp = trim($data['whatsapp'] ?? '');

// Normalize WhatsApp (Logic Issue 7)
$whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
if (!str_starts_with($whatsapp, '237') && strlen($whatsapp) === 9) {
    $whatsapp = '237' . $whatsapp;
}

$email = trim($data['email'] ?? '');
$plan = trim($data['plan'] ?? '');
$currentJob = trim($data['current_job'] ?? '');
$targetSector = trim($data['target_sector'] ?? '');

if (empty($fullName) || empty($whatsapp) || empty($plan)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir les champs obligatoires']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO subscription_leads (full_name, whatsapp, email, plan, current_job, target_sector) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fullName, $whatsapp, $email, $plan, $currentJob, $targetSector]);
    
    echo json_encode(['success' => true, 'message' => 'Votre demande a été enregistrée avec succès !']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
}
?>
