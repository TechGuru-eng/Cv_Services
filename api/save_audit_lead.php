<?php
header('Content-Type: application/json');
session_start();
require_once '../admin/config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = $_POST['email'] ?? '';
$auditData = $_SESSION['last_audit'] ?? null;

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email valide requis']);
    exit;
}

try {
    // Check if prospect already exists
    $stmt = $pdo->prepare("SELECT id FROM prospects WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing prospect with audit info
        $stmt = $pdo->prepare("UPDATE prospects SET 
            status = 'lead_audit',
            notes = CONCAT(IFNULL(notes,''), '\nAudit CV effectué: Score ', ?, ' pour le poste de ', ?),
            updated_at = NOW()
            WHERE id = ?");
        $stmt->execute([$auditData['score'] ?? 0, $auditData['job'] ?? 'Inconnu', $existing['id']]);
    } else {
        // Create new prospect
        $stmt = $pdo->prepare("INSERT INTO prospects (email, name, package_name, status, source, notes, created_at) 
                               VALUES (?, ?, ?, 'lead_audit', 'cv_audit', ?, NOW())");
        $name = explode('@', $email)[0]; // Basic name extraction
        $notes = "Audit CV effectué: Score " . ($auditData['score'] ?? 0) . " pour le poste de " . ($auditData['job'] ?? 'Inconnu');
        $stmt->execute([$email, $name, 'Audit Gratuit', $notes]);
    }

    echo json_encode(['success' => true, 'message' => 'Lead enregistré avec succès']);
    
    // Clear session audit
    unset($_SESSION['last_audit']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
