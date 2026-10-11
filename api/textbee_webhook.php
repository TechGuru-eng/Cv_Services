<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/webhook-signature.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'POST required']));
}
$rawInput = file_get_contents('php://input');
$secret = getenv('TEXTBEE_WEBHOOK_SECRET') ?: '';
if ($secret === '') {
    http_response_code(503);
    exit(json_encode(['status' => 'error', 'message' => 'Webhook not configured']));
}
if (!textbee_signature_valid($rawInput, $_SERVER['HTTP_X_SIGNATURE'] ?? '', $secret)) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid signature']));
}
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/../admin/config/sms_helper.php';

// Acknowledge TextBee Webhook request
header("Content-Type: application/json");

// Read raw JSON input
$payload = json_decode($rawInput, true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit;
}

$event = $payload['webhookEvent'] ?? '';

// Try to extract phone number dynamically from payload
$phone = $payload['recipient'] ?? '';
if (empty($phone) && isset($payload['recipients']) && is_array($payload['recipients'])) {
    $phone = $payload['recipients'][0] ?? '';
}
if (empty($phone)) {
    $phone = $payload['sender'] ?? '';
}

if (!empty($phone)) {
    try {
        $formattedPhone = SMSHelper::formatPhoneNumber($phone);
        
        switch ($event) {
            case 'MESSAGE_RECEIVED':
                $messageText = $payload['message'] ?? '';
                // Log incoming SMS
                $stmt = $pdo->prepare("INSERT INTO sms_logs (recipient, message, status, error_message) VALUES (?, ?, 'received', NULL)");
                $stmt->execute([$formattedPhone, $messageText]);
                break;
                
            case 'MESSAGE_SENT':
                // Update latest outgoing log to success if it was error or pending
                $stmt = $pdo->prepare("UPDATE sms_logs SET status = 'success' WHERE recipient = ? AND status = 'error' ORDER BY id DESC LIMIT 1");
                $stmt->execute([$formattedPhone]);
                break;
                
            case 'MESSAGE_DELIVERED':
                // Update latest outgoing log to delivered
                $stmt = $pdo->prepare("UPDATE sms_logs SET status = 'delivered' WHERE recipient = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$formattedPhone]);
                break;
                
            case 'MESSAGE_FAILED':
                // Update latest outgoing log to error with failure reason
                $errDetail = $payload['error'] ?? $payload['message'] ?? 'Failed at mobile gateway';
                $stmt = $pdo->prepare("UPDATE sms_logs SET status = 'error', error_message = ? WHERE recipient = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$errDetail, $formattedPhone]);
                break;
        }
        
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Event processed successfully"]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// Return 200 for unsupported events to avoid infinite TextBee retries
http_response_code(200);
echo json_encode(["status" => "ignored", "message" => "Event ignored or phone number missing"]);
