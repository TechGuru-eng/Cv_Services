<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// 1. Verify admin session
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Non autorisé"]);
    exit;
}

// 2. Load DB
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sms_helper.php';

$phone = trim($_GET['phone'] ?? '');

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Numéro requis"]);
    exit;
}

try {
    $formattedPhone = SMSHelper::formatPhoneNumber($phone);
    
    // Fetch all logs for this contact
    $stmt = $pdo->prepare("SELECT * FROM sms_logs WHERE recipient = ? ORDER BY id ASC");
    $stmt->execute([$formattedPhone]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Attempt to match with a client name
    $cleanLocalPhone = preg_replace('/^\+237/', '', $formattedPhone);
    $stmtClient = $pdo->prepare("SELECT name, order_ref FROM orders WHERE whatsapp = ? OR whatsapp = ? OR whatsapp LIKE ? LIMIT 1");
    $stmtClient->execute([$formattedPhone, $cleanLocalPhone, "%" . $cleanLocalPhone]);
    $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
    
    $clientName = $client ? $client['name'] : 'Client Anonyme';
    $orderRef = $client ? $client['order_ref'] : '';
    
    // Format timestamps beautifully
    $formattedMessages = [];
    foreach ($messages as $msg) {
        $formattedMessages[] = [
            "id" => $msg['id'],
            "message" => htmlspecialchars($msg['message']),
            "status" => $msg['status'],
            "clicked" => intval($msg['clicked']),
            "error_message" => htmlspecialchars($msg['error_message'] ?? ''),
            "time" => date('d/m H:i', strtotime($msg['created_at'])),
            "is_incoming" => ($msg['status'] === 'received')
        ];
    }
    
    echo json_encode([
        "success" => true,
        "contact" => [
            "phone" => $formattedPhone,
            "name" => $clientName,
            "order_ref" => $orderRef
        ],
        "messages" => $formattedMessages
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}
