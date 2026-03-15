<?php
// api/save_prospect.php
// Captures leads as they enter information, before payment.

header('Content-Type: application/json');
session_start();
require_once '../admin/config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$name = htmlspecialchars($input['name'] ?? '');
$whatsapp = htmlspecialchars($input['whatsapp'] ?? '');
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$city = htmlspecialchars($input['city'] ?? '');
$package = htmlspecialchars($input['package'] ?? 'Unknown');
$amount = floatval($input['amount'] ?? 0);
$has_recruiter_list = intval($input['has_recruiter_list'] ?? 0);
$visit_id = $_SESSION['visit_id'] ?? null;

if (empty($name) || empty($whatsapp)) {
    echo json_encode(['success' => false, 'message' => 'Name and WhatsApp are required']);
    exit;
}

// 0. Ensure Missing Columns Exist
try {
    $pdo->query("ALTER TABLE prospects ADD COLUMN IF NOT EXISTS country VARCHAR(100) DEFAULT NULL");
    $pdo->query("ALTER TABLE prospects ADD COLUMN IF NOT EXISTS source VARCHAR(100) DEFAULT 'Direct'");
} catch (Exception $e) {}

$country = htmlspecialchars($input['country'] ?? 'Unknown');
$source = htmlspecialchars($input['source'] ?? 'Direct');

try {
    // Check if a prospect with this WhatsApp already exists for today or is still pending
    $stmt = $pdo->prepare("SELECT id FROM prospects WHERE whatsapp = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$whatsapp]);
    $prospect = $stmt->fetch();

    if ($prospect) {
        // Update existing prospect
        $stmtUpdate = $pdo->prepare("UPDATE prospects SET name = ?, email = ?, city = ?, country = ?, package_name = ?, total_amount = ?, has_recruiter_list = ?, visit_id = ?, source = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$name, $email, $city, $country, $package, $amount, $has_recruiter_list, $visit_id, $source, $prospect['id']]);
        $prospect_id = $prospect['id'];
    } else {
        // Insert new prospect
        $stmtInsert = $pdo->prepare("INSERT INTO prospects (name, whatsapp, email, city, country, package_name, total_amount, has_recruiter_list, visit_id, source, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmtInsert->execute([$name, $whatsapp, $email, $city, $country, $package, $amount, $has_recruiter_list, $visit_id, $source]);
        $prospect_id = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'prospect_id' => $prospect_id]);

} catch (Exception $e) {
    error_log("Save Prospect Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
