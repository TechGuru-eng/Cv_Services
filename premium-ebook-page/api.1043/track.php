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

if (!isset($data['sessionId'])) {
    echo json_encode(['error' => 'Missing sessionId']);
    exit(1);
}

$sessionId = $data['sessionId'];
$email = isset($data['email']) ? $data['email'] : null;
$whatsapp = isset($data['whatsapp']) ? $data['whatsapp'] : null;

try {
    $stmt = $pdo->prepare("SELECT id FROM visitors WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $visitor = $stmt->fetch();

    if ($visitor) {
        // Update existing visitor
        $updateQuery = "UPDATE visitors SET last_seen = CURRENT_TIMESTAMP";
        $params = [];

        if ($email) {
            $updateQuery .= ", email = ?";
            $params[] = $email;
        }
        if ($whatsapp) {
            $updateQuery .= ", whatsapp = ?";
            $params[] = $whatsapp;
        }
        
        $updateQuery .= " WHERE session_id = ?";
        $params[] = $sessionId;
        
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute($params);
    } else {
        // Insert new visitor
        $stmt = $pdo->prepare("INSERT INTO visitors (session_id, email, whatsapp) VALUES (?, ?, ?)");
        $stmt->execute([$sessionId, $email, $whatsapp]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
