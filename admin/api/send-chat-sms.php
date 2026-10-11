<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// 1. Verify admin session
if (!isset($_SESSION['admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Non autorisé"]);
    exit;
}

// 2. Load configurations and helpers
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sms_helper.php';
require_once __DIR__ . '/../includes/activity_helper.php';

// 3. Retrieve inputs
$recipient = trim($_POST['recipient'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($recipient) || empty($message)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Champs requis manquants"]);
    exit;
}

try {
    // 4. Send SMS via TextBee Android Gateway
    $res = SMSHelper::sendSMS($recipient, $message);
    
    if ($res['success']) {
        log_activity("SMS Tchat", "Message de tchat en attente d’approbation pour " . $recipient);
        
        echo json_encode([
            "success" => true,
            "message" => "SMS préparé. Approuvez son envoi dans le tableau de bord SMS.",
            "log" => [
                "recipient" => SMSHelper::formatPhoneNumber($recipient),
                "message" => htmlspecialchars($message),
                "status" => "pending_approval",
                "created_at" => date('d/m H:i')
            ]
        ]);
        exit;
    } else {
        echo json_encode([
            "success" => false,
            "error" => $res['error'] ?? "Une erreur est survenue lors de l'envoi."
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
}
