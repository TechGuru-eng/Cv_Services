<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['fullName']) || !isset($data['email']) || !isset($data['whatsapp']) || !isset($data['city']) || !isset($data['profession'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit(1);
}

try {
    // Insert Subscriber
    $stmt = $pdo->prepare("INSERT INTO subscribers (full_name, email, whatsapp, city, profession) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['fullName'],
        $data['email'],
        $data['whatsapp'],
        $data['city'],
        $data['profession']
    ]);

    // Update Visitor as Converted
    if (isset($data['sessionId'])) {
        $stmt = $pdo->prepare("UPDATE visitors SET is_converted = 1, email = ?, whatsapp = ? WHERE session_id = ?");
        $stmt->execute([$data['email'], $data['whatsapp'], $data['sessionId']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
