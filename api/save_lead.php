<?php
require_once __DIR__ . '/' . (strpos(__FILE__, DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR) !== false ? '../' : '') . 'includes/upload_helper.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !rate_limit_ok(basename(__FILE__), 20, 600)) { http_response_code(429); header('Content-Type: application/json'); exit(json_encode(['success' => false, 'message' => 'Trop de requêtes. Réessayez plus tard.'])); }
// api/save_lead.php
require_once __DIR__ . '/../includes/cors.php';
header("Content-Type: application/json; charset=UTF-8");

require_once '../admin/config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (empty($data->whatsapp) && empty($data->email)) {
    echo json_encode(["message" => "No contact info"]);
    exit;
}

$name = htmlspecialchars($data->name ?? 'Anonyme');
$email = htmlspecialchars($data->email ?? '');
$whatsapp = htmlspecialchars($data->whatsapp ?? '');
$source = htmlspecialchars($data->source ?? 'Unknown');
$details = json_encode($data->details ?? []);
$ip = $_SERVER['REMOTE_ADDR'];

try {
    // Strategy: Update if exists from same IP/Whatsapp today, else Insert
    // We try to find a recent pending cart for this user
    
    $stmt = $pdo->prepare("SELECT id FROM abandoned_carts WHERE ((whatsapp = ? AND whatsapp != '') OR (email = ? AND email != '')) AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) AND status = 'new' LIMIT 1");
    $stmt->execute([$whatsapp, $email]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $update = $pdo->prepare("UPDATE abandoned_carts SET name = ?, email = ?, whatsapp = ?, cart_details = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$name, $email, $whatsapp, $details, $existing]);
    } else {
        $insert = $pdo->prepare("INSERT INTO abandoned_carts (name, email, whatsapp, source_page, cart_details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute([$name, $email, $whatsapp, $source, $details, $ip]);
    }

    echo json_encode(["message" => "Lead saved"]);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
