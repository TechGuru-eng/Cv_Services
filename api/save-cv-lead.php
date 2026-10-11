<?php
/**
 * API: Save CV Builder Lead
 * Captures user contact details from the Free CV Builder and syncs with CRM prospects.
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../includes/upload_helper.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'POST requis.'])); }
if (!rate_limit_ok('save-cv-lead', 20, 3600)) { http_response_code(429); exit(json_encode(['success' => false, 'message' => 'Trop de demandes.'])); }
require_once '../admin/config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Entrée invalide']);
    exit;
}

$name = htmlspecialchars(trim($input['name'] ?? ''));
$whatsapp = htmlspecialchars(trim($input['whatsapp'] ?? ''));
$email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$job_title = htmlspecialchars(trim($input['job_title'] ?? ''));
$cv_data = isset($input['cv_data']) ? json_encode($input['cv_data'], JSON_UNESCAPED_UNICODE) : null;
$visit_id = $_SESSION['visit_id'] ?? null;

if (empty($name) || empty($whatsapp)) {
    echo json_encode(['success' => false, 'message' => 'Le nom et le numéro WhatsApp sont requis']);
    exit;
}

// Generate unique slug for Digital CV
function generateUniqueSlug($name, $pdo) {
    $baseSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower(trim($name)));
    $baseSlug = trim($baseSlug, '-');
    if (empty($baseSlug)) $baseSlug = 'cv';
    
    $slug = $baseSlug . '-' . bin2hex(random_bytes(16));
    
    // Ensure uniqueness
    $stmt = $pdo->prepare("SELECT id FROM cv_builder_leads WHERE slug = ?");
    $stmt->execute([$slug]);
    while ($stmt->fetch()) {
        $slug = $baseSlug . '-' . bin2hex(random_bytes(16));
        $stmt->execute([$slug]);
    }
    return $slug;
}

try {
    // 1. Save in cv_builder_leads table
    // Check if WhatsApp already exists
    $stmtCheck = $pdo->prepare("SELECT id, slug FROM cv_builder_leads WHERE id = ? AND whatsapp = ? LIMIT 1");
    $stmtCheck->execute([$_SESSION['cv_builder_lead_id'] ?? 0, $whatsapp]);
    $existingLead = $stmtCheck->fetch();

    if ($existingLead) {
        $leadId = $existingLead['id'];
        $slug = $existingLead['slug'] ?: generateUniqueSlug($name, $pdo);
        $stmtUp = $pdo->prepare("UPDATE cv_builder_leads SET name = ?, email = ?, job_title = ?, slug = ?, cv_data = ? WHERE id = ?");
        $stmtUp->execute([$name, $email, $job_title, $slug, $cv_data, $leadId]);
    } else {
        $slug = generateUniqueSlug($name, $pdo);
        $stmtIns = $pdo->prepare("INSERT INTO cv_builder_leads (name, whatsapp, email, job_title, slug, cv_data) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([$name, $whatsapp, $email, $job_title, $slug, $cv_data]);
        $leadId = $pdo->lastInsertId();
    }

    // 2. Synchronize with prospects table (for automated follow-ups / relances)
    // We offer them the premium upgrade at 2000 FCFA
    $stmtProp = $pdo->prepare("SELECT id FROM prospects WHERE whatsapp = ? AND status = 'pending' LIMIT 1");
    $stmtProp->execute([$whatsapp]);
    $existingProp = $stmtProp->fetch();

    $packageName = "Optimisation CV Premium (Free Builder)";
    $amount = 2000.00;
    $source = "CV Builder Free";

    if ($existingProp) {
        $stmtPropUp = $pdo->prepare("UPDATE prospects SET name = ?, email = ?, package_name = ?, total_amount = ?, source = ?, updated_at = NOW() WHERE id = ?");
        $stmtPropUp->execute([$name, $email, $packageName, $amount, $source, $existingProp['id']]);
    } else {
        $stmtPropIns = $pdo->prepare("INSERT INTO prospects (name, whatsapp, email, package_name, total_amount, source, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())");
        $stmtPropIns->execute([$name, $whatsapp, $email, $packageName, $amount, $source]);
    }

    $_SESSION['cv_builder_lead_id'] = $leadId;
    echo json_encode(['success' => true, 'lead_id' => $leadId, 'slug' => $slug]);

} catch (Exception $e) {
    error_log("Save CV Lead Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible d’enregistrer le CV. Réessayez plus tard.']);
}
