<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? null;
$notes = $input['notes'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID requis']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch current state for comparison
    $stmt = $pdo->prepare("SELECT status FROM school_leads WHERE id = ?");
    $stmt->execute([$id]);
    $currentLead = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentLead) {
        throw new Exception("Lead non trouvé");
    }

    $fields = [];
    $params = [];
    $activities = [];
    
    if ($status !== null && $status !== $currentLead['status']) {
        $fields[] = "status = ?";
        $params[] = $status;
        $activities[] = [
            'type' => 'status_change',
            'content' => "Status changé de '{$currentLead['status']}' à '{$status}'"
        ];
    }
    
    if ($notes !== null && !empty(trim($notes))) {
        // We still update the main 'notes' field for quick access, but we ALSO log it
        $fields[] = "notes = ?";
        $params[] = $notes;
        $activities[] = [
            'type' => 'note',
            'content' => $notes
        ];
    }
    
    if (!empty($fields)) {
        $params[] = $id;
        $sql = "UPDATE school_leads SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Log activities
        $logStmt = $pdo->prepare("INSERT INTO school_lead_activities (lead_id, type, content) VALUES (?, ?, ?)");
        foreach ($activities as $act) {
            $logStmt->execute([$id, $act['type'], $act['content']]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Mise à jour réussie']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
